<?php
/*
===================================================================
LiteTracker Source
===================================================================
by jenaDI
-------------------------------------------------------------------
Назначение: Главная файл (для анонсера)
===================================================================
*/

require_once __DIR__ . '/bootstrap/php_compat.php';

@session_start ();
@ob_start ();
@ob_implicit_flush ( 0 );

@error_reporting ( E_ALL ^ E_NOTICE );
@ini_set ( 'display_errors', true );
@ini_set ( 'html_errors', false );
@ini_set ( 'error_reporting', E_ALL ^ E_NOTICE );


define ( 'CMS', true );


/*
===================================================================

Подключение главных файлов

===================================================================
*/
//Подключаем конфигурационный файл
require __DIR__ . '/config/config.php';
require __DIR__ . '/config/config.mysql.php';

//Подключаем главный функционал
require __DIR__ . '/functions/functions.announce.php';

//Функции для работы с announce
require __DIR__ . '/functions/functions.benc.php';
		

//Подключаем класс db
require __DIR__ . '/classes/class.db.php';

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


//Подключаем язык
$language = $config['lang'];
require $_SERVER['DOCUMENT_ROOT'].'/languages/'.$language.'/site.php';
?>
