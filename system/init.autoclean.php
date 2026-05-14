<?php
/*
===================================================================
LiteTracker Source
===================================================================
by Nick
-------------------------------------------------------------------
Назначение: Главная файл
===================================================================
*/

require_once __DIR__ . '/bootstrap/php_compat.php';

lt_session_bootstrap();
ob_start ();
ob_implicit_flush ( 0 );

error_reporting ( E_ALL ^ E_NOTICE );
ini_set ( 'display_errors', false );
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
require __DIR__ . '/config/config.mysql.php';




//Подключаем главный функционал
require __DIR__ . '/functions/functions.php';
require __DIR__ . '/functions/functions.http.php';

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
require_once __DIR__ . '/bootstrap/cache.php';
$memcached = lt_cache_bind_globals();


//Cron system
$CRON = lt_cache_get(lt_cache_key_cron(), lt_cache_key_sys_ns());
if ($CRON === false) {
	$sql = $db->query("SELECT * FROM cron");
	$CRON = array();
	while($cron  = $db->get_row($sql)) {
		$CRON[$cron['cron_name']] = $cron['cron_value'];
	}
	lt_cache_set(lt_cache_key_cron(), $CRON, 15*60, lt_cache_key_sys_ns());
}




// Отправка заголовков
// header("Content-Type: text/html; charset=".$language['charset']."");
?>
