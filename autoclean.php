<?
#!/usr/bin/env php
/*
===================================================================
LiteTracker Source
===================================================================
by Nick
-------------------------------------------------------------------
Назначение: Autoclean system
===================================================================
*/

// header("Content-Type: image/gif");

//Подключаем главный системный файл
require 'system/init.autoclean.php';
//Функции для обновления
require 'system/functions/functions.benc.php';

$bonusColumn = (lt_column_exists('users', 'bonus') ? 'bonus' : 'voice');


//Autoclean system
if((time() - $CRON['autoclean_last']) < $CRON['autoclean_interval']) {
	die(base64_decode("R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="));
}


///////////////////////////////////////////////////////////////////
//Очистка пиров
///////////////////////////////////////////////////////////////////
$secs = 30*60; //Количество секунд , через которое пир считается неактивным
$date = $db->safesql(get_date_time(gmtime() - $secs));
$peers = $db->query("DELETE FROM peers  WHERE last_action < '".$date."'");


///////////////////////////////////////////////////////////////////
//Очистка пиров
///////////////////////////////////////////////////////////////////
$torrents = array();
$res = $db->query('SELECT torrent, seeder, SUM(1) AS c FROM peers GROUP BY torrent, seeder');
while ($row = $db->get_row($res)) {
	if ($row['seeder'])
		$key = 'seeders';
	else
		$key = 'leechers';
	$torrents[$row['torrent']][$key] = $row['c'];
}

$peerssql = $db->query("SELECT torrent FROM trackers WHERE tracker='localhost'");
while (list($id) = $db->get_array($peerssql) ) {
	$db->query("UPDATE trackers SET seeders = ".(int)$torrents[$id]['seeders'].", leechers = ".(int)$torrents[$id]['leechers'].", lastchecked = ".time()." WHERE torrent = ".$id." AND tracker='localhost'");
}

///////////////////////////////////////////////////////////////////
//Начисление бонусов
///////////////////////////////////////////////////////////////////
// Начисляем бонусы за активное присутствие на сайте за последний интервал очистки
$bonus_per_hour = (float) ($config['bonus_price'] ?? $config['voice_price'] ?? 0);
$bonus_per_cleanup = round($bonus_per_hour * ((int) $CRON['autoclean_interval'] / 3600), 2);
if ($bonus_per_cleanup > 0) {
	$active_from = $db->safesql(get_date_time(time() - (int) $CRON['autoclean_interval']));
	$active_users = $db->query("SELECT DISTINCT user_id FROM sessions WHERE user_id > 0 AND last_access >= '".$active_from."'");
	while ($active_user = $db->get_row($active_users)) {
		$db->query("UPDATE users SET {$bonusColumn} = ({$bonusColumn} + ".$bonus_per_cleanup.") WHERE id = ".(int) $active_user['user_id']);
		$memcached->delete('user_'.(int) $active_user['user_id']);
	}
}


///////////////////////////////////////////////////////////////////
//Удаление просроченный кодов "Забыли пароль?"
///////////////////////////////////////////////////////////////////
$secs = 15*24*(60*60); //Количество секунд , через которое код считается просроченным (15 дней)
$date = $db->safesql(get_date_time(gmtime() - $secs));
$sql = $db->query("SELECT * FROM forgot  WHERE date < '".$date."'");
while($arr = $db->get_row($sql) ) {
	$db->query("DELETE FROM forgot WHERE id = ".$arr['id']);

}

//Обновляем cron-запись
$db->query("UPDATE cron SET cron_value=".time()." WHERE cron_name='autoclean_last'");
$memcached->delete('CRON');
die(base64_decode("R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="));
?>
