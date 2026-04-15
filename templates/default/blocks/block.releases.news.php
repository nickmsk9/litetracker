<?
if (!defined('LITETRACKER'))
	die('Direct access denied.');


////////////////////////////////////////////////////////
//Шаблон для Новых релизов
////////////////////////////////////////////////////////

 
if($count == 0) echo '<tr>'; 
?>

<td width="auto" height="300">
<? begin_frame(); ?>
<b><a href="details.php?id=<?=$id;?>"><?=htmlspecialchars(substr($row['name'] , 0 , 32)).'...';?></a></b>
<hr>
<center>
<a href="details.php?id=<?=$id;?>"><img src="public/downloads/images/<?=$image;?>" width="240px" height="300px"></a>



<table width="100%" style="padding:0" cellpadding=0>
	<tr>
		<td><img src="public/images/up.png"> <?=$language['details_10'];?> <?=$seeders;?> <img src="public/images/down.png"> <?=$language['details_11'];?> <?=$leechers;?> <img src="public/images/ok.gif"> Скачали <?=$row['completed'];?></td>
	</tr>
</table>

<? end_frame(); ?>
</td>

<? if($count == 2) { 
	$count = 0 ; 
	echo '</tr>';
} 
?>

