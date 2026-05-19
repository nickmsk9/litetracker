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
ob_start();
ob_implicit_flush(false);

error_reporting(E_ALL ^ E_NOTICE);
ini_set('display_errors', '0');
ini_set('html_errors', '0');
ini_set('error_reporting', (string)(E_ALL ^ E_NOTICE));


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
$db->connect($mysql['user'] , $mysql['password'] , $mysql['db'] ,  $mysql['host'], 1, ($mysql['port'] ?? 3306), ($mysql['connect_timeout'] ?? 5));
if (!empty($mysql['timezone'])) {
	$db->pquery("SET time_zone = ?", 's', [$mysql['timezone']], 0);
}


//Запускаем memcached/filecache
require_once __DIR__ . '/bootstrap/cache.php';
$memcached = lt_cache_bind_globals();


//Подключаем язык
$language = $config['lang'];
$languageFile = lt_languages_path($language.'/site.php');
if ($languageFile === '' || !file_exists($languageFile)) {
	die('Language system error. Missing language file');
}
require $languageFile;
?>
