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

		$memcache = new MemcachedCache();
		if ($memcache->connect($host, $port, $memcachedConfig)) {
			return $memcache;
		}
	}

	return new Filecache();
}
