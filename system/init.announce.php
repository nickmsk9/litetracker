<?php
/*
===================================================================
LiteTracker Source
===================================================================
by Nick
-------------------------------------------------------------------
Назначение: Главная файл (для анонсера)
===================================================================
*/

require_once __DIR__ . '/bootstrap/php_compat.php';

lt_session_bootstrap();
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
require_once __DIR__ . '/bootstrap/cache.php';
$memcached = lt_cache_bind_globals();


//Подключаем язык
$language = $config['lang'];
require $_SERVER['DOCUMENT_ROOT'].'/languages/'.$language.'/site.php';
?>
