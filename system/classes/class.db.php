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

	function connect($db_user, $db_pass, $db_name, $db_location = 'localhost', $show_error=1)
	{
		$this->db_id = @mysqli_connect($db_location, $db_user, $db_pass, $db_name);
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
			@mysqli_set_charset($this->db_id, COLLATE);
			@mysqli_query($this->db_id, "/*!40101 SET NAMES '" . $this->safesql(COLLATE) . "' */");
		}

		$GLOBALS['mysql_compat_default_link'] = $this->db_id;

		$this->connected = true;

		return true;
	}

	function query($query, $show_error=true)
	{
		$time_before = $this->get_real_time();

		if(!$this->connected) $this->connect(DBUSER, DBPASS, DBNAME, DBHOST);

		if(!($this->query_id = mysqli_query($this->db_id, $query) )) {

			$this->mysql_error = mysqli_error($this->db_id);
			$this->mysql_error_num = mysqli_errno($this->db_id);

			if($show_error) {
				$this->display_error($this->mysql_error, $this->mysql_error_num, $query);
			}
		}

		$this->MySQL_time_taken += $this->get_real_time() - $time_before;

		if(DEGUB_SQL || (function_exists('admin_dashboard_can_access') && admin_dashboard_can_access(($GLOBALS['USER'] ?? null), ($GLOBALS['PRIV'] ?? null)))) {
			$this->query_list[] = array( 'time'  => ($this->get_real_time() - $time_before),
									'query' => $query,
									'num'   => (count($this->query_list) + 1));
		}
		$this->query_num ++;

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

	function free( $query_id = '' )
	{

		if ($query_id == '') $query_id = $this->query_id;

		if ($query_id instanceof mysqli_result) {
			@mysqli_free_result($query_id);
		}
	}

	function close()
	{
		if ($this->db_id instanceof mysqli) {
			@mysqli_close($this->db_id);
		}
	}

	function get_real_time()
	{
		list($seconds, $microSeconds) = explode(' ', microtime());
		return ((float)$seconds + (float)$microSeconds);
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

		if ( $FH = @fopen( $config['sql_log_file'], 'a' ) )
		{
			@fwrite( $FH, $_error_string );
			@fclose( $FH );
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
