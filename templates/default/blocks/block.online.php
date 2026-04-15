<?
if (!defined('LITETRACKER'))
die('Direct access denied.');


////////////////////////////////////////////////////////
//Шаблон для Онлайн
////////////////////////////////////////////////////////
//$numUsers - Количество пользователей онлайн
//$numGuests - Количество гостей онлайн
//$onlineList - Имена онлайн
//$numAll - Количество за день

begin_frame('Кто он-лайн');
?>

	<div id='active_users' class='stats_list'>
			<b>Кто в сети: </b>
				<span class='desc'><?=$numUsers;?> пользователей, <?=$numGuests;?> гостей
				</span>

			<hr>
		
					<?=$onlineList;?>
			
			
		</div>
<?
end_frame();
?>