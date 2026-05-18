<?php
/*
===================================================================
LiteTracker Source
===================================================================
Назначение: Environment-aware MySQL конфиг
===================================================================
*/

if (!function_exists('lt_env')) {
	function lt_env($key, $default = null)
	{
		$value = getenv($key);
		return ($value === false || $value === null || $value === '' ? $default : $value);
	}
}

if (!function_exists('lt_runtime_environment')) {
	function lt_runtime_environment()
	{
		$runtime = strtolower(trim((string) lt_env('LITETRACKER_RUNTIME', '')));
		if (in_array($runtime, array('docker', 'local', 'production'), true)) {
			return $runtime;
		}
		if (is_file('/.dockerenv')) {
			return 'docker';
		}
		return 'unknown';
	}
}

$ltMysqlRuntime = lt_runtime_environment();
$ltMysqlDefaultHost = ($ltMysqlRuntime === 'docker' ? 'db' : '127.0.0.1');

$mysql = array(
	'host' => (string) lt_env('LITETRACKER_DB_HOST', $ltMysqlDefaultHost),
	'port' => (int) lt_env('LITETRACKER_DB_PORT', 3306),
	'user' => (string) lt_env('LITETRACKER_DB_USER', 'root'),
	'password' => (string) lt_env('LITETRACKER_DB_PASSWORD', ''),
	'db' => (string) lt_env('LITETRACKER_DB_NAME', 'lite'),
	'charset' => (string) lt_env('LITETRACKER_DB_CHARSET', 'utf8mb4'),
	'connect_timeout' => max(1, (int) lt_env('LITETRACKER_DB_CONNECT_TIMEOUT', 5)),
	'timezone' => (string) lt_env('LITETRACKER_DB_TIMEZONE', '+03:00'),
	'strict' => (int) in_array(strtolower((string) lt_env('LITETRACKER_DB_STRICT', '0')), array('1', 'true', 'yes', 'on'), true),
);

if ($mysql['port'] <= 0) {
	$mysql['port'] = 3306;
}
if ($mysql['charset'] === '') {
	$mysql['charset'] = 'utf8mb4';
}
if ($mysql['timezone'] === '') {
	$mysql['timezone'] = '+03:00';
}

if (!defined('DBHOST')) {
	define('DBHOST', $mysql['host']);
}
if (!defined('DBPORT')) {
	define('DBPORT', $mysql['port']);
}
if (!defined('DBUSER')) {
	define('DBUSER', $mysql['user']);
}
if (!defined('DBPASS')) {
	define('DBPASS', $mysql['password']);
}
if (!defined('DBNAME')) {
	define('DBNAME', $mysql['db']);
}
?>
