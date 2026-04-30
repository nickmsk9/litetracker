<?
if (!defined('LITETRACKER'))
	die('Direct access denied.');


////////////////////////////////////////////////////////
//Шаблон для Вывода категорий
////////////////////////////////////////////////////////

?>
<tr>
<td width="1%">
<a class="release-category-chip" href="browse.php?id_category=<?=$id;?>">#<?=$id;?></a>
</td>

<td valign="top" width="50%">
<a href="browse.php?id_category=<?=$id;?>" title='Перейти к релизам'><?=$name;?></a>
</td>

<td class='altrow stats'>

Загружено <?=$count;?> релизов <hr>
<b>Общий размер <?=$size?></b>

</ul>
</td>

</tr>
