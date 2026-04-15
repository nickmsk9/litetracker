<?
if (!defined('LITETRACKER'))
	die('Direct access denied.');


////////////////////////////////////////////////////////
//Шаблон для Релизы
////////////////////////////////////////////////////////
//$id - ID релиза
//$image - Обложка
//$name - Название релиза
//$descr - Описание релиза
//$id_user - ID пользователя
//$user_name - Имя пользователя
//$user_class - Класс пользователя
//$seeders - Раздают
//$leechers - Качают
//$peers - Пиры
//$cat_name - Имя категории
//$cat_id - ID категории
//$cat_image - Картинка категории
//$downloaded - Взяли релиз
//$completed - Скачали релиз
//$size - Размер релиза
//$type_seeders - Тип раздачи
//$video_vkontakte - Видео ВКонтакте
// begin_frame($name);



?>
<tr>
	<td rowspan="2" align="center"><a href="browse.php?id_category=<?=$cat_id;?>"><img src="public/images/categories/<?=$cat_image;?>"></a></td>
	<td colspan="7"><?=($news ? '<span class="topic_prefix">'.$language['releases_12'].'</span>' : '');?> <a href="details.php?id=<?=$id;?>" title='<?=$language['releases_10'];?>'><font <?=($banned ? 'color="red"' : '') ;?>  onmouseover="setTimeout('Tip(\'<img width=&quot;200&quot;  src=public/downloads/images/<?=$image;?>>\')', 1);" onmouseout="setTimeout('UnTip()', 1);" ";
><?=$name;?></font></a>  <?=$type_seeders;?> <?=$video_vkontakte;?></td>
	
	<tr>
		<td>Метки: <?=$tags;?></td>
		<td align="center"><?=$size;?></td>
		<td align="center"><?=$seeders;?></td>
		<td align="center"> <?=$leechers;?> </td>
		<td align="center"> <?=$num_files;?> </td>
		<td align="center"> <?=$completed;?> </td>
		<?=($PRIV['edit_release'] || $_SERVER['PHP_SELF'] == '/my.book.php' ? '<td align="center"><input type="checkbox" value="'.$id.'" name="check[]">' : '');?></td>
		
	</tr>
</tr>
<?	


?>
