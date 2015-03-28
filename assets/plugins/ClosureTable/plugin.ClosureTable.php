<?php
/**
 * ClosureTable
 *
 * Closure Table database design pattern implementation for MODX Evolution
 *
 * @version     1.0.0
 * @license     http://www.gnu.org/copyleft/gpl.html GNU Public License (GPL)
 * @category    plugin
 * @author  	Agel Nash <modx@agel-nash.ru>
 * @see 		http://planet.mysql.com/entry/?id=27321
 * @internal    @legacy_names ClosureTable
 * @internal    @modx_category API
 * @internal    @events onAfterMoveDocument,OnDocDuplicate,OnEmptyTrash,OnDocFormSave
 */
if (!defined('MODX_BASE_PATH')) { die('HACK???'); }

$table = $modx->getFullTableName('site_content_tree');

if( ! $modx->db->getRecordCount($modx->db->query("SHOW TABLES LIKE '$table'"))){
	$sql = <<< OUT
CREATE TABLE $table (
  `ancestor` int(10) unsigned NOT NULL,
  `descendant` int(10) unsigned NOT NULL,
  `depth` int(10) unsigned NOT NULL,
  PRIMARY KEY (`ancestor`,`descendant`),
  KEY `site_content_tree_descendant` (`descendant`),
  KEY `site_content_tree_ancestor` (`ancestor`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
OUT;
	$modx->db->query($sql);
}

if(!function_exists('getParentIDs')){
	function getParentIDs($modx, $id, $useRoot = false){
		$out = array();
		$parent = $modx->db->getValue("SELECT parent FROM ".$modx->getFullTableName('site_content')." WHERE id=".(int)$id);
		if(!is_null($parent) && (!empty($parent) || $useRoot)){
			$out[] = $parent;
			$out = array_merge($out, getParentIDs($modx, $parent));
		}
		return $out;
	}
}

if(!function_exists('insertSiteContentTree')){
	function insertSiteContentTree($modx, $table, $id){
		$parents = getParentIds($modx, (int)$id);
		$parents = array_merge(array($id), array_values($parents));
		$i = 0;
		foreach($parents as $parent){
			$fields = array(
				'ancestor' => (int)$parent,
				'descendant' => (int)$id,
				'depth' => $i++
			);
			$modx->db->query("INSERT IGNORE ".$table." (`".implode("`, `", array_keys($fields))."`) VALUES('".implode("', '", array_values($fields))."')");
		}
	}
}
switch($modx->event->name){
	case 'onAfterMoveDocument':{
		$modx->db->query("DELETE t1 FROM ".$table." AS t1 JOIN ".$table." AS t2 ON t1.descendant = t2.descendant
							LEFT JOIN ".$table." AS t3 ON t3.ancestor = t2.ancestor AND t3.descendant = t1.ancestor
							WHERE t2.ancestor = ".(int)$id_document." AND t3.ancestor IS NULL");

		$modx->db->query("INSERT INTO ".$table." (ancestor, descendant, depth)
						SELECT t1.ancestor, t2.descendant, t1.depth+t2.depth+1
						FROM ".$table." AS t1 JOIN ".$table." AS t2
						WHERE t2.ancestor = ".(int)$id_document." AND t1.descendant = ".(int)$new_parent);
		break;
	}
	case 'OnDocDuplicate':{
		$modx->db->query("INSERT INTO ".$table." (ancestor, descendant, depth)
						SELECT t.ancestor, ".$new_id.", t.depth FROM ".$table." AS t
						WHERE t.descendant = ".$id." UNION ALL SELECT ".$new_id.", ".$new_id.", 0");
		$modx->db->query("DELETE FROM ".$table." WHERE descendant=".$new_id." AND ancestor = ".$id." AND depth=0");
		break;
	}
	case 'OnEmptyTrash':{
		if(is_scalar($ids)) $ids = explode(",", $ids);
		if( ! is_array($ids)) $ids = array();
		foreach($ids as $id){
			$id = (int)trim($id);
			$modx->db->query("DELETE FROM ".$table." WHERE descendant IN (SELECT descendant FROM (SELECT descendant FROM ".$table." WHERE ancestor = ".$id.") as tmp)");
		}
		break;
	}
	case 'OnDocFormSave':{
		switch($mode){
			case 'upd':{
				$old_parent = $modx->db->getValue('SELECT ancestor FROM '.$table.' WHERE descendant = '.$id.' ORDER BY depth DESC LIMIT 0, 1');
				$new_parent = $modx->db->getValue("SELECT parent FROM ".$modx->getFullTableName('site_content').' WHERE id = '.$id);
				if(is_null($old_parent) || $old_parent == $id){
					insertSiteContentTree($modx, $table, $id);
					$old_parent = $new_parent;
				}
				if($new_parent!=$old_parent){
					$modx->invokeEvent("onAfterMoveDocument", array (
						"id_document" => $id,
						"old_parent" => $old_parent,
						"new_parent" => $new_parent
					));
				}
				break;
			}
			case 'new':{
				insertSiteContentTree($modx, $table, $id);
				break;
			}
		}
		break;
	}
}