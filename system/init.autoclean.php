<?php
/*
===================================================================
LiteTracker Source
===================================================================
by jenaDI
-------------------------------------------------------------------
Назначение: Главная файл
===================================================================
*/

require_once __DIR__ . '/bootstrap/php_compat.php';

session_start ();
ob_start ();
ob_implicit_flush ( 0 );

error_reporting ( E_ALL ^ E_NOTICE );
ini_set ( 'display_errors', true );
ini_set ( 'html_errors', false );
ini_set ( 'error_reporting', E_ALL ^ E_NOTICE );


define ( 'CMS', true );


/*
===================================================================

Подключение главных файлов

===================================================================
*/
//Подключаем конфигурационные файл
require __DIR__ . '/config/config.php';
require __DIR__ . '/config/config.vkontakte.php';
require __DIR__ . '/config/config.mysql.php';




//Подключаем главный функционал
require __DIR__ . '/functions/functions.php';

//Подключаем класс db
require __DIR__ . '/classes/class.db.php';


//Подключаем класс отправки почты
require __DIR__ . '/classes/class.phpmailer.php';



/*
===================================================================

Запуск

===================================================================
*/


//Сжатие 
gzip();


//Запускаем подключение  к mysql
$db = new db;
$db->connect($mysql['user'] , $mysql['password'] , $mysql['db'] ,  $mysql['host']);



//Запуск парсера шаблонов
// $tpl = new template ( );
// $tpl->dir = 'templates/' . $config['template'];
// define ( 'TEMPLATE_DIR', $tpl->dir );



//Запускаем memcached/filecache
require_once __DIR__ . '/classes/class.memcached.php';

if(class_exists('Memcached', false)) {
	$memcacheHost = getenv('LITETRACKER_MEMCACHED_HOST');
	if (!$memcacheHost) {
		$memcacheHost = gethostbyname('memcached') !== 'memcached' ? 'memcached' : '127.0.0.1';
	}

	$memcachePort = (int) (getenv('LITETRACKER_MEMCACHED_PORT') ?: ($memcacheHost === '127.0.0.1' ? 11213 : 11211));

	$memcache = new MemcachedCache;
	$memcache->connect($memcacheHost, $memcachePort);
} else {
	require __DIR__ . '/config/config.filecache.php';
	require __DIR__ . '/classes/class.filecache.php';
	$memcache = new Filecache;
}


//Cron system
if (false === ($CRON = $memcache->get('CRON'))) {
	$sql = $db->query("SELECT * FROM cron");	
	$CRON = array();
	while($cron  = $db->get_row($sql)) {
		$CRON[$cron['cron_name']] = $cron['cron_value'];
	}
	$memcache->set('CRON', $CRON  , 0, 15*60);		
}




// Отправка заголовков
// header("Content-Type: text/html; charset=".$language['charset']."");
?>
