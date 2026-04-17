<?php

class MemcachedCache
{
	var $client = null;
	var $connected = false;

	function __construct() {
		$this->client = new Memcached();
	}

	function setOption($name, $value) {
		$constant = 'Memcached::'.$name;
		if (defined($constant)) {
			$this->client->setOption(constant($constant), $value);
		}
	}

	function connect($host = '127.0.0.1', $port = 11211, $options = array()) {
		if ($this->connected) {
			return true;
		}

		$options = (is_array($options) ? $options : array());
		$options += array(
			'connect_timeout_ms' => 150,
			'poll_timeout_ms' => 150,
			'send_timeout_ms' => 150,
			'recv_timeout_ms' => 150,
			'retry_timeout' => 1,
			'server_failure_limit' => 1,
			'remove_failed_servers' => 1,
		);

		$this->setOption('OPT_CONNECT_TIMEOUT', (int) $options['connect_timeout_ms']);
		$this->setOption('OPT_POLL_TIMEOUT', (int) $options['poll_timeout_ms']);
		$this->setOption('OPT_SEND_TIMEOUT', (int) $options['send_timeout_ms']);
		$this->setOption('OPT_RECV_TIMEOUT', (int) $options['recv_timeout_ms']);
		$this->setOption('OPT_RETRY_TIMEOUT', (int) $options['retry_timeout']);
		$this->setOption('OPT_SERVER_FAILURE_LIMIT', (int) $options['server_failure_limit']);
		$this->setOption('OPT_REMOVE_FAILED_SERVERS', !empty($options['remove_failed_servers']));

		if (method_exists($this->client, 'resetServerList')) {
			$this->client->resetServerList();
		}

		$this->client->addServer($host, (int) $port);

		$versions = @$this->client->getVersion();
		if (!is_array($versions)) {
			return false;
		}

		foreach ($versions as $version) {
			if (!empty($version) && $version !== '255.255.255') {
				$this->connected = true;
				return true;
			}
		}

		if (method_exists($this->client, 'resetServerList')) {
			$this->client->resetServerList();
		}

		return false;
	}

	function get($key) {
		if (!$this->connected) {
			return false;
		}

		$value = $this->client->get($key);

		if ($this->client->getResultCode() === Memcached::RES_NOTFOUND) {
			return false;
		}

		return $value;
	}

	function set($key, $value, $flagsOrExpiration = 0, $expiration = 0) {
		if (!$this->connected) {
			return false;
		}

		$ttl = (int) ($expiration ?: $flagsOrExpiration);
		return $this->client->set($key, $value, $ttl);
	}

	function delete($key, $timeout = 0) {
		if (!$this->connected) {
			return false;
		}

		return $this->client->delete($key);
	}
}
