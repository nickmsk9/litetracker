<?php

if (function_exists('mysqli_report')) {
	mysqli_report(MYSQLI_REPORT_OFF);
}

if (!function_exists('get_magic_quotes_gpc')) {
	function get_magic_quotes_gpc() {
		return false;
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

if (!function_exists('each')) {
	function each(&$array) {
		if (!is_array($array)) {
			return false;
		}

		$key = key($array);
		if ($key === null) {
			return false;
		}

		$value = current($array);
		next($array);

		return array(
			1 => $value,
			'value' => $value,
			0 => $key,
			'key' => $key,
		);
	}
}

if (!defined('MYSQL_ASSOC') && defined('MYSQLI_ASSOC')) {
	define('MYSQL_ASSOC', MYSQLI_ASSOC);
}

if (!defined('MYSQL_NUM') && defined('MYSQLI_NUM')) {
	define('MYSQL_NUM', MYSQLI_NUM);
}

if (!defined('MYSQL_BOTH') && defined('MYSQLI_BOTH')) {
	define('MYSQL_BOTH', MYSQLI_BOTH);
}

if (!function_exists('mysql_connect')) {
	function mysql_compat_default_link($link = null) {
		if ($link instanceof mysqli) {
			return $link;
		}

		if (isset($GLOBALS['mysql_compat_default_link']) && $GLOBALS['mysql_compat_default_link'] instanceof mysqli) {
			return $GLOBALS['mysql_compat_default_link'];
		}

		return null;
	}

	function mysql_compat_parse_server($server) {
		$server = (string) $server;
		$host = $server;
		$port = (int) ini_get('mysqli.default_port');
		$socket = null;

		if ($port <= 0) {
			$port = 3306;
		}

		if ($host === '') {
			return array('localhost', $port, $socket);
		}

		if ($host[0] === '/') {
			return array('localhost', $port, $host);
		}

		if (strpos($host, ':') !== false && substr_count($host, ':') === 1) {
			list($parsedHost, $parsedPort) = explode(':', $host, 2);
			if ($parsedPort !== '' && ctype_digit($parsedPort)) {
				$host = $parsedHost;
				$port = (int) $parsedPort;
			}
		}

		return array($host, $port, $socket);
	}

	function mysql_connect($server = 'localhost', $username = null, $password = null, $new_link = false, $client_flags = 0) {
		list($host, $port, $socket) = mysql_compat_parse_server($server);
		$link = mysqli_init();

		if (!$link) {
			return false;
		}

		$connected = @mysqli_real_connect(
			$link,
			$host,
			(string) $username,
			(string) $password,
			null,
			$port,
			$socket,
			(int) $client_flags
		);

		if (!$connected) {
			return false;
		}

		$GLOBALS['mysql_compat_default_link'] = $link;

		return $link;
	}

	function mysql_select_db($database_name, $link = null) {
		$link = mysql_compat_default_link($link);
		return $link ? mysqli_select_db($link, $database_name) : false;
	}

	function mysql_get_server_info($link = null) {
		$link = mysql_compat_default_link($link);
		return $link ? mysqli_get_server_info($link) : false;
	}

	function mysql_query($query, $link = null) {
		$link = mysql_compat_default_link($link);
		return $link ? mysqli_query($link, $query) : false;
	}

	function mysql_error($link = null) {
		$link = mysql_compat_default_link($link);
		return $link ? mysqli_error($link) : mysqli_connect_error();
	}

	function mysql_errno($link = null) {
		$link = mysql_compat_default_link($link);
		return $link ? mysqli_errno($link) : mysqli_connect_errno();
	}

	function mysql_fetch_assoc($result) {
		return $result instanceof mysqli_result ? mysqli_fetch_assoc($result) : false;
	}

	function mysql_fetch_array($result, $result_type = MYSQL_BOTH) {
		return $result instanceof mysqli_result ? mysqli_fetch_array($result, $result_type) : false;
	}

	function mysql_fetch_row($result) {
		return $result instanceof mysqli_result ? mysqli_fetch_row($result) : false;
	}

	function mysql_num_rows($result) {
		return $result instanceof mysqli_result ? mysqli_num_rows($result) : 0;
	}

	function mysql_insert_id($link = null) {
		$link = mysql_compat_default_link($link);
		return $link ? mysqli_insert_id($link) : 0;
	}

	function mysql_fetch_field($result) {
		return $result instanceof mysqli_result ? mysqli_fetch_field($result) : false;
	}

	function mysql_real_escape_string($string, $link = null) {
		$link = mysql_compat_default_link($link);
		return $link ? mysqli_real_escape_string($link, $string) : addslashes($string);
	}

	function mysql_escape_string($string) {
		return mysql_real_escape_string($string);
	}

	function mysql_free_result($result) {
		return $result instanceof mysqli_result ? mysqli_free_result($result) : false;
	}

	function mysql_close($link = null) {
		$link = mysql_compat_default_link($link);
		if (!$link) {
			return false;
		}

		if (isset($GLOBALS['mysql_compat_default_link']) && $GLOBALS['mysql_compat_default_link'] === $link) {
			unset($GLOBALS['mysql_compat_default_link']);
		}

		return mysqli_close($link);
	}

	function mysql_affected_rows($link = null) {
		$link = mysql_compat_default_link($link);
		return $link ? mysqli_affected_rows($link) : -1;
	}
}
