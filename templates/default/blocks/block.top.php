<?
if (!defined('LITETRACKER'))
	die('Direct access denied.');


////////////////////////////////////////////////////////
//Шаблон для TOP 10
////////////////////////////////////////////////////////

?>

	<div class='general_box clearfix'>
	<h3><img src='templates/<?=$config['template'];?>/images/comment_new.png' alt='Иконка' />Топ - 10</h3>
	<ul class='hfeed block_list'>
			
	
	<?
	$i = 0;
	foreach($top_array as $arr) {
	$i++;
	?>
		<li>
		
		<b>#<?=$i;?></b> <a href='details.php?id=<?=$arr['id'];?>' rel='bookmark' title=''><?=htmlspecialchars($arr['name']);?></a> 
		<br><span class='date'>Рейтинг : <?=($arr['rating_up']-$arr['rating_down']);?></span>
		
		</li>

		
		
		<?
	}
	?>
			
	</ul>
	</div>