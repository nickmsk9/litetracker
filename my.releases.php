<?
/*
===================================================================
LiteTracker Source
===================================================================
by Nick
-------------------------------------------------------------------
Назначение: Мои торренты
===================================================================
*/

//Подключаем главный системный файл
require 'system/init.php';

//Проверяем пользователя
is_login();

//Заголовок
head($language['my_releases_1']);



//////////////////////////////////////////////////////////////////////////////
//Моя статистика
//////////////////////////////////////////////////////////////////////////////


//////////////////////////////////////////////////////////////////////////////
//Мои торренты
//////////////////////////////////////////////////////////////////////////////


//Выборка
$where = array();
$where[] = 't.id_user='.$USER['id'];



//Постраничная навигация
$db->query("SELECT t.* , SUM(tr.seeders)
						FROM torrents  AS t
						LEFT JOIN trackers AS tr ON  tr.torrent = t.id
						".(sizeof($where) ? 'WHERE '.implode(' AND ' , $where) : "")."
						GROUP BY t.id
						" , 1);

$count_torrent = $db->num_rows();
list($pagertop, $pagerbottom, $limit) = pager('10', $count_torrent, 'my.releases.php?');


//Запрос к таблице torrents
$sql  = $db->query("SELECT t.* , COALESCE(SUM(tr.seeders), 0) AS seeders , COALESCE(SUM(tr.leechers), 0) AS leechers , COALESCE(SUM(CASE WHEN tr.tracker<>'localhost' THEN 1 ELSE 0 END), 0) AS external_tracker_count , u.id AS id_user , u.name AS user_name , u.class AS user_class  , t.multi,
			IF((SELECT SUM(seeders) FROM trackers WHERE torrent = t.id AND tracker='localhost' GROUP BY tracker) > 0 , true , false) AS local_seeders
			FROM torrents AS t
			LEFT JOIN users AS u ON  u.id = t.id_user
			LEFT JOIN trackers AS tr ON  tr.torrent = t.id
			".(sizeof($where) ? 'WHERE '.implode(' AND ' , $where) : "")."
			GROUP BY t.id
			ORDER BY t.added DESC
			".$limit."
			");



if($db->num_rows($sql) > 0) {



	echo $pagertop;
		echo '<script type="text/javascript" src="public/js/wz_tooltip.js"></script>';
		?>
		<link href="public/css/torrenttable.css" rel="StyleSheet" type="text/css">
		<?php if ($PRIV['edit_release']) { ?>
		<form action="check_release.php" method="post">
			<?=lt_csrf_input('check_release');?>
		<?php } ?>
		<div class="lt-table-scroll">
		<table class="lt-table lt-table-compact lt-table-actions lt-releases-table tt table-clean">
			<thead>
				<tr>
					<th class="tt" align="center">Тип</th>
					<th class="tt">Имя</th>
					<th class="tt" align="center">Размер</th>
					<th class="tt" align="center">Сидеры</th>
					<th class="tt" align="center">Личеры</th>
					<th class="tt" align="center">Файлов</th>
					<th class="tt" align="center">Скачан</th>
					<?=($PRIV['edit_release'] ? '<th class="tt" align="center"><input class="lt-btn lt-btn-secondary" type="submit" value="'.$language['releases_18'].'"></th>' : '');?>
				</tr>
			</thead>
			<tbody>
		<?
	while($arr = $db->get_row($sql) ) {

		require 'modules/releases.arr.php';
	}

	echo '</tbody></table></div>';
	echo ($PRIV['edit_release'] ? '</form>'  : '');
	echo $pagertop;
} else {
	msg($language['default_8'] , $language['my_releases_2']);
}

//Подвал
foot();


?>
