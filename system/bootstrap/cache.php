<?php

function lt_create_cache_driver()
{
	global $config;

	$cacheConfig = (!empty($config['cache']) && is_array($config['cache']) ? $config['cache'] : array());
	$driver = strtolower(trim((string) ($cacheConfig['driver'] ?? 'filecache')));

	require_once __DIR__ . '/../classes/class.memcached.php';
	require_once __DIR__ . '/../classes/class.filecache.php';

	if ($driver === 'memcached' && class_exists('Memcached', false)) {
		$memcachedConfig = (!empty($cacheConfig['memcached']) && is_array($cacheConfig['memcached']) ? $cacheConfig['memcached'] : array());
		$host = trim((string) ($memcachedConfig['host'] ?? '127.0.0.1'));
		$port = (int) ($memcachedConfig['port'] ?? 11211);

		$memcached = new MemcachedCache();
		if ($memcached->connect($host, $port, $memcachedConfig)) {
			return $memcached;
		}
	}

	return new Filecache();
}

function lt_cache()
{
	static $cache = null;

	if ($cache === null) {
		$cache = lt_create_cache_driver();
	}

	return $cache;
}

function lt_cache_bind_globals()
{
	global $memcached;

	$memcached = lt_cache();

	return $memcached;
}
