<?php

if (function_exists('mysqli_report')) {
	mysqli_report(MYSQLI_REPORT_OFF);
}

if (!defined('LT_ROOT_PATH')) {
	$ltRootPath = realpath(dirname(__DIR__, 3));
	if ($ltRootPath === false) {
		$ltRootPath = dirname(__DIR__, 3);
	}
	$ltRootPath = rtrim(str_replace('\\', '/', (string) $ltRootPath), '/');
	if ($ltRootPath === '') {
		$ltRootPath = '/';
	}
	define('LT_ROOT_PATH', $ltRootPath);
}

defined('LT_APP_PATH') || define('LT_APP_PATH', LT_ROOT_PATH.'/app');
defined('LT_PUBLIC_PATH') || define('LT_PUBLIC_PATH', LT_ROOT_PATH.'/public');
defined('LT_STORAGE_PATH') || define('LT_STORAGE_PATH', LT_ROOT_PATH.'/storage');
defined('LT_DATABASE_PATH') || define('LT_DATABASE_PATH', LT_ROOT_PATH.'/database');
defined('LT_DOCS_PATH') || define('LT_DOCS_PATH', LT_ROOT_PATH.'/docs');
defined('LT_SYSTEM_PATH') || define('LT_SYSTEM_PATH', LT_APP_PATH.'/system');
defined('LT_TEMPLATES_PATH') || define('LT_TEMPLATES_PATH', LT_APP_PATH.'/templates');
defined('LT_ADMIN_PATH') || define('LT_ADMIN_PATH', LT_APP_PATH.'/admin');
defined('LT_API_PATH') || define('LT_API_PATH', LT_APP_PATH.'/api');
defined('LT_MODULES_PATH') || define('LT_MODULES_PATH', LT_APP_PATH.'/modules');
defined('LT_LANGUAGES_PATH') || define('LT_LANGUAGES_PATH', LT_APP_PATH.'/languages');

if (!function_exists('lt_path_normalize')) {
	function lt_path_normalize($path)
	{
		$path = str_replace('\\', '/', (string) $path);
		$path = preg_replace('~/+~', '/', $path);

		if ($path === '' || $path === null) {
			return '';
		}

		if ($path === '/') {
			return '/';
		}

		return rtrim($path, '/');
	}
}

if (!function_exists('lt_path_safe_relative')) {
	function lt_path_safe_relative($relative)
	{
		$relative = str_replace('\\', '/', (string) $relative);
		$relative = ltrim($relative, '/');
		if ($relative === '') {
			return '';
		}

		$parts = array();
		foreach (explode('/', $relative) as $part) {
			$part = trim($part);
			if ($part === '' || $part === '.') {
				continue;
			}
			if ($part === '..') {
				return '';
			}
			$parts[] = $part;
		}

		return implode('/', $parts);
	}
}

if (!function_exists('lt_path_ensure_dir')) {
	function lt_path_ensure_dir($dir)
	{
		$dir = lt_path_normalize($dir);
		if ($dir === '') {
			return false;
		}
		if (is_dir($dir)) {
			return true;
		}

		@mkdir($dir, 0777, true);
		return is_dir($dir);
	}
}

if (!function_exists('lt_path')) {
	function lt_path($type, $relative = '')
	{
		$type = strtolower(trim((string) $type));
		$base = LT_ROOT_PATH;

		switch ($type) {
			case 'root':
				$base = LT_ROOT_PATH;
				break;
			case 'app':
				$base = LT_APP_PATH;
				break;
			case 'public':
				$base = LT_PUBLIC_PATH;
				break;
			case 'storage':
				$base = LT_STORAGE_PATH;
				lt_path_ensure_dir($base);
				break;
			case 'database':
				$base = LT_DATABASE_PATH;
				break;
			case 'docs':
				$base = LT_DOCS_PATH;
				break;
			case 'system':
				$base = LT_SYSTEM_PATH;
				break;
			case 'templates':
				$base = LT_TEMPLATES_PATH;
				break;
			case 'admin':
				$base = LT_ADMIN_PATH;
				break;
			case 'api':
				$base = LT_API_PATH;
				break;
			case 'modules':
				$base = LT_MODULES_PATH;
				break;
			case 'languages':
				$base = LT_LANGUAGES_PATH;
				break;
			case 'cache':
				$base = LT_STORAGE_PATH.'/cache';
				lt_path_ensure_dir($base);
				break;
			case 'logs':
				$base = LT_STORAGE_PATH.'/logs';
				lt_path_ensure_dir($base);
				break;
			case 'uploads':
				$base = LT_STORAGE_PATH.'/uploads';
				lt_path_ensure_dir($base);
				break;
		}

		$base = lt_path_normalize($base);
		$safeRelative = lt_path_safe_relative($relative);
		if ($safeRelative === '') {
			return $base;
		}

		return $base.'/'.$safeRelative;
	}
}

if (!function_exists('lt_modules_path')) {
	function lt_modules_path($relative = '')
	{
		$safeRelative = lt_path_safe_relative($relative);
		if ((string) $relative !== '' && $safeRelative === '') {
			return '';
		}
		$base = lt_path_normalize(LT_MODULES_PATH);
		return ($safeRelative === '' ? $base : $base.'/'.$safeRelative);
	}
}

if (!function_exists('lt_system_path')) {
	function lt_system_path($relative = '')
	{
		$safeRelative = lt_path_safe_relative($relative);
		if ((string) $relative !== '' && $safeRelative === '') {
			return '';
		}
		$base = lt_path_normalize(LT_SYSTEM_PATH);
		return ($safeRelative === '' ? $base : $base.'/'.$safeRelative);
	}
}

if (!function_exists('lt_languages_path')) {
	function lt_languages_path($relative = '')
	{
		$safeRelative = lt_path_safe_relative($relative);
		if ((string) $relative !== '' && $safeRelative === '') {
			return '';
		}
		$base = lt_path_normalize(LT_LANGUAGES_PATH);
		return ($safeRelative === '' ? $base : $base.'/'.$safeRelative);
	}
}

if (!function_exists('lt_storage_path')) {
	function lt_storage_path($relative = '')
	{
		return lt_path('storage', $relative);
	}
}

if (!function_exists('lt_cache_path')) {
	function lt_cache_path($relative = '')
	{
		return lt_path('cache', $relative);
	}
}

if (!function_exists('lt_logs_path')) {
	function lt_logs_path($relative = '')
	{
		return lt_path('logs', $relative);
	}
}

if (!function_exists('lt_uploads_path')) {
	function lt_uploads_path($relative = '')
	{
		return lt_path('uploads', $relative);
	}
}

if (!function_exists('lt_templates_path')) {
	/**
	 * Returns absolute path to a template file/dir inside app/templates.
	 *
	 * @param  string $relative  Relative path inside templates dir (e.g. 'default/head.php').
	 * @return string
	 */
	function lt_templates_path($relative = '')
	{
		$safeRelative = lt_path_safe_relative($relative);
		if ((string) $relative !== '' && $safeRelative === '') {
			return '';
		}

		$base = lt_path_normalize(LT_TEMPLATES_PATH);
		return ($safeRelative === '' ? $base : $base.'/'.$safeRelative);
	}
}

if (!function_exists('lt_session_bootstrap')) {
	function lt_session_bootstrap() {
		lt_session_resume();
		lt_session_commit();
	}
}

if (!function_exists('lt_session_is_https_request')) {
	function lt_session_is_https_request() {
		if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
			return true;
		}
		if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') {
			return true;
		}
		if (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_SSL']) === 'on') {
			return true;
		}
		return false;
	}
}

if (!function_exists('lt_session_resume')) {
	function lt_session_resume() {
		if (session_status() !== PHP_SESSION_ACTIVE) {
			ini_set('session.use_strict_mode', '1');
			ini_set('session.use_only_cookies', '1');
			ini_set('session.cookie_httponly', '1');
			ini_set('session.cookie_samesite', 'Lax');
			ini_set('session.cookie_secure', lt_session_is_https_request() ? '1' : '0');

			session_set_cookie_params(array(
				'lifetime' => 0,
				'path' => ini_get('session.cookie_path') ?: '/',
				'domain' => ini_get('session.cookie_domain') ?: '',
				'secure' => lt_session_is_https_request(),
				'httponly' => true,
				'samesite' => 'Lax',
			));
			session_start();
		}
	}
}

if (!function_exists('lt_session_regenerate')) {
	function lt_session_regenerate($deleteOldSession = true) {
		lt_session_resume();
		session_regenerate_id((bool) $deleteOldSession);
		lt_session_commit();
	}
}

if (!function_exists('lt_session_destroy_current')) {
	function lt_session_destroy_current() {
		lt_session_resume();
		$_SESSION = array();

		if (ini_get('session.use_cookies')) {
			$params = session_get_cookie_params();
			setcookie(session_name(), '', array(
				'expires' => time() - 3600,
				'path' => (string) ($params['path'] ?? '/'),
				'domain' => (string) ($params['domain'] ?? ''),
				'secure' => (bool) ($params['secure'] ?? lt_session_is_https_request()),
				'httponly' => true,
				'samesite' => (string) ($params['samesite'] ?? 'Lax'),
			));
		}

		session_destroy();
	}
}

if (!function_exists('lt_session_commit')) {
	function lt_session_commit() {
		if (session_status() === PHP_SESSION_ACTIVE) {
			session_write_close();
		}
	}
}

if (!function_exists('lt_lock_dir')) {
	function lt_lock_dir() {
		$dir = lt_cache_path('locks');

		lt_path_ensure_dir($dir);

		return $dir;
	}
}

if (!function_exists('lt_lock_acquire')) {
	function lt_lock_acquire($name) {
		$name = preg_replace('~[^a-z0-9_.-]+~i', '_', (string) $name);
		if ($name === '') {
			return false;
		}

		$path = lt_lock_dir().'/'.$name.'.lock';
		$handle = fopen($path, 'c');
		if (!is_resource($handle)) {
			return false;
		}

		if (!flock($handle, LOCK_EX | LOCK_NB)) {
			fclose($handle);
			return false;
		}

		ftruncate($handle, 0);
		fwrite($handle, (string) getmypid());
		fflush($handle);

		return $handle;
	}
}

if (!function_exists('lt_lock_release')) {
	function lt_lock_release($handle) {
		if (!is_resource($handle)) {
			return;
		}

		flock($handle, LOCK_UN);
		fclose($handle);
	}
}

if (!function_exists('ip2long_db')) {
	function ip2long_db($ip) {
		$long = ip2long($ip);

		if ($long === false) {
			return 0;
		}

		return unpack('l', pack('L', $long))[1];
	}
}
