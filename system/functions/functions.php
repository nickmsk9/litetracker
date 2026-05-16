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

require_once __DIR__ . '/functions.common.php';

//Информация о пользователе
function get_user_info($id) {
	global $db;

	//Если нету id
	if(!$id) {
		return false;
	}

	$cacheKey = lt_cache_key_user($id);
	$cacheNs  = lt_cache_key_user_ns();

	//Запрос к таблице users
	$row = lt_cache_get($cacheKey, $cacheNs);
	if ($row === false)
	{
		$sql = $db->query("SELECT * FROM users WHERE id = ".(int)$id);
		$row  = $db->get_row($sql);
		$db->free($sql);
		lt_cache_set($cacheKey, $row, rand(1500, 3000), $cacheNs);
	}

	return $row;
}

function lt_unread_messages_count($userId)
{
	global $db;

	$userId = (int) $userId;
	if ($userId <= 0 || !lt_table_exists('mail')) {
		return 0;
	}

	$row = $db->super_query("SELECT COUNT(*) AS c FROM mail WHERE id_user_in = ".$userId." AND delete_in = 0 AND reading = 0");

	return (int) ($row['c'] ?? 0);
}

function lt_sync_user_unread_messages($userId)
{
	global $db, $USER;

	$userId = (int) $userId;
	if ($userId <= 0 || !lt_table_exists('users')) {
		return 0;
	}

	$count = lt_unread_messages_count($userId);
	$isCurrentUser = (!empty($USER['id']) && (int) $USER['id'] === $userId);
	$currentStoredCount = ($isCurrentUser ? (int) ($USER['num_messages'] ?? -1) : null);
	if (!$isCurrentUser || $currentStoredCount !== $count) {
		$db->query("UPDATE users SET num_messages = ".$count." WHERE id = ".$userId);
		lt_cache_invalidate_user($userId);
	}
	if ($isCurrentUser) {
		$USER['num_messages'] = $count;
	}

	return $count;
}

function lt_table_exists($tableName, $refresh = false)
{
	global $db;
	static $cache = array();

	$tableName = lt_schema_identifier($tableName);
	if ($tableName === '') {
		return false;
	}

	$refresh = (bool) $refresh;

	if (!$refresh && array_key_exists($tableName, $cache)) {
		return $cache[$tableName];
	}

	$cacheKey = lt_schema_table_cache_key($tableName);
	$cached = (!$refresh ? lt_schema_cache_get($cacheKey) : false);
	if (!$refresh && is_array($cached) && array_key_exists('exists', $cached)) {
		$cache[$tableName] = (bool) $cached['exists'];
		return $cache[$tableName];
	}

	$sql = $db->query("SHOW TABLES LIKE '".$db->safesql($tableName)."'", 0);
	if ($sql === false) {
		$cache[$tableName] = false;
		return false;
	}

	$row = $db->get_row($sql);
	$db->free($sql);
	$cache[$tableName] = !empty($row);
	lt_schema_cache_set($cacheKey, array('exists' => $cache[$tableName]));

	return $cache[$tableName];
}

function lt_column_exists($tableName, $columnName, $refresh = false)
{
	global $db;
	static $cache = array();

	$tableName = lt_schema_identifier($tableName);
	$columnName = lt_schema_identifier($columnName);

	if ($tableName === '' || $columnName === '') {
		return false;
	}

	$refresh = (bool) $refresh;

	$key = $tableName.'.'.$columnName;
	if (!$refresh && array_key_exists($key, $cache)) {
		return $cache[$key];
	}

	if (!lt_table_exists($tableName, $refresh)) {
		$cache[$key] = false;
		return false;
	}

	$cacheKey = lt_schema_column_cache_key($tableName, $columnName);
	$cached = (!$refresh ? lt_schema_cache_get($cacheKey) : false);
	if (!$refresh && is_array($cached) && array_key_exists('exists', $cached)) {
		$cache[$key] = (bool) $cached['exists'];
		return $cache[$key];
	}

	$sql = $db->query("SHOW COLUMNS FROM `".$tableName."` LIKE '".$db->safesql($columnName)."'", 0);
	if ($sql === false) {
		$cache[$key] = false;
		return false;
	}

	$row = $db->get_row($sql);
	$db->free($sql);
	$cache[$key] = !empty($row['Field']);
	lt_schema_cache_set($cacheKey, array('exists' => $cache[$key]));

	return $cache[$key];
}

function lt_schema_identifier($value)
{
	$value = trim((string) $value);

	return (preg_match('~^[a-zA-Z0-9_]+$~', $value) ? $value : '');
}

function lt_schema_cache_ttl()
{
	return 6 * 60 * 60;
}

function lt_schema_table_cache_key($tableName)
{
	return 'schema:table:'.$tableName.':exists';
}

function lt_schema_column_cache_key($tableName, $columnName)
{
	return 'schema:column:'.$tableName.':'.$columnName.':exists';
}

function lt_schema_cache_get($key)
{
	return (function_exists('lt_cache_get') ? lt_cache_get($key, 'schema') : false);
}

function lt_schema_cache_set($key, $value)
{
	if (function_exists('lt_cache_set')) {
		lt_cache_set($key, $value, lt_schema_cache_ttl(), 'schema');
	}
}

function lt_schema_cache_delete($key)
{
	if (function_exists('lt_cache_delete')) {
		lt_cache_delete($key, 'schema');
	}
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
	$userRow = array();

	if (is_array($user)) {
		$userId = (int) ($user['id'] ?? 0);
		$userRow = $user;
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

	if (!$userRow) {
		$userRow = get_user_info($userId);
	}

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

	$onlineFrom = get_date_time(gmtime() - ($thresholdMinutes * 60));
	$row = $db->psuper_query("SELECT user_id FROM sessions WHERE user_id = ".$userId." AND last_access >= ? LIMIT 1", 's', [$onlineFrom]);
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
// Resolves a Vite entry-point to a hashed output URL.
// Falls back to the source path if the manifest doesn't exist yet.
function lt_asset_url($entry) {
    static $manifest = null;
    if ($manifest === null) {
        $manifestPath = dirname(__DIR__, 2) . '/public/dist/manifest.json';
        if (is_file($manifestPath)) {
            $decoded  = json_decode(file_get_contents($manifestPath), true);
            $manifest = is_array($decoded) ? $decoded : [];
        } else {
            $manifest = [];
        }
    }
    $key = ltrim($entry, '/');
    if (!empty($manifest[$key]['file'])) {
        return 'public/dist/' . $manifest[$key]['file'];
    }
    return $entry;
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

	// Resolve the active CSS theme for the current user (may differ from $tpl for PHP files)
	$GLOBALS['LITETRACKER_THEME_SLUG'] = (function_exists('lt_resolve_theme') ? lt_resolve_theme($USER) : $tpl);

	//Название сайа | Название страницы
	$sitename = $config['sitename'];
	$title = (empty($title) ? '' : $title);
	$header = '';


	//Формируем header
	$header .= '<script type="text/javascript" src="public/js/jquery.js"></script>' . "\n";
	$appBundle = lt_asset_url('src/app.js');
	$header .= '<script type="module" src="' . htmlspecialchars($appBundle, ENT_QUOTES, 'UTF-8') . '"></script>' . "\n";
	$header .= '<script type="text/javascript" src="/public/js/notifications.js?v='.(int) @filemtime('public/js/notifications.js').'" defer></script>' . "\n";
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

	lt_debug_render_panel();

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

function lt_debug_enabled()
{
	return ((defined('DEBUG') && DEBUG) || (defined('DEBUG_SQL') && DEBUG_SQL));
}

function lt_debug_panel_allowed()
{
	return (lt_debug_enabled() && function_exists('admin_dashboard_is_superadmin') && admin_dashboard_is_superadmin(($GLOBALS['USER'] ?? null), ($GLOBALS['PRIV'] ?? null)));
}

function lt_debug_format_bytes($bytes)
{
	$bytes = (float) $bytes;
	$units = array('B', 'KB', 'MB', 'GB');
	$unit = 0;

	while ($bytes >= 1024 && $unit < count($units) - 1) {
		$bytes /= 1024;
		$unit++;
	}

	return number_format($bytes, ($unit === 0 ? 0 : 2), '.', ' ').' '.$units[$unit];
}

function lt_debug_mask_text($value)
{
	$value = (string) $value;
	$value = preg_replace('/([a-z0-9._%+\-]+)@([a-z0-9.\-]+\.[a-z]{2,})/i', '[email masked]', $value);
	$value = preg_replace('/((?:passkey|password|email|session|cookie|csrf|token|id_password)=)([^&\s]+)/i', '$1[masked]', $value);
	$value = preg_replace('/\b[0-9a-f]{32}\b/i', '[hash32 masked]', $value);

	return $value;
}

function lt_debug_render_panel()
{
	if (!lt_debug_panel_allowed()) {
		return;
	}

	$db = ($GLOBALS['db'] ?? null);
	if (!is_object($db)) {
		return;
	}

	$cacheStats = function_exists('lt_cache_debug_stats') ? lt_cache_debug_stats() : array();
	$cacheStats += array(
		'hits' => 0,
		'misses' => 0,
		'sets' => 0,
		'deletes' => 0,
		'errors' => 0,
	);

	$pageUrl = (string) ($_SERVER['REQUEST_URI'] ?? 'CLI');
	$pageUrl = lt_debug_mask_text($pageUrl);
	$queryList = (array) ($db->query_list ?? array());
	$slowQueries = array();
	foreach ($queryList as $queryInfo) {
		if (!empty($queryInfo['slow']) || (float) ($queryInfo['time'] ?? 0) > 0.05) {
			$slowQueries[] = $queryInfo;
		}
	}

	echo '<div style="margin:24px auto 12px;max-width:1180px;padding:12px;border:1px solid #c8d3df;background:#f7fafc;color:#1f2933;font:12px/1.45 Arial, sans-serif;text-align:left;">';
	echo '<div style="font-weight:bold;margin-bottom:8px;">LiteTracker Debug Panel</div>';
	echo '<div>Page URL: <code>'.htmlspecialchars($pageUrl, ENT_QUOTES, 'UTF-8').'</code></div>';
	echo '<div>SQL queries count: <b>'.(int) ($db->query_num ?? count($queryList)).'</b></div>';
	echo '<div>Total SQL time: <b>'.number_format((float) ($db->MySQL_time_taken ?? 0), 6, '.', '').' sec</b></div>';
	echo '<div>Memory usage: <b>'.lt_debug_format_bytes(memory_get_usage(true)).'</b>; peak: <b>'.lt_debug_format_bytes(memory_get_peak_usage(true)).'</b></div>';
	echo '<div>Cache hits/misses/sets/deletes/errors: <b>'.(int) $cacheStats['hits'].'</b> / <b>'.(int) $cacheStats['misses'].'</b> / <b>'.(int) $cacheStats['sets'].'</b> / <b>'.(int) $cacheStats['deletes'].'</b> / <b>'.(int) $cacheStats['errors'].'</b></div>';

	if (!empty($db->sql_errors)) {
		echo '<div style="margin-top:8px;color:#991b1b;font-weight:bold;">SQL errors: '.count((array) $db->sql_errors).'</div>';
	}

	if ($slowQueries) {
		echo '<div style="margin-top:8px;color:#991b1b;font-weight:bold;">Slow queries &gt; 0.05 sec: '.count($slowQueries).'</div>';
	}

	if ($queryList) {
		echo '<details open style="margin-top:10px;"><summary style="cursor:pointer;font-weight:bold;">SQL queries</summary>';
		echo '<ol style="margin:8px 0 0 22px;padding:0;">';
		foreach ($queryList as $queryInfo) {
			$time = (float) ($queryInfo['time'] ?? 0);
			$isSlow = (!empty($queryInfo['slow']) || $time > 0.05);
			$error = (string) ($queryInfo['error'] ?? '');
			$itemStyle = $isSlow ? 'background:#fff1f2;border-left:3px solid #e11d48;padding:4px 6px;margin-bottom:6px;' : 'padding:4px 6px;margin-bottom:6px;';
			if ($error !== '') {
				$itemStyle = 'background:#fef2f2;border-left:3px solid #991b1b;padding:4px 6px;margin-bottom:6px;';
			}

			echo '<li style="'.$itemStyle.'">';
			echo '<span style="font-weight:bold;">'.number_format($time, 6, '.', '').' sec</span>';
			if ($isSlow) {
				echo ' <span style="color:#991b1b;font-weight:bold;">slow</span>';
			}
			if ($error !== '') {
				echo ' <span style="color:#991b1b;font-weight:bold;">SQL error '.(int) ($queryInfo['error_num'] ?? 0).'</span>';
			}
			echo '<pre style="white-space:pre-wrap;word-break:break-word;margin:4px 0 0;font:12px/1.35 Consolas, monospace;">'.htmlspecialchars((string) ($queryInfo['query'] ?? ''), ENT_QUOTES, 'UTF-8').'</pre>';
			if ($error !== '') {
				echo '<div style="color:#991b1b;">'.htmlspecialchars($error, ENT_QUOTES, 'UTF-8').'</div>';
			}
			echo '</li>';
		}
		echo '</ol></details>';
	}

	echo '</div>';
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
       $updateset[] = 'last_access = "' . get_date_time() . '"';
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

function user_session()
{
	global $USER , $config , $db;

	//Определяем session_id
	$session_id = session_id();
	$user_id    = ($USER ? $USER['id'] : '-1');
	$last_access = get_date_time(time());
	$ip          = ip2long_db($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
	$user_agent  = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
	$php_self    = (string) ($_SERVER['PHP_SELF'] ?? '');

	$throttleKey = lt_cache_key_session_touch(md5($session_id.'|'.$user_id));
	$throttleNs  = lt_cache_key_sessions_ns();
	if (false !== lt_cache_get($throttleKey, $throttleNs)) {
		return;
	}

	$db->pquery(
		"INSERT INTO sessions (session_id, user_id, last_access, ip, user_agent, php_self)
		 VALUES (?, ?, ?, ?, ?, ?)
		 ON DUPLICATE KEY UPDATE
		   user_id=VALUES(user_id), last_access=VALUES(last_access),
		   ip=VALUES(ip), user_agent=VALUES(user_agent), php_self=VALUES(php_self)",
		'ssssss',
		[$session_id, (string) $user_id, $last_access, (string) $ip, $user_agent, $php_self]
	);

	lt_cache_set($throttleKey, '1', 60, $throttleNs);

	return;
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
function get_user_color($class, $username, $user = null) {
	$priv = get_priv_info($class);
	$isEmojiName = (!empty($priv['EDIT_PRIV']));
	$nameHtml = ($isEmojiName ? '<span class="lt-emoji-font">'.$username.'</span>' : $username);

	return "<font title=\"".htmlspecialchars((string) ($priv['NAME'] ?? ''), ENT_QUOTES, 'UTF-8')."\" style=\"color:#".htmlspecialchars((string) ($priv['COLOR'] ?? '000000'), ENT_QUOTES, 'UTF-8')."\">" . $nameHtml . "</font>";
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
	return htmlspecialchars((string) ($priv['NAME'] ?? ''), ENT_QUOTES, 'UTF-8');
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
//Формирование секретного кода
function mksecret($length = 32) {
	$set = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
	$setLen = strlen($set);
	$str = '';
	for ($i = 0; $i < $length; $i++) {
		$str .= $set[random_int(0, $setLen - 1)];
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

	$reencoded = mb_convert_encoding($value, 'Windows-1252', 'UTF-8');
	if (!is_string($reencoded) || ($reencoded === '' && $value !== '')) {
		return $value;
	}

	$fixed = mb_convert_encoding($reencoded, 'UTF-8', 'UTF-8');
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
		$_SESSION['lt_csrf'][$scope] = bin2hex(random_bytes(16));
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
	global $config;

   $subnet = explode('.', getip());
	$subnet[2] = $subnet[3] = 0;
	$subnet = implode('.', $subnet); // 255.255.0.0

	//Очищаем старые cookies
	logout_cookie();
	//Добавляем cookies
	lt_set_cookie(COOKIE_ID, $id, $expires, true, 'Lax');
	lt_set_cookie(COOKIE_PASSWORD, md5($password_hash.COOKIE_SALT.$subnet), $expires, true, 'Lax');

	lt_cache_invalidate_user($id);
}


//Удаление cookies
function logout_cookie() {
	global $USER , $config;

	$expires = time() - 3600;
	lt_set_cookie(COOKIE_ID, '', $expires, true, 'Lax');
	lt_set_cookie(COOKIE_PASSWORD, '', $expires, true, 'Lax');
	unset($_COOKIE[COOKIE_ID], $_COOKIE[COOKIE_PASSWORD]);
	if($USER && isset($USER['id'])) {
		lt_cache_invalidate_user($USER['id']);
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
	global $db;
	$ret = array();

	$cacheKey = lt_cache_key_tags_genre($cat);
	$cacheNs  = lt_cache_key_tags_ns();

	$ret = lt_cache_get($cacheKey, $cacheNs);
	if ($ret === false)
	{
		$cache = array();
		$res = $db->query("SELECT id, name, howmuch FROM tags WHERE category=".(int)$cat." ORDER BY name ASC") or sqlerr(__FILE__ , __LINE__);
		while ($row = $db->get_row() )
			$cache[] = $row;

		lt_cache_set($cacheKey, $cache, 500, $cacheNs);
		$ret = $cache;
	}

	return $ret;
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
		if ($pages <= 1) {
			$page = 0;
			return array('', '', ' LIMIT 0, ' . (int) $rpp);
		}

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
	$explode = explode(' '  , (string) $date);



	//////////////////////////////////////////////
	//$explode_date['0'] - год
	//$explode_date['1'] - месяц
	//$explode_date['2'] - день
	//////////////////////////////////////////////
	//Разбиваем дату на гггг-мм-дд
	$explode_date = explode('-' , (string) ($explode[0] ?? '0000-01-01'));

	//Удаляем нуль перез числом
	$explode_date[2] = ltrim((string) ($explode_date[2] ?? '0'), '0') ?: '0';



	//////////////////////////////////////////////
	//$explode_time['0'] - час
	//$explode_time['1'] - минута
	//$explode_time['2'] - секунда
	//////////////////////////////////////////////
	//Разбиваем время на чч:мм:cc
	$explode_time = explode(':' , (string) ($explode[1] ?? '00:00:00'));

	$day   = (string) ($explode_date[2] ?? '0');
	$month = (string) ($explode_date[1] ?? '01');
	$year  = (string) ($explode_date[0] ?? '0000');
	$hour  = (string) ($explode_time[0] ?? '00');
	$min   = (string) ($explode_time[1] ?? '00');

	return ($day == date('d') ? $language['month_13'] : $day .' '.($mounth[$month] ?? '')).' '. ($day != date('d') ? $year.' года' : '' ) . ' , '.$hour.':'.$min;
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

//Нагрузка на сервер
function get_server_load() {
	global  $phpver;
	if (strtolower(substr(PHP_OS, 0, 3)) === 'win') {
		return 0;
	} elseif (file_exists("/proc/loadavg")) {
		$load = file_get_contents("/proc/loadavg");
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


//Отправка локального сообщения
function send_msg($name = ''  , $text = '' , $user_in = 0 ,  $user_out = 0 ) {
	global $memcached , $db;

	$user_in = (int) $user_in;
	if(!$user_in) {
		return 0;
	}

	if(empty($name) ) {
		$name = 'Re:';
	}

	if(empty($text) ) {
		return 0;
	}
	$user_out = (int) $user_out;
	$db->pquery("INSERT INTO mail(name, text, id_user_in, id_user_out, date, delete_in, delete_out) VALUES (?, ?, ".$user_in.", ".$user_out.", NOW(), 0, 0)", 'ss', [$name, $text]);
	$db->query("UPDATE users SET num_messages=(num_messages+1) WHERE id=".$user_in);
	lt_cache_invalidate_user($user_in);
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
	$bb[] = "#\[url\]((?:https?|ftp)://([\w\#$%&~/.\-;:=,?@\]+]+|\[(?!url=))*?)\[/url\]#is";
	$html[] = "<a href=\"\\1\" title=\"\\1\">\\1</a>";
	$bb[] = "#\[url\]((www|ftp)\.([\w\#$%&~/.\-;:=,?@\]+]+|\[(?!url=))*?)\[/url\]#is";
	$html[] = "<a href=\"http://\\1\" title=\"\\1\">\\1</a>";
	$bb[] = "#\[url=((?:https?|ftp)://[\w\#$%&~/.\-;:=,?@\[\]+]*?)\]([^?\n\r\t].*?)\[/url\]#is";
	$html[] = "<a href=\"\\1\" title=\"\\1\">\\2</a>";
	$bb[] = "#\[url=((www|ftp)\.[\w\#$%&~/.\-;:=,?@\[\]+]*?)\]([^?\n\r\t].*?)\[/url\]#is";
	$html[] = "<a href=\"http://\\1\" title=\"\\1\">\\3</a>";
	$bb[] = "/\[url=((?:https?|ftp|mailto):[^()<>\s\"']+?)\]([\s\S]+?)\[\/url\]/i";
	$html[] = "<a href=\"\\1\">\\2</a>";
	$bb[] = "/\[url\]((?:https?|ftp):[^()<>\s\"']+?)\[\/url\]/i";
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
$s = preg_replace_callback("/\[youtube\]([\s\S]+?)\[\/youtube\]/i", function ($m) {
	$url = trim(htmlspecialchars_decode($m[1], ENT_QUOTES));
	$url = str_replace("watch?v=", "v/", $url);
	if (!preg_match('#^https?://(?:www\.)?youtube\.com/v/[A-Za-z0-9_\-]{11}(?:[?&][^\s\'"<>]*)?$#i', $url)) {
		return '';
	}
	$safe = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
	return "<object width=\"640\" height=\"505\"><param name=\"movie\" value=\"" . $safe . "&amp;hl=ru&amp;fs=1&amp;\"></param><param name=\"allowFullScreen\" value=\"true\"></param><param name=\"allowscriptaccess\" value=\"always\"></param><embed src=\"" . $safe . "&amp;hl=ru&amp;fs=1&amp;\" type=\"application/x-shockwave-flash\" allowscriptaccess=\"always\" allowfullscreen=\"true\" width=\"640\" height=\"505\"></embed></object>";
}, $s);
///////////////////////////////////end tag youtube

/////////////////////////////////Tag [rutube][/rutube]
$s = preg_replace_callback("/\[rutube\]([\s\S]+?)\[\/rutube\]/i", function ($m) {
	$url = trim(htmlspecialchars_decode($m[1], ENT_QUOTES));
	$url = preg_replace("#http://rutube\.ru/tracks/([0-9]+)\.html\?v=#", "http://video.rutube.ru/", $url);
	if (!preg_match('#^https?://(?:video\.)?rutube\.ru/[A-Za-z0-9/_\-\.]+$#i', $url)) {
		return '';
	}
	$safe = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
	return "<object width=\"640\" height=\"505\"><param name=\"movie\" value=\"" . $safe . "\"></param><param name=\"allowFullScreen\" value=\"true\"></param><param name=\"allowscriptaccess\" value=\"always\"></param><embed src=\"" . $safe . "\" type=\"application/x-shockwave-flash\" allowscriptaccess=\"always\" allowfullscreen=\"true\" width=\"640\" height=\"505\"></embed></object>";
}, $s);
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

	$id = (int) $id;
	$cacheKey = lt_cache_key_cats($id);
	$cacheNs  = lt_cache_key_cats_ns();
	$categories_who = lt_cache_get($cacheKey, $cacheNs);

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

		lt_cache_set($cacheKey, $categories_who, 3600, $cacheNs);
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
//Получение списка классов
function get_classes_list() {
	global $db;

	$cacheKey = lt_cache_key_priv_all();
	$cacheNs  = lt_cache_key_priv_ns();

	//Определяем права пользовател
	$result = lt_cache_get($cacheKey, $cacheNs);
	if ($result === false) {
		$db->query("SELECT * FROM priv WHERE id > 0 ");
		$result = array();
		while($row = $db->get_row() ) {
			$result[] = $row;
		}
		lt_cache_set($cacheKey, $result, 300, $cacheNs);
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
