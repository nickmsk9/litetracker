<?
if (!defined('LITETRACKER'))
	die('Direct access denied.');


////////////////////////////////////////////////////////
//Шаблон для Статистика
////////////////////////////////////////////////////////
begin_frame('Статистика');

?>
		<table witdh="50%" align="center">
			<tr>
				<td><?=$language['stats_2'];?></td>
				<td><?=$torrents;?></td>
			</tr>
			
			<tr>
				<td><?=$language['stats_3'];?></td>
				<td><?=$seeders;?> ( из них гостей <?=$seeders_guest;?> )</td>
			</tr>
			<tr>
				<td>Скачано торрентов</td>
				<td><?=$completed;?></td>
			</tr>
				
				
			<tr>	
				<td><?=$language['stats_4'];?></td>
				<td><?=$leechers;?></td>
			<tr>	
				<td><?=$language['stats_5'];?></td>
				<td><?=($peers);?></td>
			</tr>

			<tr>
				<td><?=$language['stats_6'];?></td>
				<td><?=($size);?></td>
			</tr>

			<tr>
				<td>Пользователей</td>
				<td><?=($registered);?> <?=($registered_day > 0 ? '( +'.$registered_day.' сегодня)' : '');?></td>
				
				
				
			</tr>
			
		</table>
<? end_frame();?>
