<?php

class MemcachedCache
{
	var $client = null;
	var $connected = false;

	function __construct() {
		$this->client = new Memcached();
	}

	function connect($host = '127.0.0.1', $port = 11211) {
		if (!$this->connected && empty($this->client->getServerList())) {
			$this->client->addServer($host, (int) $port);
		}

		$this->connected = true;
		return true;
	}

	function get($key) {
		$value = $this->client->get($key);

		if ($this->client->getResultCode() === Memcached::RES_NOTFOUND) {
			return false;
		}

		return $value;
	}

	function set($key, $value, $flagsOrExpiration = 0, $expiration = 0) {
		$ttl = (int) ($expiration ?: $flagsOrExpiration);
		return $this->client->set($key, $value, $ttl);
	}

	function delete($key, $timeout = 0) {
		return $this->client->delete($key);
	}
}
