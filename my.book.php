<?
/*
===================================================================
LiteTracker Source
===================================================================
by jenaDI
-------------------------------------------------------------------
Назначение: Мои закладки
===================================================================
*/

//Подключаем главный системный файл
require 'system/init.php';

//Проверяем пользователя
is_login();

//////////////////////////////////////////////////////////////////////////////
//Массовое удаление
//////////////////////////////////////////////////////////////////////////////
if($_GET['act'] == 'check_delete') {
	//Массив с данными
	$array = $_POST['check'];
	if(!count($array) || !is_array($array)) {
		err($language['default_1'] , $language['books_5'] , 1);
	}

	//Обрабатываем данные
	$ids = array();
	foreach($array AS $id) {
		$ids[] = (int)$id;
	}	
	
	$i = 0;
	foreach($ids AS $id) {
		$db->query("SELECT * FROM torrents WHERE id=".$id);
		if(!$db->num_rows() ) {
			continue;
		}
		$db->query("SELECT * FROM books WHERE id_torrent=".$id);
		if(!$db->num_rows() ) {
			continue;
		}
		
		$db->query("DELETE FROM books WHERE id_torrent=".$id);
		$i++;
	}
	
	head('Удаление закладок');
	begin_frame('Удаление закладок');
	msg(sprintf($language['books_6'] , $i , count($ids)) , '<a href="javascript:history.go(-1)">'.$language['books_7'].'</a>');
	end_frame();
	foot();
	die();
}
//////////////////////////////////////////////////////////////////////////////
//Добавление закладки
//////////////////////////////////////////////////////////////////////////////
if($_GET['act'] == 'add') {
	$id = (int)$_GET['id'];
	//Проверяем торрент
	$count_t = $db->super_query("SELECT COUNT(*) AS count FROM torrents WHERE id=".$id."");
	if(!$count_t['count']) {
		err($language['default_1'] , $language['download_1'] , 1);
	}	
	
	//Проверяем закладку
	$count_b = $db->super_query("SELECT COUNT(*) AS count FROM books WHERE id_torrent=".$id." AND id_user=".$USER['id']."");
	if($count_b['count']) {
		err($language['default_1'] , $language['books_1'] , 1);
	}	
	
	//Добавляем закладку
	$db->query("INSERT INTO books(id_torrent , id_user , date ) VALUES (".$id." , ".$USER['id']." , NOW() )");
	header("Location:details.php?id=".$id."");
	die();
}



//////////////////////////////////////////////////////////////////////////////
//Удаление закладки
//////////////////////////////////////////////////////////////////////////////
if($_GET['act'] == 'delete') {
	$id = (int)$_GET['id'];
	//Проверяем торрент
	$count_t = $db->super_query("SELECT COUNT(*) AS count FROM torrents WHERE id=".$id."");
	if(!$count_t['count']) {
		err($language['default_1'] , $language['download_1'] , 1);
	}	
	
	//Проверяем закладку
	$count_b = $db->super_query("SELECT COUNT(*) AS count FROM books WHERE id_torrent=".$id." AND id_user=".$USER['id']."");
	if(!$count_b['count']) {
		err($language['default_1'] , $language['books_2'] , 1);
	}	
	
	//Добавляем закладку
	$db->query("DELETE FROM  books WHERE id_torrent=".$id." AND id_user=".$USER['id']);
	header("Location:details.php?id=".$id."");
	die();
}


//////////////////////////////////////////////////////////////////////////////
//Вывод закладок
//////////////////////////////////////////////////////////////////////////////

//Заголовок
head($language['books_3']);


//Выборка
$where = array();
$where[] = 't.id_user='.$USER['id'];



//Постраничная навигация
$db->query("SELECT t.* 
					FROM books AS b 
					LEFT JOIN torrents  AS t ON b.id_torrent = t.id
					LEFT JOIN trackers AS tr ON  tr.torrent = t.id				
					WHERE b.id_user=".$USER['id']."
					GROUP BY t.id 
					ORDER BY t.added DESC
					" , 1);
						
$count_torrent = $db->num_rows();
list($pagertop, $pagerbottom, $limit) = pager('10', $count_torrent, 'my.book.php?');


//Запрос к таблице torrents
$sql = $db->query("SELECT t.* , SUM(tr.seeders) AS seeders , SUM(tr.leechers) AS leechers , t.multi,
			IF((SELECT SUM(seeders) FROM trackers WHERE torrent = t.id AND tracker='localhost' GROUP BY tracker) > 0 , true , false) AS local_seeders
			FROM books AS b 
			LEFT JOIN torrents  AS t ON b.id_torrent = t.id		
			LEFT JOIN trackers AS tr ON  tr.torrent = t.id				
			WHERE b.id_user=".$USER['id']."
			GROUP BY t.id 
			ORDER BY t.added DESC
			".$limit."
			");	
			

					
if($db->num_rows($sql) > 0) {
		
	//ДОполнительные файлы js и моды
	echo '<script src="js/overlib.js"></script>';   

	echo $pagertop; 	
	
	echo '<script type="text/javascript" src="public/js/wz_tooltip.js"></script>';  
		?>
		<link href="public/css/torrenttable.css" rel="StyleSheet" type="text/css">
		<form action="my.book.php?act=check_delete" method="post">
		<table width="95%" class="tt" align="center">
            <tr><td class="tt" style="width:45px;" align="center"><font  color=white>Тип</font></td>
                <td class="tt"><font color=white>Имя</font></td>
				<td class="tt" width="60" align="center"><font  color=white>Размер</font></td>
				<td class="tt" width="30" align="center"><font  color=white>Сидеры</font></td>
				<td class="tt" width="30" align="center"><font  color=white>Личеры</font></td>
				<td class="tt" width="30" align="center"><font  color=white>Файлов</font></td>
				<td class="tt" width="30" align="center"><font  color=white>Скачан</font></td>
				<td class="tt" width="30" align="center"><font  color=white><input type="submit" value="<?=$language['releases_19'];?>"></font></td>
			
			</tr>
		<?
	while($arr = $db->get_row($sql) ) {
		require 'modules/releases.arr.php';
	}
	echo '</table></form>';
	echo $pagertop;
} else {
	msg($language['default_8'] , $language['books_4']);
}					


//Подвал
foot();


?>