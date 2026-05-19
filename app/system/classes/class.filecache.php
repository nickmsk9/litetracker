<?php
/*
===================================================================
LiteTracker Source
===================================================================
by Nick
-------------------------------------------------------------------
Назначение: Файловый кеш
===================================================================
*/

class Filecache {

	public $dir = null;
	public $type = null;
	public $timeout = null;
	public $memory = array();


	//construct
	function __construct() {
		global $config;

		$this->dir = $config['filecache']['dir'];
		$this->type = $config['filecache']['type'];
		$this->timeout = $config['filecache']['timeout'];
		$this->ensureDirectory();
	}

	function enabled() {
		global $config;

		return !empty($config['filecache']['use']);
	}

	function ensureDirectory() {
		if (!is_dir($this->dir)) {
			mkdir($this->dir, 0777, true);
		}

		return is_dir($this->dir);
	}

	function getPath($file) {
		$file = str_replace(array('\\', '/'), '_', (string) $file);
		return $this->dir.$file.$this->type;
	}

	function getDefaultTtl() {
		return max(1, (int) $this->timeout);
	}

	function getTtl($flagsOrExpiration = 0, $expiration = 0) {
		$ttl = (int) ($expiration ?: $flagsOrExpiration);
		if ($ttl <= 0) {
			$ttl = $this->getDefaultTtl();
		}

		return $ttl;
	}

	//Получение списка
	function get($file) {
		if (!$this->enabled() || !$this->ensureDirectory()) {
			if (function_exists('lt_cache_debug_count')) {
				lt_cache_debug_count('errors');
				lt_cache_debug_count('misses');
			}
			return false;
		}

		$shell = $this->getPath($file);

		if (array_key_exists($shell, $this->memory)) {
			if (function_exists('lt_cache_debug_count')) {
				lt_cache_debug_count('hits');
			}
			return $this->memory[$shell];
		}

		if (!file_exists($shell) || !is_readable($shell) || filesize($shell) <= 0) {
			if (function_exists('lt_cache_debug_count')) {
				lt_cache_debug_count('misses');
			}
			return false;
		}

		$content = file_get_contents($shell);
		if ($content === false || $content === '') {
			if (function_exists('lt_cache_debug_count')) {
				lt_cache_debug_count('errors');
				lt_cache_debug_count('misses');
			}
			return false;
		}

		$payload = unserialize($content, ['allowed_classes' => false]);
		if (is_array($payload) && array_key_exists('expires_at', $payload) && array_key_exists('value', $payload)) {
			if ((int) $payload['expires_at'] < time()) {
				$this->delete($file);
				if (function_exists('lt_cache_debug_count')) {
					lt_cache_debug_count('misses');
				}
				return false;
			}

			$this->memory[$shell] = $payload['value'];
			if (function_exists('lt_cache_debug_count')) {
				lt_cache_debug_count('hits');
			}
			return $payload['value'];
		}

		if ((time() - $this->getDefaultTtl()) < filemtime($shell)) {
			$this->memory[$shell] = $payload;
			if (function_exists('lt_cache_debug_count')) {
				lt_cache_debug_count('hits');
			}
			return $payload;
		}

		$this->delete($file);
		if (function_exists('lt_cache_debug_count')) {
			lt_cache_debug_count('misses');
		}
		return false;
	}

	//Запись
	function set($file, $data, $flagsOrExpiration = 0, $expiration = 0) {
		if (function_exists('lt_cache_debug_count')) {
			lt_cache_debug_count('sets');
		}

		if (!$this->enabled() || !$this->ensureDirectory()) {
			if (function_exists('lt_cache_debug_count')) {
				lt_cache_debug_count('errors');
			}
			return false;
		}

		$shell = $this->getPath($file);
		$payload = serialize(array(
			'expires_at' => time() + $this->getTtl($flagsOrExpiration, $expiration),
			'value' => $data,
		));

		$fh = fopen($shell, 'c');
		if (!$fh) {
			if (function_exists('lt_cache_debug_count')) {
				lt_cache_debug_count('errors');
			}
			return false;
		}

		$result = false;
		if (flock($fh, LOCK_EX)) {
			ftruncate($fh, 0);
			$result = (fwrite($fh, $payload) !== false);
			fflush($fh);
			flock($fh, LOCK_UN);
		}
		fclose($fh);

		if ($result) {
			$this->memory[$shell] = $data;
		} elseif (function_exists('lt_cache_debug_count')) {
			lt_cache_debug_count('errors');
		}

		return $result;
	}

	//Удаление
	function delete($file  , $time = 0) {
		if (function_exists('lt_cache_debug_count')) {
			lt_cache_debug_count('deletes');
		}

		$shell = $this->getPath($file);
		unset($this->memory[$shell]);

		if (file_exists($shell)) {
			$result = unlink($shell);
			if (!$result && function_exists('lt_cache_debug_count')) {
				lt_cache_debug_count('errors');
			}
			return $result;
		}

		return false;
	}


}
?>
