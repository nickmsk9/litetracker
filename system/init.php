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
ob_start();
ob_implicit_flush(false);

error_reporting(E_ALL ^ E_NOTICE);
ini_set('display_errors', '0');
ini_set('html_errors', '0');
ini_set('error_reporting', (string)(E_ALL ^ E_NOTICE));


define ( 'CMS', true );


//Функция определения времени(с 1970 г.)
function timer() {
	list($usec, $sec) = explode(" ", microtime());
	return ((float)$usec + (float)$sec);
}
$timer['a'] = timer();

/*
===================================================================

Подключение главных файлов

===================================================================
*/
//Подключаем конфигурационные файл
require __DIR__ . '/config/config.php';
require __DIR__ . '/config/config.version.php';
require __DIR__ . '/config/config.mysql.php';



//Подключаем главный функционал
require __DIR__ . '/functions/functions.php';
require __DIR__ . '/functions/functions.http.php';
require __DIR__ . '/functions/functions.upload.php';
require __DIR__ . '/functions/functions.metadata.php';

// Канонические helpers/services
require_once __DIR__ . '/../app/helpers/FormattingHelper.php';
require_once __DIR__ . '/../app/helpers/TorrentDescriptionHelper.php';
require_once __DIR__ . '/../app/helpers/UploadAssetHelper.php';
require_once __DIR__ . '/../app/helpers/UserHelper.php';
require_once __DIR__ . '/../app/Support/CacheKeys.php';
require_once __DIR__ . '/../app/Support/CacheInvalidation.php';
require_once __DIR__ . '/../app/Services/TorrentMetadataService.php';
require_once __DIR__ . '/../app/Services/TorrentDescriptionService.php';
require_once __DIR__ . '/../app/Services/CommentService.php';



//Функции для тегов
require __DIR__ . '/functions/functions.tags.php';
//Функции для редактора WYSIWYG
require __DIR__ . '/functions/functions.textbb.php';

//htmLawed 1.1.9.4
require __DIR__ . '/functions/functions.htmLawed.php';

//Подключаем класс ipcheck
require __DIR__ . '/classes/class.ipcheck.php';

//Функционал комментирования
require __DIR__ . '/functions/functions.comments.php';

//Функционал уведомлений
require __DIR__ . '/functions/functions.notifications.php';

//Журнал действий модераторов
require __DIR__ . '/functions/functions.moderation_log.php';

//Статусы и модерация раздач
require __DIR__ . '/functions/functions.torrent_status.php';

//CAPTCHA
require __DIR__ . '/functions/functions.recaptchalib.php';

//Подключаем класс db
require __DIR__ . '/classes/class.db.php';

//Поключаем класс отправки почты
require __DIR__ . '/classes/class.phpmailer.php';

//Поключаем класс ЧПУ
require __DIR__ . '/classes/class.rewrite.php';




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
if (!empty($config['mysql_timezone_offset'])) {
	$db->pquery("SET time_zone = ?", 's', [$config['mysql_timezone_offset']], 0);
}

//Запускаем мод ЧПУ
$rewrite = new rewrite;

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


//Подключаем языковую систему
if(!empty($_COOKIE['language'])) {
	$language = $_COOKIE['language'];
} else {
	$language = $config['lang'];
}


if(!is_language($language) ) {
	die('Language system error. Please clear cookie');
}

require $_SERVER['DOCUMENT_ROOT'].'/languages/'.$language.'/site.php';

//Отправка заголовков
header("Content-Type: text/html; charset=".$language['charset']."");


//Определяем пользователя
user_check();


//Бан по IP - адресу
if(!$PRIV['ip_util']) {
	$ip = ip2long_db(getip()); //IP адрес

	//Бан по IP - адресу
	$ban_resource = lt_cache_get(lt_cache_key_ip_ban($ip), lt_cache_key_sys_ns());
	if ($ban_resource === false) {
		$sql = $db->query("SELECT * FROM bans WHERE '".$ip."'  >= first AND '".$ip."' <= last");
		$ban_resource = $db->get_row($sql);
		lt_cache_set(lt_cache_key_ip_ban($ip), $ban_resource, 1000, lt_cache_key_sys_ns());
	}

	if($ban_resource) {
		die('Please note, your IP ('.getip().') has been banned '.convent_date($ban_resource['date']).'');
	}
}



//Определяем passkey для пользователя
if($USER && strlen($USER['passkey']) != 32) {
	$USER['passkey'] = md5($USER['name'].get_date_time().$USER['password']);
	$sql = $db->query('UPDATE users SET passkey="'.$USER['passkey'].'" WHERE id="'.$USER['id'].'"');
	$db->free($sql);
	lt_cache_invalidate_user($USER['id']);
}


if($USER) {
	//Если пользователь забанен , делаем выход
	if($USER['banned']) {
		logout_cookie();
		lt_cache_invalidate_user($USER['id']);
	}
}
?>
