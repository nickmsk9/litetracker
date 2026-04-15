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

@session_start ();
@ob_start ();
@ob_implicit_flush ( 0 );

@error_reporting ( E_ALL ^ E_NOTICE );
@ini_set ( 'display_errors', true );
@ini_set ( 'html_errors', false );
@ini_set ( 'error_reporting', E_ALL ^ E_NOTICE );


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
require __DIR__ . '/config/config.vkontakte.php';
require __DIR__ . '/config/config.mysql.php';



//Подключаем главный функционал
require __DIR__ . '/functions/functions.php';
require __DIR__ . '/functions/functions.vkontakte.php';
require __DIR__ . '/functions/functions.blocks.php';
require __DIR__ . '/functions/functions.upload.php';
require __DIR__ . '/functions/functions.polls.php';



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
		
//reCaptcha 
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

//Запускаем мод ЧПУ 
$rewrite = new rewrite;

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
	if (false === ($ban_resource = $memcache->get('ip_bans_'.$ip))) {
		$sql = $db->query("SELECT * FROM bans WHERE '".$ip."'  >= first AND '".$ip."' <= last");			
		$ban_resource = $db->get_row($sql);
		$memcache->set('ip_bans_'.$ip, $ban_resource  , 0, 1000);		
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
	$memcache->delete('user_'.$USER['id']);
}


//Определение плозого рейтинга и авто-бан аккаунта
if($USER) {
	$certain_time = get_certain_time($USER['added']);

	if( get_ratio($USER['uploaded'] , $USER['downloaded']  ) < $config['bad_rating'] && $PRIV['bad_rating']) {
		
		if(!$USER['bad_rating']) {
			$sql = $db->query("UPDATE users SET bad_rating='1' WHERE id=".$USER['id']);
			$db->free($sql);
			$memcache->delete('user_'.$USER['id']);			
		}
		
		//Баним , если прошло время
		if($certain_time['days'] >= $config['days_rating'] ) {
			$sql = $db->query("UPDATE users SET banned='1' WHERE id=".$USER['id']);
			$db->free($sql);
			$memcache->delete('user_'.$USER['id']);	
		}

	}
		

	//Если рейтинг изменился в лучшую сторону , удаляем пользователя из списка плохих пользователей
	if($USER['bad_rating'] && get_ratio($USER['uploaded'] , $USER['downloaded'] ) >= $config['bad_rating']  && $PRIV['bad_rating'] ) {
		$sql = $db->query("UPDATE users SET bad_rating='0' WHERE id=".$USER['id']);
		$db->free($sql);
		$memcache->delete('user_'.$USER['id']);
	}

	//Если пользователь забанен , делаем выход
	if($USER['banned']) {
		logout_cookie();
		$memcache->delete('user_'.$USER['id']);
	}
}
?>
