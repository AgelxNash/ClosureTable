### Closure Table for MODX Evolution
**На данный момент, плагин полностью готов к работе только на [сборке Дмитрия](https://github.com/dmi3yy/modx.evo.custom) версии не ниже 1.1b-d7.1**

Чтобы детальней понять суть данного компонента советую ознакомиться со следующим материалом:
* [Презентация Билла Карвина о способах хранения и обработки иерархических данных в MySQL](http://www.slideshare.net/billkarwin/models-for-hierarchical-data)
* [Описание паттерна Closure Table](http://planet.mysql.com/entry/?id=27321)

### Примеры SQL запросов
##### Получение всех дочерних документов относительно записи с ID 666
```sql
SELECT `c`.`id`, `c`.`pagetitle`, `t`.`descendant`, `t`.`depth` 
FROM `modx_site_content` as `c`
JOIN `modx_site_content_tree` as `t` ON `c`.`id` = `t`.`descendant`
WHERE `t`.`ancestor` = 666 AND `t`.`depth` > 0
ORDER BY `t`.`depth` ASC, `c`.`menuindex` ASC
```
##### Получение всего стека родительсих документов для записи с ID 666
```sql
SELECT `c`.`id`, `c`.`pagetitle`, `t`.`descendant`, `t`.`depth` 
FROM `modx_site_content` as `c`
JOIN `modx_site_content_tree` as `t` ON `c`.`id` = `t`.`ancestor`
WHERE `t`.`descendant` = 666 AND `t`.`depth` > 0
ORDER BY `t`.`depth` DESC
```

### Преимущества
* Нет жесткой привязки к массиву aliasListing в отличии от UltimateParent
* Возможность получения 1 SQL запросом всего дерева дочерних ресурсов без цепочки запросов в стиле 
```sql
SELECT `id` FROM `modx_site_content` WHERE `parent` IN(
    SELECT `id` FROM `modx_site_content` WHERE `parent` IN (
        SELECT `id` FROM `modx_site_content` WHERE `parent` IN (
            ....
        )
    )
)
```

### Установка
После установки плагина ClosureTable, необходимо переиндексировать дерево для уже существующих документов. Чтобы это сделать, нужно пересохранить каждый имеющийся документ в дереве. Если установка плагина выполнялась на чистый MODX, а в дереве не так много документов, то выполнить процедуру пересохранения можно и руками. В противном же случае потребуется установить MODxAPI из репозитория [DocLister](https://github.com/AgelxNash/DocLister). После чего воспользоваться следующим кодом:
```php
include_once(MODX_BASE_PATH.'assets/lib/MODxAPI/modResource.php');
$DocObj = new modResource($modx);

$q = $modx->db->query("SELECT id FROM ".$modx->getFullTableName('site_content'));
while($row = $modx->db->getRow($q)){
    $DocObj->edit($row['id'])->save(true, true);
}
```

### Автор
---------
<table>
  <tr>
    <td><img src="http://www.gravatar.com/avatar/bf12d44182c98288015f65c9861903aa?s=220"></td>
	<td valign="top">
		<h4>Борисов Евгений
			<br />
			Agel Nash
		</h4>
		<a href="http://agel-nash.ru">http://agel-nash.ru</a><br />
		<br />
		<strong>ICQ</strong>: 8608196<br />
		<strong>Skype</strong>: agel.nash<br />
		<strong>Email</strong>: modx@agel-nash.ru
	</td>
	<td valign="top">
		<h4>Реквизиты для доната<br /><br /></h4>
		<br /><br />
		<strong>WMZ</strong>: Z762708026453<br />
		<strong>WMR</strong>: R203864025267<br />
		<strong>PayPal</strong>: agel_nash@xaker.ru<br />
	</td>
  </tr>
</table>