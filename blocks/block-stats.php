<?
/*
===================================================================
LiteTracker Source
===================================================================
by jenaDI
-------------------------------------------------------------------
Назначение: Статистика трекера
===================================================================
*/

global $db , $memcache , $config, $language;
if (($stats = $memcache->get('stats')))
{
	//Всего торрентов
	$torrents = $stats["torrents"] ?? 0;
	//Ратио трекера
	$ratio = $stats["ratio"] ?? 0;
	//Пиры
	$peers = $stats["peers"] ?? 0;
	//Сидеры
	$seeders = $stats["seeders"] ?? 0;
	$seeders_guest = $stats["seeders_guest"] ?? 0;
	//Личеры
	$leechers = $stats["leechers"] ?? 0;
	//Мультитрекерные личеры/сидеры
	$r_leechers = $stats["r_leechers"] ?? 0;
	$r_seeders = $stats["r_seeders"] ?? 0;
	
	//Скачали
	$completed = $stats['completed'] ?? 0;
	// $completed_guest = $stats['completed_guest'];
	
	//Общий размер раздач
	$size = $stats['size'] ?? mksize(0);
	//Всего зарегистрировано
	$registered = $stats['registered'] ?? 0;
	
	//За сегодня
	$registered_day = $stats['registered_day'] ?? 0;
	
	//Не подтвержренных
	$unverified = $stats['unverified'] ?? 0;
	//Предупрежденных
	$warned_users = $stats['warned_users'] ?? 0;
	//Отключенных
	$disabled = $stats['disabled'] ?? 0;
	//Аплоадеров
	$uploaders = $stats['uploaders'] ?? 0;
	//VIP
	$vip = $stats['vip'] ?? 0;
}
else
{
		   
	//Торренты
	$torrents = $db->super_query("SELECT COUNT(*) AS count FROM torrents");
	$torrents = number_format($torrents['count']);
	
	
	//Сидеров/Личеров
	$peers1 = $db->super_query("SELECT SUM(seeders) AS seeders , SUM(leechers) AS leechers FROM trackers");
	

	$seeders = number_format($peers1['seeders']);
	
	
	//Гости - сидеры
	$seeders_guest = $db->super_query("SELECT  COUNT(*) AS count FROM peers WHERE userid = '0' AND seeder = '1'");
	$seeders_guest = $seeders_guest['count'];
	
	$leechers = number_format($peers1['leechers']);
	
	
	//Подключения
	$peers = $db->super_query("SELECT COUNT(userid) AS c FROM peers");
	$peers = $peers['c'];
	
	//Размер
	$size = $db->super_query("SELECT SUM(size) AS count FROM torrents");
	$size = mksize($size['count']);
	
	//Пользователи
	$registered = $db->super_query("SELECT COUNT(*) AS count FROM users");
	$registered = number_format($registered['count']);
	
	//За сегодня
	$registered_day = $db->super_query("SELECT COUNT(*) AS count FROM users WHERE ADDDATE(added, INTERVAL 1 DAY) > NOW()");
	$registered_day = number_format($registered_day['count']);
	
	//Скачали
	// $completed = $db->super_query("SELECT COUNT(*) AS c FROM peers WHERE finishedat <> '0'");
	// $completed = number_format($completed['c']);
	$completed = $db->super_query("SELECT SUM(completed) AS c FROM torrents ");
	$completed = number_format($completed['c']);
	
	
	//Скачали гостей
	// $completed_guest = $db->super_query("SELECT COUNT(*) AS c FROM peers WHERE finishedat <> '0' AND userid = 0");
	// $completed_guest = number_format($completed_guest['c']);
	
	
	//Заносим все в массив
	$stats = array(
		"torrents" => $torrents , 
		"seeders" => $seeders ,
		"seeders_guest" => $seeders_guest ,
		"leechers" => $leechers ,
		"completed" => $completed ,
		// "completed_guest" => $completed_guest ,
		"peers" => $peers ,
		"size" => $size ,
		"registered" => $registered ,
		"registered_day" => $registered_day ,
	);	
		
	$memcache->set('stats', $stats , 0, 15*60);
}		




//Подключаем шаблон
require 'templates/'.$config['template'].'/blocks/block.stats.php';
?>
