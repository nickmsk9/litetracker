<?php
/*
===================================================================
LiteTracker Source
===================================================================
Общие функции для web и announce
===================================================================
*/

function gzip() {
	global $config;
	static $already_loaded;

	if ($already_loaded) {
		return;
	}

	$gzipEnabled = !isset($config['gzip']) || !empty($config['gzip']);
	if (
		extension_loaded('zlib') &&
		ini_get('zlib.output_compression') != '1' &&
		ini_get('output_handler') != 'ob_gzhandler' &&
		$gzipEnabled
	) {
		ob_start('ob_gzhandler');
	} else {
		ob_start();
	}

	$already_loaded = true;
}

function lt_is_public_ipv4($ip)
{
	$ip = trim((string) $ip);
	if ($ip === '') {
		return false;
	}

	return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
}

// IP address with trusted proxy headers support.
function getip()
{
	if (isset($_SERVER)) {
		$forwarded = (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '');
		if ($forwarded !== '') {
			$parts = explode(',', $forwarded);
			$candidate = trim((string) ($parts[0] ?? ''));
			if (lt_is_public_ipv4($candidate)) {
				return $candidate;
			}
		}

		$clientIp = (string) ($_SERVER['HTTP_CLIENT_IP'] ?? '');
		if (lt_is_public_ipv4($clientIp)) {
			return $clientIp;
		}

		$remote = (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
	} else {
		$forwarded = (string) getenv('HTTP_X_FORWARDED_FOR');
		if ($forwarded !== '') {
			$parts = explode(',', $forwarded);
			$candidate = trim((string) ($parts[0] ?? ''));
			if (lt_is_public_ipv4($candidate)) {
				return $candidate;
			}
		}

		$clientIp = (string) getenv('HTTP_CLIENT_IP');
		if (lt_is_public_ipv4($clientIp)) {
			return $clientIp;
		}

		$remote = (string) getenv('REMOTE_ADDR');
	}

	return (validip($remote) ? $remote : '0.0.0.0');
}

function validip($ip) {
	$ip = trim((string) $ip);
	if ($ip === '') {
		return false;
	}

	return (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false);
}

// Преобразуем размер файла
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

// Определяем ратио
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

// Информация о правах класса
function get_priv_info($class) {
	global $memcached , $db;

	$class = (int)$class;
	$row = false;
	$cacheKey = 'priv_'.$class;

	if (is_object($memcached)) {
		$row = $memcached->get($cacheKey);
	}

	if ($row === false || empty($row['id'])) {
		$row = $db->super_query("SELECT * FROM priv WHERE id=".$class);
		if (is_object($memcached)) {
			$memcached->set($cacheKey, $row, 0, 1000);
		}
	}

	if ($row) {
		return $row;
	}

	if (is_object($memcached)) {
		$row = $memcached->get('priv_guest_defaults');
	} else {
		$row = false;
	}

	if ($row === false) {
		$row = array();
		$sql = $db->query("SHOW COLUMNS FROM priv");
		while ($column = $db->get_row($sql)) {
			$row[$column['Field']] = 0;
		}
		$db->free($sql);

		$row['id'] = 0;
		$row['NAME'] = 'Гость';
		$row['COLOR'] = '000000';

		if (is_object($memcached)) {
			$memcached->set('priv_guest_defaults', $row, 0, 1000);
		}
	}

	return $row;
}
