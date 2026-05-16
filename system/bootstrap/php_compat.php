<?php

if (function_exists('mysqli_report')) {
	mysqli_report(MYSQLI_REPORT_OFF);
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
		$dir = dirname(__DIR__).'/cache/locks';

		if (!is_dir($dir)) {
			mkdir($dir, 0777, true);
		}

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
