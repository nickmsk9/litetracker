<?
if (!defined('LITETRACKER'))
	die('Direct access denied.');


////////////////////////////////////////////////////////
//Шаблон для TOP 10
////////////////////////////////////////////////////////

?>

	<div class='general_box clearfix'>
	<h3><img src='templates/<?=$config['template'];?>/images/comment_new.png' alt='Иконка' />Лучшие раздающие</h3>
	
	<table class='ipb_table'>
	<!--<tr class='header'>
	<th scope='col' class='col_c_icon'>&nbsp;</th>
	<th scope='col' class='col_c_forum'>Ник</th>
	</tr>-->
	<?
	$i = 0;
	foreach($top_array as $arr) {
	$i++;
	?>
		<tr>
		<td width="1%">
		<a href="profile.php?id=<?=$arr['id'];?>"><?=($arr['avatar'] ? '<img src="public/avatars/'.$arr['avatar'].'" width="50">' : '<img src="public/images/default_avatar.gif" width="50">');?></a>
		
		</td>
		
		<td>
		<a href="profile.php?id=<?=$arr['id'];?>"><?=get_user_color($arr['class'] , $arr['name']);?></a>
		<br>
		<b>Раздал:</b><?=mksize($arr['uploaded']);?>
		<br>
		<b>Скачал:</b><?=mksize($arr['downloaded']);?>
		</td>
		</tr>
		
		<?
	}
	?>
	</table>	
	
	</div>