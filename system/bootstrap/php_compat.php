<?php

if (function_exists('mysqli_report')) {
	mysqli_report(MYSQLI_REPORT_OFF);
}

if (!defined('LT_ROOT_PATH')) {
	$ltRootPath = realpath(dirname(__DIR__, 2));
	if ($ltRootPath === false) {
		$ltRootPath = dirname(__DIR__, 2);
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
defined('LT_SYSTEM_PATH') || define('LT_SYSTEM_PATH', LT_ROOT_PATH.'/system');
defined('LT_TEMPLATES_PATH') || define('LT_TEMPLATES_PATH', LT_ROOT_PATH.'/templates');
defined('LT_ADMIN_PATH') || define('LT_ADMIN_PATH', LT_APP_PATH.'/admin');
defined('LT_API_PATH') || define('LT_API_PATH', LT_APP_PATH.'/api');

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

if (!function_exists('lt_runtime_pick_path')) {
	function lt_runtime_pick_path($preferred, $legacy)
	{
		$preferred = lt_path_normalize($preferred);
		$legacy = lt_path_normalize($legacy);

		if (lt_path_ensure_dir($preferred)) {
			return $preferred;
		}
		if (lt_path_ensure_dir($legacy)) {
			return $legacy;
		}

		return ($preferred !== '' ? $preferred : $legacy);
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
			case 'cache':
				$base = lt_runtime_pick_path(LT_STORAGE_PATH.'/cache', LT_ROOT_PATH.'/cache');
				break;
			case 'logs':
				$base = lt_runtime_pick_path(LT_STORAGE_PATH.'/logs', LT_ROOT_PATH.'/logs');
				break;
			case 'tmp':
				$base = lt_runtime_pick_path(LT_STORAGE_PATH.'/tmp', LT_ROOT_PATH.'/tmp');
				break;
			case 'uploads':
				$base = lt_runtime_pick_path(LT_STORAGE_PATH.'/uploads', LT_ROOT_PATH.'/uploads');
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

if (!function_exists('lt_tmp_path')) {
	function lt_tmp_path($relative = '')
	{
		return lt_path('tmp', $relative);
	}
}

if (!function_exists('lt_uploads_path')) {
	function lt_uploads_path($relative = '')
	{
		return lt_path('uploads', $relative);
	}
}

if (!function_exists('lt_session_bootstrap')) {
	function lt_session_bootstrap() {
		lt_session_resume();
		lt_session_commit();
	}
}

if (!function_exists('lt_session_resume')) {
	function lt_session_resume() {
		if (session_status() !== PHP_SESSION_ACTIVE) {
			session_start();
		}
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
