<?
/*
===================================================================
LiteTracker Source
===================================================================
by jenaDI
-------------------------------------------------------------------
Назначение: Новые релизы
===================================================================
*/

global $memcache , $db ,  $language ,$config , $PRIV; 
if (false === ($news_releases = $memcache->get('news_releases'))) {
	
	$sql = $db->query("SELECT t.* ,  SUM(tr.seeders) AS seeders  , SUM(tr.leechers) AS leechers 
				FROM torrents  AS t 
				LEFT JOIN trackers AS tr ON tr.torrent = t.id 
				WHERE t.banned = '0' AND t.news='1' AND (infohash <> '' AND image <> '') AND  ADDDATE(t.added, INTERVAL ".$config['releases_news']." DAY) > NOW() GROUP BY t.id   ORDER BY t.added DESC LIMIT 25 ");
	while($row = $db->get_row($sql) ) 
		$news_releases[] = $row;
	$memcache->set('news_releases', $news_releases  , 0, (15*60));		
}


if($news_releases) {
	
	echo '<script type="text/javascript" src="public/js/wz_tooltip.js"></script>';  

	?>

	
	<table align="center">
	<?
	$rows = 0;
	$count = 0;
	foreach($news_releases AS $row) {
		$count++;
		$class = ($rows++ % 2) ? 'row1' : 'row2'; // $row is zero-based 
		//Номер релиза
		$id = $row['id'];
		
		//Размер файла
		$size = mksize($row['size']);
		
		$descr = array();
		//Год
		if($row['year'] != 0) {
			$descr[] = htmlspecialchars($row['year']);
		}	
		//Язык
		if($row['language'] != '') {
			$descr[] = htmlspecialchars($row['language']);
		}
		//Качество
		if($row['quality'] != '') {
			$descr[] = htmlspecialchars($row['quality']);
		}
		
		//Собираем массив
		$descr = (count($descr) ? implode(' / ' , $descr ) : '');
		
		//Обложка
		$image = $row['image'];
		
		//Информация о Категории
		$category = categories_array($row['id_category']);

		//Имя категории
		$cat_name = htmlspecialchars($category['name']);

		//ID Категории
		$id_category = $category['id'];

		//Картинка Категории
		$cat_image = $category['image'];


		//Описание
		$tags  = tags_echo($row['tags']);
		
		/////////////////////////////////////////////////////////
		//Пользователи
		/////////////////////////////////////////////////////////
		$user = get_user_info($row['id_user']);
		//ID пользователя
		$id_user = $user['id'];
		//Имя пользователя
		$user_name = $user['name'];
		//Класс пользователя
		$user_class = $user['class'];
		
		//Раздают
		$seeders  = number_format($row['seeders']);
		
		//Качают
		$leechers  = number_format($row['leechers']);
		
		require 'templates/'.$config['template'].'/blocks/block.releases.news.php';
	}
	
	echo '</table>';
	
	// end_frame();
	
	
}

?>					