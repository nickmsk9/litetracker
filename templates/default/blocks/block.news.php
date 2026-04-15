<?
if (!defined('LITETRACKER'))
die('Direct access denied.');
?>
<div style="padding-bottom: 5px;"><a onclick="$('#newsblock').slideToggle('fast');" href="javascript:void(0)">
 <div valign="center" class="menuzag">
 <span valign="center">Последние новости</span>
 </div>
 </a>
 <div style="display: none;" class="menubody" id="newsblock">
 <?
	foreach($news_array as $arr) {
		
		if (strlen($arr['name']) > 30)
			$arr['name'] = substr($arr['name'] , 0, 30).'...';
		
		
		if (strlen($arr['text']) > 150)
			$arr['text'] = substr($arr['text'] , 0, 150).'...'; 
		
		/////////////////////////////////////////////////////////
		//Пользователь
		/////////////////////////////////////////////////////////
		$user = get_user_info($arr['id_user']);
		//ID пользователя
		$id_user = $user['id'];
		//Имя пользователя
		$user_name = $user['name'];
		//Класс пользователя
		$user_class = $user['class'];
		
		?>
		
		
		<a class="proleft" href='news.php?id=<?=$arr['id'];?>' rel='bookmark' title='<?=$language['news_18'];?>'><?=htmlspecialchars($arr['name']);?></a> 
		
		
		
		<?
	}
	?>
 
 </div>
 
</div>
