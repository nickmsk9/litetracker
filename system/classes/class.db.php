<?php
/*
===================================================================
LiteTracker Source
===================================================================
by Nick
-------------------------------------------------------------------
Назначение: Класс для работы с mysql
===================================================================
*/

class db
{
	public $db_id = false;
	public $connected = false;
	public $query_num = 0;
	public $query_list = array();
	public $mysql_error = '';
	public $mysql_version = '';
	public $mysql_error_num = 0;
	public $mysql_extend = 'MySQLi';
	public $MySQL_time_taken = 0;
	public $query_id = false;
	public $sql_errors = array();
	public $slow_query_threshold = 0.05;

	function connect($db_user, $db_pass, $db_name, $db_location = 'localhost', $show_error=1)
	{
		$this->db_id = mysqli_connect($db_location, $db_user, $db_pass, $db_name);
		if(!$this->db_id) {
			if($show_error == 1) {
				$this->display_error(mysqli_connect_error(), mysqli_connect_errno());
			} else {
				return false;
			}
		}

		$this->mysql_version = mysqli_get_server_info($this->db_id);

		if(!defined('COLLATE'))
		{
			define('COLLATE', !empty($GLOBALS['mysql']['charset']) ? $GLOBALS['mysql']['charset'] : 'utf8mb4');
		}

		if (version_compare($this->mysql_version, '4.1', ">=")) {
			mysqli_set_charset($this->db_id, COLLATE);
			mysqli_query($this->db_id, "/*!40101 SET NAMES '" . $this->safesql(COLLATE) . "' */");
		}

		$GLOBALS['mysql_compat_default_link'] = $this->db_id;

		$this->connected = true;

		return true;
	}

	function query($query, $show_error=true)
	{
		$time_before = $this->get_real_time();

		if(!$this->connected) $this->connect(DBUSER, DBPASS, DBNAME, DBHOST);

		$this->query_id = mysqli_query($this->db_id, $query);
		$elapsed = $this->get_real_time() - $time_before;
		$error = '';
		$error_num = 0;

		if(!$this->query_id) {
			$this->mysql_error = mysqli_error($this->db_id);
			$this->mysql_error_num = mysqli_errno($this->db_id);
			$error = $this->mysql_error;
			$error_num = $this->mysql_error_num;
		}

		$this->MySQL_time_taken += $elapsed;
		$this->query_num ++;
		$this->record_query($query, $elapsed, $error, $error_num);

		if(!$this->query_id) {
			if($show_error) {
				$this->display_error($this->mysql_error, $this->mysql_error_num, $query);
			}
		}

		return $this->query_id;
	}

	function get_row($query_id = '')
	{
		if ($query_id == '') $query_id = $this->query_id;

		return ($query_id instanceof mysqli_result ? mysqli_fetch_assoc($query_id) : false);
	}

	function get_array($query_id = '')
	{
		if ($query_id == '') $query_id = $this->query_id;

		return ($query_id instanceof mysqli_result ? mysqli_fetch_array($query_id) : false);
	}


	function super_query($query, $multi = false)
	{

		if(!$multi) {

			$this->query($query);
			$data = $this->get_row();
			$this->free();
			return $data;

		} else {
			$this->query($query);

			$rows = array();
			while($row = $this->get_row()) {
				$rows[] = $row;
			}

			$this->free();

			return $rows;
		}
	}

	function num_rows($query_id = '')
	{

		if ($query_id == '') $query_id = $this->query_id;

		return ($query_id instanceof mysqli_result ? mysqli_num_rows($query_id) : 0);
	}

	function insert_id()
	{
		return ($this->db_id instanceof mysqli ? mysqli_insert_id($this->db_id) : 0);
	}

	function affected_rows()
	{
		return ($this->db_id instanceof mysqli ? mysqli_affected_rows($this->db_id) : 0);
	}

	function get_result_fields($query_id = '') {

		if ($query_id == '') $query_id = $this->query_id;

		$fields = array();
		if (!$query_id instanceof mysqli_result) {
			return $fields;
		}

		while ($field = mysqli_fetch_field($query_id))
		{
            $fields[] = $field;
		}

		return $fields;
   	}

	function safesql( $source )
	{
		if ($this->db_id instanceof mysqli) return mysqli_real_escape_string ($this->db_id, (string) $source);
		else return addslashes((string) $source);
	}

	/**
	 * Execute a prepared statement with positional ? placeholders.
	 *
	 * @param string $sql    SQL with ? placeholders
	 * @param string $types  MySQLi type string: 's'=string, 'i'=integer, 'd'=double, 'b'=blob.
	 *                       Pass '' to auto-bind everything as string.
	 * @param array  $params Values for each placeholder
	 * @param bool   $show_error  Whether to halt on error
	 * @return mysqli_result|true|false
	 */
	function pquery($sql, $types = '', array $params = array(), $show_error = true)
	{
		$time_before = $this->get_real_time();

		if (!$this->connected) $this->connect(DBUSER, DBPASS, DBNAME, DBHOST);

		$stmt = mysqli_prepare($this->db_id, $sql);
		if (!$stmt) {
			$this->mysql_error     = mysqli_error($this->db_id);
			$this->mysql_error_num = mysqli_errno($this->db_id);
			$elapsed = $this->get_real_time() - $time_before;
			$this->MySQL_time_taken += $elapsed;
			$this->query_num++;
			$this->record_query($this->interpolate_prepared_sql($sql, $params), $elapsed, $this->mysql_error, $this->mysql_error_num);
			if ($show_error) {
				$this->display_error($this->mysql_error, $this->mysql_error_num, $sql);
			}
			return false;
		}

		if ($params) {
			if ($types === '') {
				$types = str_repeat('s', count($params));
			}
			mysqli_stmt_bind_param($stmt, $types, ...$params);
		}

		if (!mysqli_stmt_execute($stmt)) {
			$this->mysql_error     = mysqli_stmt_error($stmt);
			$this->mysql_error_num = mysqli_stmt_errno($stmt);
			$elapsed = $this->get_real_time() - $time_before;
			$this->MySQL_time_taken += $elapsed;
			$this->query_num++;
			$this->record_query($this->interpolate_prepared_sql($sql, $params), $elapsed, $this->mysql_error, $this->mysql_error_num);
			mysqli_stmt_close($stmt);
			if ($show_error) {
				$this->display_error($this->mysql_error, $this->mysql_error_num, $sql);
			}
			return false;
		}

		$elapsed = $this->get_real_time() - $time_before;
		$this->MySQL_time_taken += $elapsed;
		$this->query_num++;

		$this->record_query($this->interpolate_prepared_sql($sql, $params), $elapsed);

		$result = mysqli_stmt_get_result($stmt);
		mysqli_stmt_close($stmt);

		if ($result instanceof mysqli_result) {
			$this->query_id = $result;
			return $result;
		}

		// DML statement: insert_id() and affected_rows() read from $this->db_id and still work
		return true;
	}

	/**
	 * Like super_query() but uses a prepared statement.
	 *
	 * @param string $sql
	 * @param string $types
	 * @param array  $params
	 * @param bool   $multi   true = return all rows, false = return first row
	 * @return array|null
	 */
	function psuper_query($sql, $types = '', array $params = array(), $multi = false)
	{
		$this->pquery($sql, $types, $params);

		if (!$multi) {
			$data = $this->get_row();
			$this->free();
			return $data;
		}

		$rows = array();
		while ($row = $this->get_row()) {
			$rows[] = $row;
		}
		$this->free();
		return $rows;
	}

	function free( $query_id = '' )
	{

		if ($query_id == '') $query_id = $this->query_id;

		if ($query_id instanceof mysqli_result) {
			mysqli_free_result($query_id);
		}
	}

	function close()
	{
		if ($this->db_id instanceof mysqli) {
			mysqli_close($this->db_id);
		}
	}

	function get_real_time()
	{
		list($seconds, $microSeconds) = explode(' ', microtime());
		return ((float)$seconds + (float)$microSeconds);
	}

	function record_query($query, $elapsed, $error = '', $error_num = 0)
	{
		$entry = array(
			'time' => (float) $elapsed,
			'query' => $this->mask_debug_sql($query),
			'num' => (count($this->query_list) + 1),
			'slow' => ((float) $elapsed > (float) $this->slow_query_threshold),
			'error' => (string) $error,
			'error_num' => (int) $error_num,
		);

		$this->query_list[] = $entry;

		if ($error !== '') {
			$this->sql_errors[] = $entry;
		}
	}

	function interpolate_prepared_sql($sql, array $params = array())
	{
		if (!$params) {
			return $sql;
		}

		$idx = 0;
		return preg_replace_callback('/\?/', function ($m) use ($params, &$idx) {
			$val = ($params[$idx] ?? '?');
			$idx++;

			if ($val === null) {
				return 'NULL';
			}

			if (is_int($val) || is_float($val)) {
				return (string) $val;
			}

			return "'".str_replace("'", "\\'", (string) $val)."'";
		}, $sql);
	}

	function mask_debug_sql($query)
	{
		$query = (string) $query;
		$sensitive = '(passkey|password|password_code|email|session_id|cookie|csrf_token|token|id_password)';

		$query = preg_replace_callback('/(INSERT\s+INTO\s+`?[\w]+`?\s*\()(.*?)(\)\s*VALUES\s*\()(.*?)(\))/is', function ($matches) use ($sensitive) {
			$columns = $this->split_sql_csv($matches[2]);
			$values = $this->split_sql_csv($matches[4]);

			if (!$columns || !$values || count($columns) !== count($values)) {
				return $matches[0];
			}

			foreach ($columns as $index => $column) {
				$column = trim((string) $column, " \t\n\r\0\x0B`");
				if (preg_match('/^'.$sensitive.'$/i', $column)) {
					$values[$index] = '[masked]';
				}
			}

			return $matches[1].implode(', ', $columns).$matches[3].implode(', ', $values).$matches[5];
		}, $query);

		$query = preg_replace('/([a-z0-9._%+\-]+)@([a-z0-9.\-]+\.[a-z]{2,})/i', '[email masked]', $query);
		$query = preg_replace('/\$2y\$[0-9]{2}\$[^\'"\s,)]+/i', '[password-hash masked]', $query);
		$query = preg_replace('/\$argon2(?:i|id)\$[^\'"\s,)]+/i', '[password-hash masked]', $query);
		$query = preg_replace('/\b[0-9a-f]{32}\b/i', '[hash32 masked]', $query);

		$query = preg_replace('/(\b'.$sensitive.'\b\s*=\s*)(\'[^\']*\'|"[^"]*"|[^\s,;)]+)/i', '$1[masked]', $query);
		$query = preg_replace('/(\b'.$sensitive.'\b\s+(?:LIKE|IN)\s*)(\([^)]+\)|\'[^\']*\'|"[^"]*")/i', '$1[masked]', $query);

		return $query;
	}

	function split_sql_csv($value)
	{
		$value = (string) $value;
		$parts = array();
		$current = '';
		$quote = '';
		$depth = 0;
		$length = strlen($value);

		for ($i = 0; $i < $length; $i++) {
			$char = $value[$i];
			$prev = ($i > 0 ? $value[$i - 1] : '');

			if ($quote !== '') {
				$current .= $char;
				if ($char === $quote && $prev !== '\\') {
					$quote = '';
				}
				continue;
			}

			if ($char === '\'' || $char === '"') {
				$quote = $char;
				$current .= $char;
				continue;
			}

			if ($char === '(') {
				$depth++;
				$current .= $char;
				continue;
			}

			if ($char === ')' && $depth > 0) {
				$depth--;
				$current .= $char;
				continue;
			}

			if ($char === ',' && $depth === 0) {
				$parts[] = trim($current);
				$current = '';
				continue;
			}

			$current .= $char;
		}

		if (trim($current) !== '') {
			$parts[] = trim($current);
		}

		return $parts;
	}

	function display_error($error, $error_num, $query = '')
	{
		global $config , $USER;
		if($query) {
			$query = preg_replace("/([0-9a-f]){32}/", "********************************", $query);
		}

		/*
		 * Пишем в логи
		*/
		$_error_string  = "\n===================================================";
		$_error_string .= "\n Date: ". date( 'r' );
		$_error_string .= "\n Error Number: " . $error_num;
		$_error_string .= "\n Error: " . $error;
		$_error_string .= "\n IP Address: " . ($_SERVER['REMOTE_ADDR'] ?? 'CLI');
		// $_error_string .= "\n in file ".$file." on line ".$line;
		$_error_string .= "\n URL:".($_SERVER['REQUEST_URI'] ?? 'CLI');
		$_error_string .= "\n Username: ".($USER['name'] ?? 'guest')."[".($USER['id'] ?? 0)."]";

		if ( $FH = fopen( $config['sql_log_file'], 'a' ) )
		{
			fwrite( $FH, $_error_string );
			fclose( $FH );
		}


		/*
		 *	Выводим
		*/
		header('HTTP/1.1 500 Internal Server Error');
		echo '<!doctype html><html lang="ru"><head><meta charset="utf-8"><title>Ошибка сервера</title></head><body><h1>Ошибка сервера</h1><p>Во время обработки запроса произошла внутренняя ошибка. Попробуйте повторить действие позже.</p></body></html>';

		exit();
	}

}
?>
