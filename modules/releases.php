<?
/*
===================================================================
LiteTracker Source
===================================================================
by jenaDI
-------------------------------------------------------------------
Назначение: Релизы
===================================================================
*/

$act = (string) ($_GET['act'] ?? '');
$id_category = isset($_GET['id_category']) ? (int) $_GET['id_category'] : 0;
$type = (string) ($_GET['type'] ?? 'name');
$search = trim((string) ($_GET['search'] ?? ''));
$sort = trim((string) ($_GET['sort'] ?? ''));
$id_user = isset($_GET['id_user']) ? (int) $_GET['id_user'] : 0;
$releases_news = !empty($_GET['releases_news']);
$releases_to_day = !empty($_GET['releases_to_day']);
$page = isset($_GET['page']) ? (int) $_GET['page'] : 0;

//////////////////////////////////////////////////////////////////
//Вывод Категорий
//////////////////////////////////////////////////////////////////
if($act == 'all' && $config['search_forum']) {
	$cache_categories = categories_array();
	?>
	<link href="public/css/torrenttable.css" rel="StyleSheet" type="text/css">
	<table width="95%" class="tt" align="center">
            <tr><td class="tt" style="width:45px;" align="center"></td>
                <td class="tt"><font color=white>Название</font></td>
                <td class="tt"><font color=white>Статистика</font></td>
				</td></tr>
	<?
	foreach($cache_categories AS $arr) {
		//Название категории
		$name = htmlspecialchars($arr['name']);
		
		//Номер категории
		$id = $arr['id'];
		
		//Картинка
		$cat_image = $arr['image'];
		
		//Количество релизов
		$count  = number_format($arr['count']);
		
		//Общий размер
		$size  = mksize($arr['size']);
		
		//Подключаем шаблон
		require 'templates/'.$config['template'].'/tpl.releases.categories.php';
	}	
	echo '</table>';
	
}


//////////////////////////////////////////////////////////////////
//Вывод релизов
//////////////////////////////////////////////////////////////////
elseif($act == 'releases' || $id_category || $search !== '' || !$config['search_forum'] ) {

	//Массив для сортировки результата
	$where = array();
	//Массив для определение GET - параметров
	$get = array();

	// $where[] = 'tr.seeders <> 0';
	
	//Показываем только релизы, у которых есть торрент-файл и обложка
	// $where[] = 'image <> "" AND infohash <> ""'; 
	
	
	if(!$PRIV['details_banned_view'] ) {
		$where[] = 't.banned <> 1';
	}
	
	//Категория
	if(!empty($id_category) && is_numeric($id_category) && $id_category != 0) {
		$where[] = 't.id_category = '.$db->safesql($id_category);
		$get[] = 'id_category='.$id_category;
	}

	if(!empty($search) ) {
		$get[] = 'search='.$search;
		if($type == 'tags') {
			$get[] = 'type=tags';
			$where[] = "t.tags LIKE '%" . sqlwildcardesc($search) . "%'";
		}
		else {
			$get[] = 'type=name';
			$where[] = "t.name LIKE '%" . sqlwildcardesc($search) . "%'";		
		}
	}

	//Сортировка
	if($sort == 'desc' || empty($sort) ) {
		$get[] = 'sort=desc';
		$sort = 'ORDER BY t.added DESC' ;
	}
	elseif($sort == 'asc') {
		$get[] = 'sort=asc';
		$sort = 'ORDER BY t.added ASC';
	}
	elseif($sort == 'rand') {
		$get[] = 'sort=rand';
		$sort = 'ORDER BY RAND() ';
	}else {
		$get[] = 'sort=desc';
		$sort = 'ORDER BY t.added DESC ';
	}


		
	//Релизы пользователя
	if($id_user) {
		$where[] = 't.id_user='.$id_user;
		$get[] = 'id_user='.$id_user;
	}
		
	//Новинки месяца
	if($releases_news) {
		$where[] = 't.news=1 AND ADDDATE(t.added, INTERVAL '.$config['releases_news'].' DAY) > NOW()';
		$get[] = 'releases_news=1';
	}	
	
	//Релизы сегодня
	if($releases_to_day) {
		$where[] = 'ADDDATE(t.added, INTERVAL 1 DAY) > NOW()';
		$get[] = 'releases_to_day=1';	
	}
	

		
	//Постраничная навигация
	$db->query("SELECT t.* , SUM(tr.seeders) 
							FROM torrents  AS t
							LEFT JOIN trackers AS tr ON  tr.torrent = t.id				
							".(count($where) ? 'WHERE '.implode(' AND ' , $where) : "")."
							GROUP BY t.id
							" , 1);
							
	$count_torrent = $db->num_rows();
	list($pagertop, $pagerbottom, $limit) = pager('20', $count_torrent, 'browse.php?act=releases&'.(count($get) ? implode('&' ,$get).'&' : '') );
		
	//Мониторинг поиска
	$conf_str_lenght = 5; //Количество символов , которые будет учитывать Мониторинг

	//Добавляем поисковую фразу в базу данных
	if(!empty($search) && substr_count((string) ($_SERVER['QUERY_STRING'] ?? ''), 'page') == 0 && strlen($search) >= $conf_str_lenght && $USER && ($type == '' || $type == 'name')) {
		//Проверяем существует ли данный запрос в базе
		//Если существует , обновляем информацию
		$check_query = $db->super_query("SELECT COUNT(*) AS count FROM search_query WHERE id_user=".($USER ? $USER['id'] : '-1')."  AND  text LIKE '%" . sqlwildcardesc($search). "%'");

		if($check_query['count'] > 0) 
			$db->query("UPDATE search_query SET last_date = NOW() , num_views = (num_views + 1) , num_torrents = ".$count_torrent." WHERE id_user=".($USER ? $USER['id'] : '-1')."  AND  text LIKE '%" . sqlwildcardesc($search). "%'");
		else 
			$db->query("INSERT INTO search_query (text , id_user , last_date , num_torrents) VALUES ('".sqlwildcardesc($search)."' , ".($USER ? $USER['id'] : '-1')." , NOW() , ".$count_torrent.") ");	
	}	
		
	//Запрос к таблице torrents
	$sql  = $db->query("SELECT t.* , SUM(tr.seeders) AS seeders , SUM(tr.leechers) AS leechers , t.multi,
				IF((SELECT SUM(seeders) FROM trackers WHERE torrent = t.id AND tracker='localhost' GROUP BY tracker) > 0 , true , false) AS local_seeders , 
				
				IF(ADDDATE(t.added, INTERVAL ".$config['releases_news']." DAY) > NOW() AND t.news = '1' , 1 , 0) AS new_release
				FROM torrents AS t			
				INNER JOIN trackers AS tr ON  tr.torrent = t.id				
				".(sizeof($where) ? 'WHERE '.implode(' AND ' , $where) : "")."
				GROUP BY t.id 
				".$sort."
				".$limit."
				");	
	

	if($db->num_rows() > 0) {
		
			

		
		//Дополнительные файлы js и моды
		
		// echo '<script src="public/js/overlib.js"></script>';  	
		echo '<script type="text/javascript" src="public/js/wz_tooltip.js"></script>';  	
		echo $pagertop;

		?>
		<link href="public/css/torrenttable.css" rel="StyleSheet" type="text/css">
		<?=($PRIV['edit_release'] ? '<form action="check_release.php" method="post">' : '');?>
		<table width="95%" class="tt" align="center">
            <tr><td class="tt" style="width:45px;" align="center"><font  color=white>Тип</font></td>
                <td class="tt"><font color=white>Имя</font></td>
				<td class="tt" width="60" align="center"><font  color=white>Размер</font></td>
				<td class="tt" width="30" align="center"><font  color=white>Сидеры</font></td>
				<td class="tt" width="30" align="center"><font  color=white>Личеры</font></td>
				<td class="tt" width="30" align="center"><font  color=white>Файлов</font></td>
				<td class="tt" width="30" align="center"><font  color=white>Скачан</font></td>
				<?=($PRIV['edit_release'] ? '<td class="tt" width="30" align="center"><font size=$size color=white><input type="submit" value="'.$language['releases_18'].'"></td>' : '');?>
			
			
		<?
		$row = 0;
		//Вывод релизов
		while($arr = $db->get_row($sql) ) {
			$class = ($row++ % 2) ? 'row1' : 'row2'; // $row is zero-based 
			//Вывод релиза
			require 'modules/releases.arr.php';
			
			//Увеличиваем счетчик
			$i++;
			
		}
		echo '</table></div>';
		echo ($PRIV['edit_release'] ? '</form>'  : '');
		
				
		//Вывод видео и картинок
		if($page === 0) {
				//Видео
				if(strlen($search) >= $config['search_video_lenght'] && $config['search_video']) {
					$video = $db->query("SELECT id , video_vkontakte , COUNT(*) AS count FROM torrents WHERE name LIKE '%" . sqlwildcardesc($search) . "%' AND video_vkontakte != '' GROUP BY id DESC LIMIT 1 ");
					if($db->num_rows($video) > 0) {
						$video = $db->get_row($video);
						
					
						if(!empty($video['video_vkontakte']) ) {
							begin_frame($language['details_17']);
							echo '<iframe src="'.htmlspecialchars($video['video_vkontakte']).'" width="100%" height="200" frameborder="0"></iframe>';
							echo '<a href="video_vkontakte.php?id='.$video['id'].'"  class="proleft">'.$language['video_vkontakte_1'].'</a>';
							end_frame();
						}	
						
					}
					
				}
			

				//Картинки
				if(strlen($search) >= $config['search_image_lenght'] && $config['search_image']) {
					$image_sql = mysql_query("SELECT id , image , COUNT(*) AS c , name FROM torrents WHERE name LIKE '%" . sqlwildcardesc($search) . "%' AND image != '' GROUP BY id DESC LIMIT 5 ");
					if(mysql_num_rows($image_sql) > 0) {
						begin_frame($language['image_1']);
						echo '<center>';
						
						while($row = mysql_fetch_array($image_sql) ) {
							echo '<a href="details.php?id='.$row['id'].'"><img src="public/downloads/images/'.$row['image'].'" width="100" height="100" title="'.htmlspecialchars($row['name']).'"></a>&nbsp';
						}	
						
						echo '</center>';
						end_frame();
						
					}
						
					
				}
		}

		echo $pagertop;
	} else {
		msg($language['default_8'], $language['releases_7']);
	}		
}
?>
