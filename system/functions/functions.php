<?php
/*
===================================================================
LiteTracker Source
===================================================================
by jenaDI
-------------------------------------------------------------------
Назначение: Функции
===================================================================
*/

//Информация о пользователе
function get_user_info($id) {
	global $db , $memcached;

	//Если нету id
	if(!$id) {
		return false;
	}

	//Запрос к таблице users
	if (false === ($row = $memcached->get('user_'.$id)))
	{
		$sql = $db->query("SELECT * FROM users WHERE id = ".$id);
		$row  = $db->get_row($sql);
		$db->free($sql);
		$memcached->set('user_'.$id, $row  , 0, rand(1500 , 3000) );
	}

	return $row;
}

function lt_table_exists($tableName)
{
	global $db;
	static $cache = array();

	$tableName = preg_replace('~[^a-z0-9_]~i', '', (string) $tableName);
	if ($tableName === '') {
		return false;
	}

	if (array_key_exists($tableName, $cache)) {
		return $cache[$tableName];
	}

	$row = $db->super_query("SHOW TABLES LIKE '".$db->safesql($tableName)."'");
	$cache[$tableName] = !empty($row);

	return $cache[$tableName];
}

function lt_column_exists($tableName, $columnName)
{
	global $db;
	static $cache = array();

	$tableName = preg_replace('~[^a-z0-9_]~i', '', (string) $tableName);
	$columnName = preg_replace('~[^a-z0-9_]~i', '', (string) $columnName);

	if ($tableName === '' || $columnName === '') {
		return false;
	}

	$key = $tableName.'.'.$columnName;
	if (array_key_exists($key, $cache)) {
		return $cache[$key];
	}

	if (!lt_table_exists($tableName)) {
		$cache[$key] = false;
		return false;
	}

	$row = $db->super_query("SHOW COLUMNS FROM `".$tableName."` LIKE '".$db->safesql($columnName)."'");
	$cache[$key] = !empty($row['Field']);

	return $cache[$key];
}

function profile_public_mask()
{
	static $mask = null;

	if ($mask === null) {
		$mask = (int) hexdec(substr(md5('profile-mask|'.COOKIE_SALT), 0, 8));
	}

	return $mask;
}

function profile_public_id($userId)
{
	$userId = (int) $userId;
	if ($userId <= 0) {
		return '';
	}

	$maskedId = ($userId ^ profile_public_mask());
	if ($maskedId < 0) {
		$maskedId = $maskedId + 4294967296;
	}

	return sprintf('%08x', $maskedId).substr(md5('profile-public|'.$userId.'|'.COOKIE_SALT), 0, 16);
}

function profile_user_id_from_public($publicId)
{
	$publicId = strtolower(trim((string) $publicId));
	if (!preg_match('~^[a-f0-9]{24}$~', $publicId)) {
		return 0;
	}

	$maskedId = (int) hexdec(substr($publicId, 0, 8));
	$userId = ($maskedId ^ profile_public_mask());

	if ($userId <= 0) {
		return 0;
	}

	return (profile_public_id($userId) === $publicId ? $userId : 0);
}

function profile_href($user, $view = 'profile', $params = array())
{
	$userId = 0;

	if (is_array($user)) {
		$userId = (int) ($user['id'] ?? 0);
	} else {
		$userId = (int) $user;
	}

	if ($userId <= 0) {
		return 'profile.php';
	}

	$view = trim((string) $view);
	$allowedViews = array('profile', 'torrents', 'bonus');
	if (!in_array($view, $allowedViews, true)) {
		$view = 'profile';
	}

	$extraParams = (is_array($params) ? $params : array());
	$params = array('id' => $userId);

	if ($view !== 'profile') {
		$params['view'] = $view;
	}

	if ($extraParams) {
		$params = array_merge($params, $extraParams);
	}

	$query = http_build_query($params);

	return 'profile.php'.($query !== '' ? '?'.$query : '');
}

function user_is_online($userId, $thresholdMinutes = 15)
{
	global $db;
	static $cache = array();

	$userId = (int) $userId;
	$thresholdMinutes = max(1, (int) $thresholdMinutes);

	if ($userId <= 0) {
		return false;
	}

	$cacheKey = $userId.':'.$thresholdMinutes;
	if (array_key_exists($cacheKey, $cache)) {
		return $cache[$cacheKey];
	}

	$onlineFrom = $db->safesql(get_date_time(gmtime() - ($thresholdMinutes * 60)));
	$row = $db->super_query("SELECT user_id FROM sessions WHERE user_id = ".$userId." AND last_access >= '".$onlineFrom."' LIMIT 1");
	$cache[$cacheKey] = !empty($row['user_id']);

	return $cache[$cacheKey];
}

function user_blacklist_available()
{
	return lt_table_exists('users_blacklist');
}

function user_is_blacklisted($userId, $blockedUserId)
{
	global $db;
	static $cache = array();

	$userId = (int) $userId;
	$blockedUserId = (int) $blockedUserId;

	if ($userId <= 0 || $blockedUserId <= 0 || !user_blacklist_available()) {
		return false;
	}

	$cacheKey = $userId.':'.$blockedUserId;
	if (array_key_exists($cacheKey, $cache)) {
		return $cache[$cacheKey];
	}

	$row = $db->super_query("SELECT id FROM users_blacklist WHERE user_id = ".$userId." AND blocked_user_id = ".$blockedUserId." LIMIT 1");
	$cache[$cacheKey] = !empty($row['id']);

	return $cache[$cacheKey];
}

function admin_dashboard_is_superadmin($user = null, $priv = null)
{
	if ($user === null) {
		$user = ($GLOBALS['USER'] ?? null);
	}

	if ($priv === null) {
		$priv = ($GLOBALS['PRIV'] ?? array());
	}

	$user = (is_array($user) ? $user : array());
	$priv = (is_array($priv) ? $priv : array());

	return !empty($priv['EDIT_PRIV']);
}

function admin_dashboard_can_access($user = null, $priv = null)
{
	if ($user === null) {
		$user = ($GLOBALS['USER'] ?? null);
	}

	if ($priv === null) {
		$priv = ($GLOBALS['PRIV'] ?? array());
	}

	$user = (is_array($user) ? $user : array());
	$priv = (is_array($priv) ? $priv : array());

	if (empty($user['id'])) {
		return false;
	}

	if (admin_dashboard_is_superadmin($user, $priv)) {
		return true;
	}

	$flags = array(
		'cats',
		'user_add',
		'messages',
		'setting_user',
		'ip_util',
		'search_query',
		'sessions_view',
		'sessions_clear',
		'multitracker_accounts',
		'news_add',
		'edit_news',
		'faq_moderate',
		'comments_edit',
		'comments_delete',
		'edit_release',
		'edit_banned',
	);

	foreach ($flags as $flag) {
		if (!empty($priv[$flag])) {
			return true;
		}
	}

	return user_wall_reports_can_moderate();
}



//Gzip сжатие
function gzip() {
	global $config;
	static $already_loaded;
	if ($already_loaded) {
		return;
	}

	if (extension_loaded('zlib') && ini_get('zlib.output_compression') != '1' && ini_get('output_handler') != 'ob_gzhandler' && $config['gzip']) {
		ob_start('ob_gzhandler');
	} else {
		ob_start();
	}

	$already_loaded = true;
}

//Head голова сайта
function head($title = '' , $light = false , $description = '' , $keywords = '' ) {
	global $config , $language , $USER , $db , $memcached, $PRIV , $rewrite;

	//Сайт открыт
	if($config['siteonline'] == 0 ) {
		die($language['template_26']);
	}

	//Тема трекера
	$tpl = $config['template'];

	//Название сайа | Название страницы
	$sitename = $config['sitename'];
	$title = (empty($title) ? '' : $title);
	$header = '';


	//Формируем header
	$header .= '<script type="text/javascript" src="public/js/jquery.js"></script>
	';
	$header .= '<script type="text/javascript" src="public/js/main.js"></script>
	';
	$header .=	'<link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
	';
	$header .=	'<title>'.$sitename.' » '.$title.'</title>
	';
	$header .=	'<meta name="description" content="'.$sitename.' скачать бесплатно , скачать торрент без регистрации , Смотреть онлайн, фильмы , кино , сериалы , мультфильмы,  новинки, комедии, ужасы,  Без регистранции и смс, все абсолютно бесплатно, фильмы онлайн , многое другое, последние новинки онлайн" />
	';
	$header .=	'<meta name="keywords " content=" фильмы бесплатно , скачать игры бесплатно, скачать без регистрации,  сериалы , скачать сериалы бесплатно, еротика бесплатно , скачать фильмы , скачать игры , аниме торренты , скачать игры беслплатно , торрент трекер без регистрации , скачать бесплатно эротику , аниме, хентай , фильмы торренты , скачать хентай " />
	';
	$header .= '<meta name="robots" content="INDEX,FOLLOW" />
	';




	//Подключаем шаблон
	require 'templates/'.$tpl.'/template.php';

	//Если шаблон легкий
	if($light == true && !defined('LIGHT')) {
		define('LIGHT'  , true);
	}


	require 'templates/'.$tpl.'/head.php';



}

//Подвал сайта
function foot($light = false) {
	global $config , $language , $USER , $db , $memcached , $timer ,$PRIV , $rewrite , $CRON ;

	//Тема трекера
	$tpl = $config['template'];


	$timer['b'] = timer();

	//За сколько секунд загрузилась страница
	$seconds = number_format($timer['b'] - $timer['a'] , 8);


	//Если шаблон легкий
	if($light == true && !defined('LIGHT')) {
		define('LIGHT'  , true);
	}


	//Подключаем шаблон
	require 'templates/'.$tpl.'/foot.php';

	//DEGUB SQL
	$showSqlDebug = (DEGUB_SQL || admin_dashboard_can_access(($USER ?? null), ($PRIV ?? null)));
	if($showSqlDebug) {
		foreach($db->query_list AS $res) {
			echo '<b>'.$res['num'].' - ('.(round($res['time'] , 1) >= 0.6 ? '<font color="red">'.$res['time'].'</font>' : '<font color="green">'.$res['time'].'</font>' ).')</b>'.' - '.$res['query'].'<br><br>';
		}

	}

	if(!$config['crontab']) {
		//Autoclean system
		if( (time() - $CRON['autoclean_last'] ) > $CRON['autoclean_interval'] ) {
			echo '<img src="autoclean.php" border="0" width="0" height="0">';
		}
		//Multi Remote
		if ($CRON['multi_remote'] && ( (time() - $CRON['last_remotecheck']) > $CRON['remotecheck_interval'])) {
			echo '<img width="0px" height="0px" alt="" title="" src="update.peers.php"/>';
		}
	}
}

function stdfoot($light = false)
{
	$GLOBALS['LITETRACKER_STANDARD_SIDEBAR'] = true;

	foot($light);
}


//Определяем пользователя
function user_check() {
	global $config,$memcached , $db;

	//Удаляем USER
	unset($GLOBALS["USER"]);
	$updateset = array();


	$uid = (int)($_COOKIE[COOKIE_ID] ?? 0); //ID пользователя
	$pass = $_COOKIE[COOKIE_PASSWORD] ?? ''; //PASSWORD пользователя

	//Проверяем cookies
    if (empty($uid) || empty($pass) ) {
		//check user session
		user_session();
		$GLOBALS["PRIV"] = get_priv_info(0);
        return;
	}
	//Проверяем ID и PASSWORD на валидность
    if (!$uid || strlen($pass) != 32) {
		//check user session
		user_session();
		$GLOBALS["PRIV"] = get_priv_info(0);
        return;
	}

	//Информация о пользователе
	$row = get_user_info($uid);


	//Если запрос возвращает false
    if (!$row) {
		//check user session
		user_session();
		$GLOBALS["PRIV"] = get_priv_info(0);
        return;
	}


    $subnet = explode('.', getip());
	$subnet[2] = $subnet[3] = 0;
	$subnet = implode('.', $subnet); // 255.255.0.0
	if ($pass !== md5($row["password"] . COOKIE_SALT . $subnet)) {
		//check user session
		user_session();
		$GLOBALS["PRIV"] = get_priv_info(0);
		return;
	}


	//Обновляем  IP адрес , если он изменился
	$ip = ip2long_db($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'); //IP адрес
    if ($ip != $row['ip']) {
        $updateset[] = 'ip = "'. $ip . '"';
	}


	//Обновляем время, если оно изменилось
    if (strtotime($row['last_access']) <= strtotime(get_date_time(gmtime() - (10*60)) ) ) {
       $updateset[] = 'last_access = "' . $db->safesql(get_date_time()).'"';
	}

	//Если что-нибудь требует обновлению - обновляем :D
    if (sizeof($updateset)) {
		// $memcached->delete('user_'.$uid);
        $sql = $db->query("UPDATE LOW_PRIORITY users SET ".implode(", ", $updateset)." WHERE id=" . $row["id"]);
		// $db->free($sql);
	}

	//Определяем IP-адрем пользователя
    $row['ip'] = $ip;
	//Определяем USER
    $GLOBALS["USER"] = $row;
	$GLOBALS["PRIV"] = get_priv_info($row['class']);


	//check user session
	user_session();
}

//Определяем сессию
function user_session()
{
	global $USER , $config , $memcached ,$db;

	$update = array();

	//Определяем session_id
	$session_id = session_id();
	$update[] = 'session_id="'.$db->safesql($session_id).'"';

	//Определяем id пользователя
	if($USER) {
		$user_id = $USER['id'];
	}
	else {
		$user_id = '-1';
	}
	$update[] = 'user_id="'.$db->safesql($user_id).'"';


	//Определяем время последнее вермя посещения сайта
	$last_access = get_date_time(time());
	$update[] = 'last_access="'.$last_access.'"';

	//Определяем ip
	$ip = ip2long_db($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
	$update[] = 'ip="'.$ip.'"';


	//Определяем user_agent
	$user_agent =  $_SERVER["HTTP_USER_AGENT"] ?? '';
	$update[] = 'user_agent="'.$db->safesql($user_agent).'"';

	//Определяем php_self
	$php_self = $_SERVER['PHP_SELF'] ?? '';
	$update[] = 'php_self="'.$db->safesql($php_self).'"';

	$throttleKey = 'session_touch_'.md5($session_id.'|'.$user_id);
	$shouldWrite = true;
	if (is_object($memcached) && false !== $memcached->get($throttleKey)) {
		$shouldWrite = false;
	}

	if (sizeof($update) && $shouldWrite) {
			$sql = $db->query("INSERT INTO sessions (session_id, user_id, last_access, ip , user_agent, php_self) VALUES ('{$session_id}', '{$user_id}', '{$last_access}', '{$ip}' , '{$user_agent}', '{$php_self}') ON DUPLICATE KEY UPDATE ".implode(", ", $update));
			// $db->free($sql);
			if (is_object($memcached)) {
				$memcached->set($throttleKey, "1", 0, 60);
			}
	}

	return;
}


//IP адрес
function getip()
{
  if (isset($_SERVER)) {
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
      $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
      $ip = $_SERVER['HTTP_CLIENT_IP'];
    } else {
      $ip = $_SERVER['REMOTE_ADDR'];
    }
  } else {
    if (getenv('HTTP_X_FORWARDED_FOR')) {
      $ip = getenv('HTTP_X_FORWARDED_FOR');
    } elseif (getenv('HTTP_CLIENT_IP')) {
      $ip = getenv('HTTP_CLIENT_IP');
    } else {
      $ip = getenv('REMOTE_ADDR');
    }
  }

  return $ip;

}

//Определяем время
function get_date_time($timestamp = 0) {
	if ($timestamp)
		return date("Y-m-d H:i:s", $timestamp);
	else
		return date("Y-m-d H:i:s");
}
//Определяем время
function gmtime() {
    return strtotime(get_date_time());
}

//Цвет и ник пользователя
function get_user_color($class, $username) {
	$priv = get_priv_info($class);
	return "<font  title=\"".htmlspecialchars($priv['NAME'])."\" style=\"color:#".htmlspecialchars($priv['COLOR'])."\">" . $username . "</font>";
}


//Определяем класс пользователя
function get_user_class()
{
  global $USER;
  return $USER["class"];
}

//Имя класса
function get_user_class_name($class)
{
	$priv = get_priv_info($class);
	return "<font>".htmlspecialchars($priv['NAME'])."</font>";

}

//Вывод сообщения
function msg($subject = '' , $text = '' , $type = 'success') {

	echo '<table width="97%" align="center"><tr><td><p class="message">';
	echo '<strong>'.$subject.'</strong><br>';
	echo ''.$text.'';
	echo '</p></td></tr></table>';
}

/*
function msg($heading = '', $text = '', $div = 'success') {
    if ($htmlstrip) {
        $heading = htmlspecialchars(trim($heading));
        $text = htmlspecialchars(trim($text));
    }
    print("<table class=\"main\" width=\"95%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" align=\"center\"><tr><td class=\"embedded\">\n");
    print("<div class=\"$div\">".($heading ? "<b>$heading</b><br />" : "")." ".$text."</div></td></tr></table>\n");

}
*/

//Для ajax
function msg_ajax($text , $type = 'ajaxsuccess') {
	echo '<div id="'.$type.'">'.$text.'</div>';
}

//Функция проверки имя
function validusername($username)
{
	if ($username == "")
	    return false;

	// The following characters are allowed in user names
	$allowedchars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_".
	"абвгдеёжзйиклмнопрстуфхшщэюяьъчйыцЦЧЙАБВГДЕЁЖЗИКЛМНОПРСТУФХШЩЭЮЯЬЪЙЫ";

	for ($i = 0; $i < strlen($username); ++$i)
	    if (strpos($allowedchars, $username[$i]) === false)
	        return false;

	    return true;
}

//Функция проверки email
function validemail($email) {
    return preg_match('/^[\w.-]+@([\w.-]+\.)+[a-z]{2,6}$/is', $email);
}

//Функция проверки файла
function validfilename($name) {
    return preg_match('/^[^\0-\x1f:\\\\\/?*\xff#<>|]+$/si', $name);
}


function validip($ip) {
	return preg_match("/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/", $ip);
}
//Формирование секретного кода
function mksecret($length = 32) {
$set = array("a","A","b","B","c","C","d","D","e","E","f","F","g","G","h","H","i","I","j","J","k","K","l","L","m","M","n","N","o","O","p","P","q","Q","r","R","s","S","t","T","u","U","v","V","w","W","x","X","y","Y","z","Z","1","2","3","4","5","6","7","8","9");
	$str = '';
	for($i = 1; $i <= $length; $i++)
	{
		$ch = rand(0, count($set)-1);
		$str .= $set[$ch];
	}
	return $str;
}

function lt_is_https_request()
{
	if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
		return true;
	}

	if ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443) {
		return true;
	}

	return (strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https');
}

function lt_cookie_domain()
{
	global $config;

	if (empty($config['cookies_mode'])) {
		return '';
	}

	$domain = trim((string) ($_SERVER['HTTP_HOST'] ?? ''));
	if ($domain === '') {
		return '';
	}

	if (strtolower(substr($domain, 0, 4)) === 'www.') {
		$domain = substr($domain, 4);
	}

	if (substr($domain, 0, 1) !== '.') {
		$domain = '.'.$domain;
	}

	return $domain;
}

function lt_set_cookie($name, $value, $expires = 0x7fffffff, $httpOnly = true, $sameSite = 'Lax')
{
	$options = array(
		'expires' => (int) $expires,
		'path' => '/',
		'domain' => lt_cookie_domain(),
		'secure' => lt_is_https_request(),
		'httponly' => (bool) $httpOnly,
		'samesite' => ($sameSite !== '' ? (string) $sameSite : 'Lax'),
	);

	setcookie((string) $name, (string) $value, $options);
}

function lt_password_hash_value($password)
{
	return password_hash((string) $password, PASSWORD_DEFAULT);
}

function lt_password_verify_user($password, $userRow, &$needsRehash = false)
{
	$needsRehash = false;
	$password = (string) $password;
	$storedHash = trim((string) ($userRow['password'] ?? ''));
	$passwordCode = (string) ($userRow['password_code'] ?? '');

	if ($storedHash === '') {
		return false;
	}

	if (strpos($storedHash, '$2y$') === 0 || strpos($storedHash, '$argon2') === 0) {
		$isValid = password_verify($password, $storedHash);
		if ($isValid) {
			$needsRehash = password_needs_rehash($storedHash, PASSWORD_DEFAULT);
		}

		return $isValid;
	}

	$legacyHash = md5($passwordCode.$password.$passwordCode);
	if (!hash_equals($storedHash, $legacyHash)) {
		return false;
	}

	$needsRehash = true;

	return true;
}

function lt_is_mobile_request()
{
	$userAgent = strtolower((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
	if ($userAgent === '') {
		return false;
	}

	return (bool) preg_match('~android|iphone|ipad|ipod|mobile|opera mini|iemobile|windows phone|blackberry|webos~i', $userAgent);
}

function lt_is_private_ip($ip)
{
	$ip = trim((string) $ip);
	if ($ip === '' || $ip === '127.0.0.1' || $ip === '::1') {
		return ($ip !== '');
	}

	return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
}

function lt_require_cron_access()
{
	global $config;

	$token = trim((string) ($config['cron_token'] ?? ''));
	$providedToken = trim((string) ($_GET['token'] ?? $_POST['token'] ?? $_SERVER['HTTP_X_CRON_TOKEN'] ?? ''));
	$remoteIp = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));

	if ($token !== '' && hash_equals($token, $providedToken)) {
		return true;
	}

	if (lt_is_private_ip($remoteIp)) {
		return true;
	}

	header('HTTP/1.1 403 Forbidden');
	die('Forbidden');
}

function lt_fix_utf8_mojibake($value)
{
	$value = (string) $value;
	if ($value === '') {
		return $value;
	}

	// Common signature of UTF-8 text that was decoded as Latin-1 / Windows-1252.
	if (!preg_match('/[\x{00D0}\x{00D1}\x{00C3}\x{00E2}]/u', $value)) {
		return $value;
	}

	if (!function_exists('mb_convert_encoding')) {
		return $value;
	}

	$reencoded = @mb_convert_encoding($value, 'Windows-1252', 'UTF-8');
	if (!is_string($reencoded) || ($reencoded === '' && $value !== '')) {
		return $value;
	}

	$fixed = @mb_convert_encoding($reencoded, 'UTF-8', 'UTF-8');
	if (!is_string($fixed) || $fixed === '') {
		return $value;
	}

	$sourceCyrillic = preg_match_all('/\p{Cyrillic}/u', $value, $sourceMatches);
	$fixedCyrillic = preg_match_all('/\p{Cyrillic}/u', $fixed, $fixedMatches);

	if ((int) $fixedCyrillic <= (int) $sourceCyrillic) {
		return $value;
	}

	return $fixed;
}

function lt_csrf_token($scope = 'default')
{
	$scope = preg_replace('~[^a-z0-9:_-]+~i', '-', trim((string) $scope));
	if ($scope === '') {
		$scope = 'default';
	}

	if (function_exists('lt_session_resume')) {
		lt_session_resume();
	}

	if (!isset($_SESSION['lt_csrf']) || !is_array($_SESSION['lt_csrf'])) {
		$_SESSION['lt_csrf'] = array();
	}

	if (empty($_SESSION['lt_csrf'][$scope])) {
		if (function_exists('random_bytes')) {
			$_SESSION['lt_csrf'][$scope] = bin2hex(random_bytes(16));
		} else {
			$_SESSION['lt_csrf'][$scope] = md5(mksecret(32).microtime(true).$scope);
		}
	}

	$token = (string) $_SESSION['lt_csrf'][$scope];

	if (function_exists('lt_session_commit')) {
		lt_session_commit();
	}

	return $token;
}

function lt_csrf_input($scope = 'default', $fieldName = 'csrf_token')
{
	$scope = (string) $scope;
	$fieldName = trim((string) $fieldName);
	if ($fieldName === '') {
		$fieldName = 'csrf_token';
	}

	return '<input type="hidden" name="'.htmlspecialchars($fieldName, ENT_QUOTES, 'UTF-8').'" value="'.htmlspecialchars(lt_csrf_token($scope), ENT_QUOTES, 'UTF-8').'">';
}

function lt_csrf_query($scope = 'default', $fieldName = 'csrf_token')
{
	$fieldName = trim((string) $fieldName);
	if ($fieldName === '') {
		$fieldName = 'csrf_token';
	}

	return rawurlencode($fieldName).'='.rawurlencode(lt_csrf_token($scope));
}

function lt_csrf_validate($scope = 'default', $token = null)
{
	$scope = preg_replace('~[^a-z0-9:_-]+~i', '-', trim((string) $scope));
	if ($scope === '') {
		$scope = 'default';
	}

	if (function_exists('lt_session_resume')) {
		lt_session_resume();
	}

	if ($token === null) {
		$token = (string) ($_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '');
	}

	$expected = (string) ($_SESSION['lt_csrf'][$scope] ?? '');
	if ($expected === '' || $token === '') {
		if (function_exists('lt_session_commit')) {
			lt_session_commit();
		}
		return false;
	}

	$isValid = hash_equals($expected, (string) $token);

	if (function_exists('lt_session_commit')) {
		lt_session_commit();
	}

	return $isValid;
}

//Добавление cookies
function login_cookie($id, $password_hash,  $expires = 0x7fffffff) {
	global $memcached , $config;

   $subnet = explode('.', getip());
	$subnet[2] = $subnet[3] = 0;
	$subnet = implode('.', $subnet); // 255.255.0.0

	//Очищаем старые cookies
	logout_cookie();
	//Добавляем cookies
	lt_set_cookie(COOKIE_ID, $id, $expires, true, 'Lax');
	lt_set_cookie(COOKIE_PASSWORD, md5($password_hash.COOKIE_SALT.$subnet), $expires, true, 'Lax');


	//Удаляем memcached файл
	$memcached->delete('user_'.$id);

}


//Удаление cookies
function logout_cookie() {
	global  $memcached , $USER , $config;

	$expires = time() - 3600;
	lt_set_cookie(COOKIE_ID, '', $expires, true, 'Lax');
	lt_set_cookie(COOKIE_PASSWORD, '', $expires, true, 'Lax');
	unset($_COOKIE[COOKIE_ID], $_COOKIE[COOKIE_PASSWORD]);
	//Удаляем memcached файл
	if($USER && isset($USER['id'])) {
		$memcached->delete('user_'.$USER['id']);
	}
}

//Проверка авторизации
function is_login() {
    global $USER;
    if (!$USER) {
	    header("Location: /login.php?referer=".$_SERVER['PHP_SELF']."");
        die();
    }
}

//Вывод ошибки
function err($subject = '' , $text = '' , $pref = 0 , $type = 'error') {
	global $language;
	head(($type == 'success' ? 'Успешно' : 'Ошибка') , true);
	// begin_frame('Ошибка');
	msg($subject , $text.($pref ? '<br><a href="javascript:history.go(-1);">'.$language['default_11'].'</a>' : '') , $type);
	// end_frame();
	foot(true);
	die();
}

//Вывод тегов для категории
function taggenrelist($cat) {
	global $memcached , $db;
	$ret = array();

	if (false === ($ret = $memcached->get("taggenrelist_".$cat)))
	{
		$cache = array();
		$res = $db->query("SELECT id, name, howmuch FROM tags WHERE category=".$db->safesql($cat)." ORDER BY name ASC") or sqlerr(__FILE__ , __LINE__);
		while ($row = $db->get_row() )
			$cache[] = $row;

		$memcached->set("taggenrelist_".$cat, $cache , 0, 500);
		$ret = $cache;
	}

	return $ret;
}


function addtags($addtags) {
	global $language;
	foreach(explode(",", $addtags) as $tag)
	{
		if(!empty($addtags))
			$tags .= "<a style=\"font-weight:normal;\" href=\"browse.php?text=".$tag."&type=tags\">".$tag."</a>, ";
	}

	if ($tags)
		$tags = substr($tags, 0, -2);
	if (empty($addtags))
	$tags = $language['tags_1'];
	return $tags;
}


//Анти - XSS
function antixss() {
	//Запрещенные элементы
	$array = array('./' , '../' , '\'' , '<script>' , 'document.cookie' , '</script>' );

	//GET
	$query = $_GET;
	if( sizeof($query) ) {
		foreach($query AS $arr => $value) {
			$clear_xss = str_replace($array , '[xss]' , $value);
			$_GET[$arr]  = $clear_xss;
		}

	}

	//GET
	$query = $_POST;
	if( sizeof($query) ) {
		foreach($query AS $arr => $value) {
			$clear_xss = str_replace($array , '[xss]' , $value);
			$_POST[$arr]  = $clear_xss;
		}

	}


	return true;
}

//sqlwildcardesc
function sqlwildcardesc($x) {
	global $db;

	$value = ($db ? $db->safesql($x) : addslashes((string) $x));
	return str_replace(array("%","_"), array("\\%","\\_"), $value);
}



//Постраничная навигация
function pager($rpp, $count, $href, $opts = array()) {
	$opts = array_merge(array('lastpagedefault' => 0), $opts);
	$pagertop = '';
	$pagerbottom = '';
	$pages = ceil($count / $rpp);

	if (!$opts["lastpagedefault"])
		$pagedefault = 0;
	else {
		$pagedefault = floor(($count - 1) / $rpp);
		if ($pagedefault < 0)
			$pagedefault = 0;
	}

	if (isset($_GET["page"])) {
		$page = (int)$_GET["page"];
		if ($page < 0)
			$page = $pagedefault;
	}
	else
		$page = $pagedefault;



	$mp = $pages - 1;

	if ($count) {
		$pagerarr = array();
		$dotted = 0;
		$dotspace = 3;
		$dotend = $pages - $dotspace;
		$curdotend = $page - $dotspace;
		$curdotstart = $page + $dotspace;
		for ($i = 0; $i < $pages; $i++) {
			if (($i >= $dotspace && $i <= $curdotend) || ($i >= $curdotstart && $i < $dotend)) {
				if (!$dotted)
				   $pagerarr[] = '<li><span class="page-item dots">...</span></li>';
				$dotted = 1;
				continue;
			}
			$dotted = 0;
			$start = $i * $rpp + 1;
			$end = $start + $rpp - 1;
			if ($end > $count)
				$end = $count;

			$text = $i + 1;
			$title = $start.'&nbsp;-&nbsp;'.$end;
			if ($i != $page)
				$pagerarr[] = '<li><a class="page-item page" title="'.$title.'" href="'.$href.'page='.$i.'">'.$text.'</a></li>';
			else
				$pagerarr[] = '<li><span class="page-item page active">'.$text.'</span></li>';

				  }
		$prev = '';
		$next = '';

		if ($page >= 1) {
			$prev = '<a class="page-item prev" href="'.$href.'page='.($page - 1).'" aria-label="Предыдущая страница">&larr;</a>';
		}

		if ($page < $mp && $mp >= 0) {
			$next = '<a class="page-item next" href="'.$href.'page='.($page + 1).'" aria-label="Следующая страница">&rarr;</a>';
		}

		$pagertop = '<nav class="paging" aria-label="Навигация по страницам">';
		if ($prev !== '') {
			$pagertop .= $prev;
		}
		$pagertop .= '<ul class="list-reset">'.join('', $pagerarr).'</ul>';
		if ($next !== '') {
			$pagertop .= $next;
		}
		$pagertop .= '</nav>';
		$pagerbottom = $pagertop;

	}
	else {
		$pagertop = '';
		$pagerbottom = '';
	}

	$start = $page * $rpp;

	return array($pagertop, $pagerbottom, "LIMIT $start , $rpp");
}

//Преобразуем размер файла
function mksize($bytes) {
	if ($bytes < 1000 * 1024)
		return number_format($bytes / 1024, 2) . " kB";
	elseif ($bytes < 1000 * 1048576)
		return number_format($bytes / 1048576, 2) . " MB";
	elseif ($bytes < 1000 * 1073741824)
		return number_format($bytes / 1073741824, 2) . " GB";
	else
		return number_format($bytes / 1099511627776, 2) . " TB";
}

//Цвет ратио
  function get_ratio_color($ratio) {
    if ($ratio < 0.1) return "#ff0000";
    if ($ratio < 0.2) return "#ee0000";
    if ($ratio < 0.3) return "#dd0000";
    if ($ratio < 0.4) return "#cc0000";
    if ($ratio < 0.5) return "#bb0000";
    if ($ratio < 0.6) return "#aa0000";
    if ($ratio < 0.7) return "#990000";
    if ($ratio < 0.8) return "#880000";
    if ($ratio < 0.9) return "#770000";
    if ($ratio < 1) return "#660000";
    return "#000000";
}


function sql_timestamp_to_unix_timestamp($s)
{
  return mktime(substr($s, 11, 2), substr($s, 14, 2), substr($s, 17, 2), substr($s, 5, 2), substr($s, 8, 2), substr($s, 0, 4));
}

//Преобразование даты / времени
//гггг-мм-дд чч:мм:сс
function convent_date($date = '' ) {
	global $language;
	//Название месяцев
	$mounth = array(
			'01' => $language['month_1'],
			'02' => $language['month_2'],
			'03' => $language['month_3'],
			'04' => $language['month_4'],
			'05' => $language['month_5'],
			'06' => $language['month_6'],
			'07' => $language['month_7'],
			'08' => $language['month_8'],
			'09' => $language['month_9'],
			'10' => $language['month_10'],
			'11' => $language['month_11'],
			'12' => $language['month_12'],
			);


	//////////////////////////////////////////////
	//$explode['0'] - дата
	//$explode['1'] - время
	//////////////////////////////////////////////
	//Разбиваем на дата / время
	$explode = explode(' '  , $date);



	//////////////////////////////////////////////
	//$explode_date['0'] - год
	//$explode_date['1'] - месяц
	//$explode_date['2'] - день
	//////////////////////////////////////////////
	//Разбиваем дату на гггг-мм-дд
	$explode_date = explode('-' , $explode['0']);

	//Удаляем нуль перез числом
	if(substr($explode_date['2'] ,0,1) == '0' ) {
		$explode_date['2'] = str_replace('0' , '' , $explode_date['2']);
	}



	//////////////////////////////////////////////
	//$explode_time['0'] - час
	//$explode_time['1'] - минута
	//$explode_time['2'] - секунда
	//////////////////////////////////////////////
	//Разбиваем время на чч:мм:cc
	$explode_time = explode(':' , $explode['1']);

	return ($explode_date['2'] == date('d') ? $language['month_13'] : $explode_date['2'] .' '.$mounth[$explode_date['1']]).' '. ($explode_date['2'] != date('d') ? $explode_date['0'].' года' : '' ) . ' , '.$explode_time['0'].':'.$explode_time['1'];
}


//Вывод рейтинга пользователю или гостю
function get_user_rating($uploaded = '' , $downloaded = '') {
	global $USER , $language;

	if(!empty($uploaded)  && !empty($downloaded) ) {
		$down =	$downloaded;
		$up =	$uploaded;
	}



	$ratio = get_ratio($up , $down);
	if($ratio <= 0){
		echo '<div id="rateTopZero"><img src="public/images/rate/zero1.gif"></div><div id="rateBottomZero"><font color="#8ba1bc">'.$language['rating_1'].': '.$ratio.'%</font></div>';
	}elseif($ratio > 0 AND $ratio <= 10){
		echo '<div id="rateTopGreen"><img src="public/images/rate/green1.gif"></div><div id="rateBottomGreen"><font color="#1e7300">'.$language['rating_1'].': '.$ratio.'%</font></a></div>';
	}elseif($ratio > 10 AND $ratio <= 100){
		echo '<div id="rateTopGold"><img src="public/images/rate/gold1.gif"></div><div id="rateBottomGold"><font color="#948239">'.$language['rating_1'].': '.$ratio.'%</font></a></div>';
	}elseif($ratio > 100){
		echo '<div id="rateTopDarkgold"><img src="public/images/rate/darkgold1.gif"></div><div id="rateBottomDarkgold"><font color="#fff2c8">'.$language['rating_1'].': '.$ratio.'%</font></a></div>';
	}

}

//Определяем ратио
function get_ratio($uploaded , $downloaded) {

	if($downloaded > 0) {
		$ratio =  ($uploaded / ($downloaded / 10) / 1);
		$ratio = number_format($ratio);
		$ratio = str_replace(',' , '' , $ratio);
	}else {
		$ratio = '0';
	}

	return $ratio;
}

//Преобразуем дату
function rusdate($num,$type = 0){
    $rus = array (
        "year"    => array( "лет", "год", "года", "года", "года", "лет", "лет", "лет", "лет", "лет"),
        "month"  => array( "месяцев", "месяц", "месяца", "месяца", "месяца", "месяцев", "месяцев", "месяцев", "месяцев", "месяцев"),
        "week"  => array( "недель", "неделю", "недели", "недели", "недели", "недель", "недель", "недель", "недель", "недель"),
        "day"   => array( "дней", "день", "дня", "дня", "дня", "дней", "дней", "дней", "дней", "дней"),
        "hour"    => array( "часов", "час", "часа", "часа", "часа", "часов", "часов", "часов", "часов", "часов"),
        "minute" => array( "минут", "минуту", "минуты", "минуты", "минуты", "минут", "минут", "минут", "минут", "минут"),
        "second" => array( "секунд", "секунду", "секунды", "секунды", "секунды", "секунд", "секунд", "секунд", "секунд", "секунд"),
    );

    $num = intval($num);
    if ( 10 < $num && $num < 20) return $rus[$type][0];
    return $rus[$type][$num % 10];
}

function get_elapsed_time($date,$showseconds=true,$unix=true){
    if($date == "0000-00-00 00:00:00") return "---";
    if(!$unix){$U = date('U',strtotime($date));}else{$U=$date;};
    $N = time();
    $diff = $N-$U;


    if($diff>=31536000){
        $Iyear = floor($diff/31536000);
        $diff = $diff-($Iyear*31536000);
    }
    if($diff>=2629800){    //2592000 seconds in month with 30 days
        $Imonth = floor($diff/2629800);
        $diff = $diff-($Imonth*2629800);
    }
    if($diff>=604800){
        $Iweek = floor($diff/604800);
        $diff = $diff-($Iweek*604800);
    }
    if($diff>=86400){
        $Iday = floor($diff/86400);
        $diff = $diff-($Iday*86400);
    }
    if($diff>=3600){
        $Ihour = floor($diff/3600);
        $diff = $diff-($Ihour*3600);
    }
    if($diff>=60){
        $Iminute = floor($diff/60);
        $diff = $diff-($Iminute*60);
    }
    if($diff>0){
        $Isecond = floor($diff);
    }

    $j = " ";

    $ret = "";

    if(isset($Iyear)) $ret .= $Iyear." ".rusdate($Iyear,'year').$j;
    if(isset($Imonth)) $ret .= $Imonth ." ".rusdate($Imonth ,'month').$j;
    if(isset($Iweek)) $ret .= $Iweek ." ".rusdate($Iweek ,'week').$j;
    if(isset($Iday)) $ret .= $Iday ." ".rusdate($Iday ,'day').$j;
    if(isset($Ihour)) $ret .= $Ihour ." ".rusdate($Ihour ,'hour').$j;
    if(isset($Iminute)) $ret .= $Iminute ." ".rusdate($Iminute ,'minute').$j;

//    if($showseconds==false && $Iminute<1)$Iminute=0;
    if($showseconds==false && $Iminute<1 && $Ihour<1 && $Iday<1 && $Iweek<1 && $Imonth<1 && $Iyear<1)return rusdate(0 ,'minute');

    if(($Isecond>0 OR $ret=="") AND $showseconds==true){
        if($ret=="" AND !isset($Isecond))$Isecond=0;
        $ret .= $Isecond ." ".rusdate($Isecond ,'second').$j;
    }
    return $ret;
}



//Нагрузка на сервер
function get_server_load() {
	global  $phpver;
	if (strtolower(substr(PHP_OS, 0, 3)) === 'win') {
		return 0;
	} elseif (@file_exists("/proc/loadavg")) {
		$load = @file_get_contents("/proc/loadavg");
		$serverload = explode(" ", $load);
		$serverload['0'] = round($serverload['0'], 4);
		if(!$serverload) {
			$load = exec("uptime");
			$load = preg_split('/load averages?: /', $load);
			$serverload = explode(",", $load['1']);
		}
	} else {
		$load = exec("uptime");
		$load = preg_split('/load averages?: /', $load);
		$serverload = explode(",", $load['1']);
	}
	$returnload = trim($serverload['0']);
	if(!$returnload) {
		$returnload = $tracker_lang['unknown'];
	}
	return $returnload;
}


/**
 * Узнаем сколько времени прошло с определенной даты
 * @param datetime $time
 * @return array (years , months , days)
 */
function get_certain_time($time) {
	$date1 = $time;
	$date2 = get_date_time();

	$diff = abs(strtotime($date2) - strtotime($date1));

	$years = floor($diff / (365*60*60*24));
	$months = floor(($diff - $years * 365*60*60*24) / (30*60*60*24));
	$days = floor(($diff - $years * 365*60*60*24 - $months*30*60*60*24)/ (60*60*24));


	$array = array('years' => $years , 'months' => $months , 'days' => $days);
	return $array;
}

//Отправка локального сообщения
function send_msg($name = ''  , $text = '' , $user_in = 0 ,  $user_out = 0 ) {
	global $memcached , $db;

	if(!$user_in) {
		return 0;
	}

	if(empty($name) ) {
		$name = 'Re:';
	}

	if(empty($text) ) {
		return 0;
	}
	$db->query("INSERT INTO mail(name , text , id_user_in , id_user_out , date , delete_in , delete_out ) VALUES ('".$db->safesql($name)."' , '".$db->safesql($text)."' , ".$user_in." , ".$user_out." , NOW() , 0 , 0 )");
	$db->query("UPDATE users SET num_messages=(num_messages+1) WHERE id=".$user_in);
	$memcached->delete("user_".$user_in);
	return 1;
}


//BB code Encode
function format_comment($text, $strip_html = true) {
	$s = $text;


	if ($strip_html)
		$s = htmlspecialchars_uni($s);
		// $s = htmlspecialchars($s);

	$bb[] = "#\[img\](?!javascript:)([^?](?:[^\[]+|\[(?!url))*?)\[/img\]#i";
	$html[] = "<img class=\"linked-image\" src=\"\\1\" border=\"0\" alt=\"\\1\" title=\"\\1\" />";
	$bb[] = "#\[img=([a-zA-Z]+)\](?!javascript:)([^?](?:[^\[]+|\[(?!url))*?)\[/img\]#is";
	$html[] = "<img class=\"linked-image\" src=\"\\2\" align=\"\\1\" border=\"0\" alt=\"\\2\" title=\"\\2\" />";
	$bb[] = "#\[img\ alt=([a-zA-Zа-яА-Я0-9\_\-\. ]+)\](?!javascript:)([^?](?:[^\[]+|\[(?!url))*?)\[/img\]#is";
	$html[] = "<img class=\"linked-image\" src=\"\\2\" align=\"\\1\" border=\"0\" alt=\"\\1\" title=\"\\1\" />";
	$bb[] = "#\[img=([a-zA-Z]+) alt=([a-zA-Zа-яА-Я0-9\_\-\. ]+)\](?!javascript:)([^?](?:[^\[]+|\[(?!url))*?)\[/img\]#is";
	$html[] = "<img class=\"linked-image\" src=\"\\3\" align=\"\\1\" border=\"0\" alt=\"\\2\" title=\"\\2\" />";
	$bb[] = "#\[kp=([0-9]+)\]#is";
	$html[] = "<a href=\"http://www.kinopoisk.ru/level/1/film/\\1/\" rel=\"nofollow\"><img src=\"http://www.kinopoisk.ru/rating/\\1.gif/\" alt=\"Кинопоиск\" title=\"Кинопоиск\" border=\"0\" /></a>";
	$bb[] = "#\[url\]([\w]+?://([\w\#$%&~/.\-;:=,?@\]+]+|\[(?!url=))*?)\[/url\]#is";
	$html[] = "<a href=\"\\1\" title=\"\\1\">\\1</a>";
	$bb[] = "#\[url\]((www|ftp)\.([\w\#$%&~/.\-;:=,?@\]+]+|\[(?!url=))*?)\[/url\]#is";
	$html[] = "<a href=\"http://\\1\" title=\"\\1\">\\1</a>";
	$bb[] = "#\[url=([\w]+?://[\w\#$%&~/.\-;:=,?@\[\]+]*?)\]([^?\n\r\t].*?)\[/url\]#is";
	$html[] = "<a href=\"\\1\" title=\"\\1\">\\2</a>";
	$bb[] = "#\[url=((www|ftp)\.[\w\#$%&~/.\-;:=,?@\[\]+]*?)\]([^?\n\r\t].*?)\[/url\]#is";
	$html[] = "<a href=\"http://\\1\" title=\"\\1\">\\3</a>";
	$bb[] = "/\[url=([^()<>\s]+?)\]((\s|.)+?)\[\/url\]/i";
	$html[] = "<a href=\"\\1\">\\2</a>";
	$bb[] = "/\[url\]([^()<>\s]+?)\[\/url\]/i";
	$html[] = "<a href=\"\\1\">\\1</a>";
	$bb[] = "#\[mail\](\S+?)\[/mail\]#i";
	$html[] = "<a href=\"mailto:\\1\">\\1</a>";
	$bb[] = "#\[mail\s*=\s*([\.\w\-]+\@[\.\w\-]+\.[\w\-]+)\s*\](.*?)\[\/mail\]#i";
	$html[] = "<a href=\"mailto:\\1\">\\2</a>";
	$bb[] = "#\[color=(\#[0-9A-F]{6}|[a-z]+)\](.*?)\[/color\]#si";
	$html[] = "<span style=\"color: \\1\">\\2</span>";
	$bb[] = "#\[(font|family)=([A-Za-z ]+)\](.*?)\[/\\1\]#si";
	$html[] = "<span style=\"font-family: \\2\">\\3</span>";
	$bb[] = "#\[size=([0-9]+)\](.*?)\[/size\]#si";
	$html[] = "<span style=\"font-size: \\1\">\\2</span>";
	$bb[] = "#\[(left|right|center|justify)\](.*?)\[/\\1\]#is";
	$html[] = "<div align=\"\\1\">\\2</div>";
	$bb[] = "#\[b\](.*?)\[/b\]#si";
	$html[] = "<b>\\1</b>";
	$bb[] = "#\[i\](.*?)\[/i\]#si";
	$html[] = "<i>\\1</i>";
	$bb[] = "#\[u\](.*?)\[/u\]#si";
	$html[] = "<u>\\1</u>";
	$bb[] = "#\[s\](.*?)\[/s\]#si";
	$html[] = "<s>\\1</s>";
	$bb[] = "#\[li\]#si";
	$html[] = "<li>";
	$bb[] = "#\[hr\]#si";
	$html[] = "<hr>";


	$s = preg_replace($bb, $html, $s);

	// Linebreaks
	$s = nl2br($s);

	  //[spoiler]Text[/spoiler]
$s = str_replace("[spoiler]","<div class=\"news-wrap\"><div class=\"news-head folded clickable\"><i>Скрытый текст</i></div><div class=\"news-body\">", $s);
// continue below //

    //[spoiler=name]Text[/spoiler]
$s = preg_replace("#\[spoiler=\s*((\s|.)+?)\s*\]#si",
"<div style=\"position: static;\" class=\"news-wrap\"><div class=\"news-head folded clickable\"><i>\\1</i></div><div class=\"news-body\">", $s);

$s = str_replace("[/spoiler]","</div></div>",$s);


	while (preg_match("#\[quote\](.*?)\[/quote\]#si", $s)) $s = encode_quote($s);
	while (preg_match("#\[quote=(.+?)\](.*?)\[/quote\]#si", $s)) $s = encode_quote_from($s);
	while (preg_match("#\[hide\](.*?)\[/hide\]#si", $s)) $s = encode_spoiler($s);
	while (preg_match("#\[hide=(.+?)\](.*?)\[/hide\]#si", $s)) $s = encode_spoiler_from($s);
	if (preg_match("#\[code\](.*?)\[/code\]#si", $s)) $s = encode_code($s);
	if (preg_match("#\[php\](.*?)\[/php\]#si", $s)) $s = encode_php($s);
/////////////////////////////////Tag [youtube][/youtube]
while (preg_match("/\[youtube\]((\s|.)+?)\[\/youtube\]/i", $s)) {
$s = str_replace("watch?v=","v/", $s);
$s = preg_replace ("/\[youtube\]((\s|.)+?)\[\/youtube\]/i", "<object width='640' height='505'><param name=movie value='\\1&hl=ru&fs=1&'></param><param name='allowFullScreen' value='true'></param><param name='allowscriptaccess' value='always'></param><embed src='\\1&hl=ru&fs=1&' type='application/x-shockwave-flash' allowscriptaccess='always' allowfullscreen='true' width='640' height='505'></embed></object>", $s);
}
///////////////////////////////////end tag youtube

/////////////////////////////////Tag [rutube][/rutube]
while (preg_match("/\[rutube\]((\s|.)+?)\[\/rutube\]/i", $s)) {
$s = preg_replace("/http:\/\/rutube.ru\/tracks\/([0-9]+)\.html\?v\=/","http://video.rutube.ru/", $s);
$s = preg_replace ("/\[rutube\]((\s|.)+?)\[\/rutube\]/i", "<object width='640' height='505'><param name=movie value='\\1'></param><param name='allowFullScreen' value='true'></param><param name='allowscriptaccess' value='always'></param><embed src='\\1' type='application/x-shockwave-flash' allowscriptaccess='always' allowfullscreen='true' width='640' height='505'></embed></object>", $s);
}
///////////////////////////////////end tag rutube

	// URLs
	$s = format_urls($s);



	return $s;
}


// Format quote
function encode_quote($text) {
	$start_html = "<div align=\"left\" ><div style=\"padding: 7px 5px 5px 7px; overflow: auto;\">"
	."<table width=\"100%\" cellspacing=\"1\" cellpadding=\"2\" border=\"0\" align=\"center\">"
	."<tr bgcolor=\"#74a3d4\"><td style=\"font-weight:bold;color:#fff\">Цитата</td></tr><tr style=\"border: 0.7px solid #74a3d4;\"><td style=\"background-color:#fff;\">";
	$end_html = "</td></tr></table></div></div>";
	$text = preg_replace("#\[quote\](.*?)\[/quote\]#si", "".$start_html."\\1".$end_html."", $text);
	return $text;
}

// Format quote from
function encode_quote_from($text) {
	$start_html = "<div align=\"left\" ><div style=\"padding: 6px 4px 4px 6px; overflow: auto;\">"
	."<table width=\"100%\" cellspacing=\"1\" cellpadding=\"2\" border=\"0\" align=\"center\">"
	."<tr bgcolor=\"#74a3d4\"><td style=\"font-weight:bold;color:#fff\">Сообщение от  \\1</td></tr><tr style=\"border: 0.7px solid #74a3d4;\"><td style=\"background-color:#fff;\">";
	$end_html = "</td></tr></table></div></div>";
	$text = preg_replace("#\[quote=(.+?)\](.*?)\[/quote\]#si", "".$start_html."\\2".$end_html."", $text);
	return $text;
}


function format_urls($s)
{
	return preg_replace(
    	"/(\A|[^=\]'\"a-zA-Z0-9])((http|ftp|https|ftps|irc):\/\/[^()<>\s]+)/i",
	    "\\1<a href=\"\\2\">\\2</a>", $s);
}

function encode_php($text) {
	$start_html = "<div align=\"center\"><div style=\"width: 85%; overflow: auto\">"
	."<table width=\"100%\" cellspacing=\"1\" cellpadding=\"3\" border=\"0\" align=\"center\" class=\"bgcolor4\">"
	."<tr bgcolor=\"F3E8FF\"><td colspan=\"2\"><font class=\"block-title\">PHP - Код</font></td></tr>"
	."<tr class=\"bgcolor1\"><td align=\"right\" class=\"code\" style=\"width: 5px; border-right: none\">{ZEILEN}</td><td>";
	$end_html = "</td></tr></table></div></div>";
	$match_count = preg_match_all("#\[php\](.*?)\[/php\]#si", $text, $matches);
    for ($mout = 0; $mout < $match_count; ++$mout) {
        $before_replace = $matches[1][$mout];
        $after_replace = $matches[1][$mout];
        $after_replace = trim ($after_replace);
		$after_replace = str_replace("&lt;", "<", $after_replace);
		$after_replace = str_replace("&gt;", ">", $after_replace);
		$after_replace = str_replace("&quot;", '"', $after_replace);
		$after_replace = preg_replace("/<br.*/i", "", $after_replace);
		$after_replace = (substr($after_replace, 0, 5 ) != "<?php") ? "<?php\n".$after_replace."" : "".$after_replace."";
		$after_replace = (substr($after_replace, -2 ) != "?>") ? "".$after_replace."\n?>" : "".$after_replace."";
        ob_start ();
        highlight_string ($after_replace);
        $after_replace = ob_get_contents ();
        ob_end_clean ();
		$zeilen_array = explode("<br />", $after_replace);
        $j = 1;
        $zeilen = "";
      foreach ($zeilen_array as $str) {
        $zeilen .= "".$j."<br />";
        ++$j;
      }
		$after_replace = str_replace("\n", "", $after_replace);
		$after_replace = str_replace("&amp;", "&", $after_replace);
		$after_replace = str_replace("  ", "&nbsp; ", $after_replace);
		$after_replace = str_replace("  ", " &nbsp;", $after_replace);
		$after_replace = str_replace("\t", "&nbsp; &nbsp;", $after_replace);
		$after_replace = preg_replace("/^ {1}/m", "&nbsp;", $after_replace);
		$str_to_match = "[php]".$before_replace."[/php]";
		$replace = str_replace("{ZEILEN}", $zeilen, $start_html);
      $replace .= $after_replace;
      $replace .= $end_html;
      $text = str_replace ($str_to_match, $replace, $text);
    }
	$text = str_replace("[php]", $start_html, $text);
	$text = str_replace("[/php]", $end_html, $text);
    return $text;
}

if (!function_exists("htmlspecialchars_uni")) {
	function htmlspecialchars_uni($message) {
		$message = preg_replace("#&(?!\#[0-9]+;)#si", "&amp;", $message); // Fix & but allow unicode
		$message = str_replace("<","&lt;",$message);
		$message = str_replace(">","&gt;",$message);
		$message = str_replace("\"","&quot;",$message);
		$message = str_replace("  ", "&nbsp;&nbsp;", $message);
		return $message;
	}
}

// Format code
function encode_code($text) {
	$start_html = "<div align=\"center\"><div style=\"width: 85%; overflow: auto\">"
	."<table width=\"100%\" cellspacing=\"1\" cellpadding=\"3\" border=\"0\" align=\"center\" class=\"bgcolor4\">"
	."<tr bgcolor=\"E5EFFF\"><td colspan=\"2\"><font class=\"block-title\">Код</font></td></tr>"
	."<tr class=\"bgcolor1\"><td align=\"right\" class=\"code\" style=\"width: 5px; border-right: none\">{ZEILEN}</td><td class=\"code\">";
	$end_html = "</td></tr></table></div></div>";
	$match_count = preg_match_all("#\[code\](.*?)\[/code\]#si", $text, $matches);
    for ($mout = 0; $mout < $match_count; ++$mout) {
      $before_replace = $matches[1][$mout];
      $after_replace = $matches[1][$mout];
      $after_replace = trim ($after_replace);
      $zeilen_array = explode ("<br />", $after_replace);
      $j = 1;
      $zeilen = "";
      foreach ($zeilen_array as $str) {
        $zeilen .= "".$j."<br />";
        ++$j;
      }
      $after_replace = str_replace ("", "", $after_replace);
      $after_replace = str_replace ("&amp;", "&", $after_replace);
      $after_replace = str_replace ("", "&nbsp; ", $after_replace);
      $after_replace = str_replace ("", " &nbsp;", $after_replace);
      $after_replace = str_replace ("", "&nbsp; &nbsp;", $after_replace);
      $after_replace = preg_replace ("/^ {1}/m", "&nbsp;", $after_replace);
      $str_to_match = "[code]".$before_replace."[/code]";
      $replace = str_replace ("{ZEILEN}", $zeilen, $start_html);
      $replace .= $after_replace;
      $replace .= $end_html;
      $text = str_replace ($str_to_match, $replace, $text);
    }

    $text = str_replace ("[code]", $start_html, $text);
    $text = str_replace ("[/code]", $end_html, $text);
    return $text;
}


//Список категорий
/*
function get_categories() {
	global $db , $memcached;
	 //Получаем список категорий
	if (false === ($cache_result = $memcached->get('upload_categories')))
	{
		$categories_who = array();
		$cats = $db->query("SELECT c.* , COUNT(t.id)  AS count , SUM(t.size) AS size
					FROM categories AS c
					LEFT JOIN torrents AS t ON t.id_category = c.id
					GROUP BY c.id");
		while($arr = $db->get_row() )
			$categories_who[] = $arr;

		$memcached->set('upload_categories', $categories_who , 0, (24*60*60));
		$cache_result = $categories_who;
	}

	return $cache_result;
}
*/


//Получение списка категорий
function categories_array($id = 0) {
	global $db;

	$memcached = (function_exists('lt_cache') ? lt_cache() : null);

	$id = (int) $id;
	$cacheKey = 'categories_' . $id;
	$categories_who = (is_object($memcached) ? $memcached->get($cacheKey) : false);

	if ($categories_who === false) {
		$sql = $db->query("
			SELECT c.*, COUNT(t.id) AS count, SUM(t.size) AS size
			FROM categories AS c
			LEFT JOIN torrents AS t ON t.id_category = c.id
			" . ($id ? "WHERE c.id = " . $id : "") . "
			GROUP BY c.id
			ORDER BY c.id DESC
		");

		if ($id) {
			$categories_who = $db->get_row($sql);
			if (!$categories_who) {
				$categories_who = array();
			}
		} else {
			$categories_who = array();
			while ($arr = $db->get_row($sql)) {
				$categories_who[] = $arr;
			}
		}

		if (is_object($memcached)) {
			$memcached->set($cacheKey, $categories_who, 0, 1000);
		}
	}

	return $categories_who;
}

//Извлечение папок
function get_list_dir($dir , $nameSelect = "" , $elemSelected = "" ) {
		$open = opendir($dir);
		$list .=  '<select name="'.($nameSelect == "" ? $dir : $nameSelect).'">';
		while(false !== ($filename = readdir($open))){
			if(filetype($dir."/".$filename) == 'dir') {
				if ($filename != "." && $filename != "..") {
					$list .=  "<option value='".$filename."'   ".(!empty($elemSelected) && $elemSelected == $filename ? "selected"  : "" ).">".$filename."</option>";
				}
			}
		}
		$list .=  '</select>';

		return $list;

}


//Получение массива языков
function get_languages() {
	$open = opendir($_SERVER['DOCUMENT_ROOT'].'/languages');
	$array = array();
	while ($file = readdir($open)) {
		if (is_language($file) && $file != "." && $file != "..") {
			$array[] = $file;
		}
	}
	closedir($open);
	sort($array);
	return $array;
}

//Получение списка языков
function get_select_language() {
	global $USER , $config;

	if($_COOKIE['language']) {
		$select_language = $_COOKIE['language'];
	} else {
		$select_language = $config['lang'];
	}

	$languages = get_languages();

	$select .= '<form action="language.php" method="post">';
	$select .= '<select name="language">';
	foreach ($languages as $language) {
		$select .= '<option value="'.$language.'" '.($select_language == $language ? 'selected' : '').'>'.$language.'</option>';
	}
	$select .= '<input type="submit" value="OK">';
	$select .= '</select></form>';
	return $select;
}


//Проверка языка
function is_language($language = "") {
	return file_exists($_SERVER['DOCUMENT_ROOT']."/languages/$language/site.php");
}


//Информация о правах класса
function get_priv_info($class) {
	global $memcached , $db;

	$class = (int)$class;

	//Определяем права пользовател
	if (false === ($row = $memcached->get('priv_'.$class)))
	{
		$row = $db->super_query("SELECT * FROM priv WHERE id=".$class);
		$memcached->set('priv_'.$class , $row , 0, 1000);
	}

	if ($row) {
		return $row;
	}

	if (false === ($row = $memcached->get('priv_guest_defaults'))) {
		$row = array();
		$sql = $db->query("SHOW COLUMNS FROM priv");
		while ($column = $db->get_row($sql)) {
			$row[$column['Field']] = 0;
		}
		$db->free($sql);

		$row['id'] = 0;
		$row['NAME'] = 'Гость';
		$row['COLOR'] = '000000';

		$memcached->set('priv_guest_defaults', $row, 0, 1000);
	}

	return $row;
}

//Получение списка классов
function get_classes_list() {
	global $memcached , $db;

	//Определяем права пользовател
	if (false === ($result = $memcached->get('priv_all'))) {
		$db->query("SELECT * FROM priv WHERE id > 0 ");
		$result = array();
		while($row = $db->get_row() ) {
			$result[] = $row;
		}
		$memcached->set('priv_all' , $result , 0, 300);
	}
	return $result;
}


function is_valid_id($id) {
  return is_numeric($id) && ($id > 0) && (floor($id) == $id);
}

/*
 * 	Проверка друзей
*/
function check_friend($id_user  , $id_friend) {
	global $db;

	//Запрос к friends
	$check = $db->super_query("SELECT COUNT(*) AS count , id FROM friends  WHERE  status='yes' AND friendid=".$id_friend."  AND  userid=".$id_user." GROUP BY id ");
	return $check;
}

?>
