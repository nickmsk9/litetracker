<?php

function lt_cache_debug_stats()
{
	if (empty($GLOBALS['lt_cache_debug_stats']) || !is_array($GLOBALS['lt_cache_debug_stats'])) {
		$GLOBALS['lt_cache_debug_stats'] = array(
			'hits' => 0,
			'misses' => 0,
			'sets' => 0,
			'deletes' => 0,
			'errors' => 0,
			'driver' => 'unknown',
			'fallback_reason' => '',
			'memcached_online' => null,
		);
	}

	return $GLOBALS['lt_cache_debug_stats'];
}

function lt_cache_debug_count($counter, $amount = 1)
{
	$counter = (string) $counter;
	if (empty($GLOBALS['lt_cache_debug_stats']) || !is_array($GLOBALS['lt_cache_debug_stats'])) {
		lt_cache_debug_stats();
	}

	if (!array_key_exists($counter, $GLOBALS['lt_cache_debug_stats'])) {
		$GLOBALS['lt_cache_debug_stats'][$counter] = 0;
	}

	$GLOBALS['lt_cache_debug_stats'][$counter] += (int) $amount;
}

function lt_create_cache_driver()
{
	global $config;

	lt_cache_debug_stats();
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
			$GLOBALS['lt_cache_debug_stats']['driver'] = 'memcached';
			$GLOBALS['lt_cache_debug_stats']['memcached_online'] = true;
			$GLOBALS['lt_cache_debug_stats']['fallback_reason'] = '';
			return $memcached;
		}

		$GLOBALS['lt_cache_debug_stats']['memcached_online'] = false;
		$GLOBALS['lt_cache_debug_stats']['fallback_reason'] = 'memcached_unavailable';
		lt_cache_debug_count('errors');
	} elseif ($driver === 'memcached') {
		$GLOBALS['lt_cache_debug_stats']['memcached_online'] = false;
		$GLOBALS['lt_cache_debug_stats']['fallback_reason'] = 'memcached_extension_missing';
		lt_cache_debug_count('errors');
	} else {
		$GLOBALS['lt_cache_debug_stats']['fallback_reason'] = 'configured_filecache';
	}

	$GLOBALS['lt_cache_debug_stats']['driver'] = 'filecache';
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

function lt_cache_prefix()
{
	global $config;

	static $prefix = null;

	if ($prefix !== null) {
		return $prefix;
	}

	$seed = (defined('COOKIE_SALT') ? (string) COOKIE_SALT : '').'|'.(string) ($config['sitename'] ?? 'litetracker');
	$namespace = trim((string) ($config['cache']['namespace'] ?? 'litetracker'));
	$namespace = preg_replace('~[^a-z0-9:_-]+~i', '-', $namespace);
	if ($namespace === '') {
		$namespace = 'litetracker';
	}
	$prefix = $namespace.':'.substr(md5($seed), 0, 12);

	return $prefix;
}

function lt_cache_runtime_info()
{
	global $config;

	$stats = lt_cache_debug_stats();
	$cacheConfig = (!empty($config['cache']) && is_array($config['cache']) ? $config['cache'] : array());
	$memcachedConfig = (!empty($cacheConfig['memcached']) && is_array($cacheConfig['memcached']) ? $cacheConfig['memcached'] : array());

	return array(
		'configured_driver' => (string) ($cacheConfig['driver'] ?? 'filecache'),
		'active_driver' => (string) ($stats['driver'] ?? 'unknown'),
		'fallback_reason' => (string) ($stats['fallback_reason'] ?? ''),
		'memcached_host' => (string) ($memcachedConfig['host'] ?? ''),
		'memcached_port' => (int) ($memcachedConfig['port'] ?? 0),
		'memcached_online' => ($stats['memcached_online'] ?? null),
		'namespace' => (string) ($cacheConfig['namespace'] ?? 'litetracker'),
	);
}

function lt_cache_namespace_normalize($namespace)
{
	$namespace = strtolower(trim((string) $namespace));
	$namespace = preg_replace('~[^a-z0-9:_-]+~', '-', $namespace);

	return ($namespace !== '' ? $namespace : 'default');
}

function lt_cache_raw_key($key)
{
	$key = trim((string) $key);
	$key = preg_replace('~\s+~', '-', $key);

	return lt_cache_prefix().':'.$key;
}

function lt_cache_raw_get($key)
{
	$cache = lt_cache();

	if (!is_object($cache) || !method_exists($cache, 'get')) {
		return false;
	}

	return $cache->get(lt_cache_raw_key($key));
}

function lt_cache_raw_set($key, $value, $ttl = 0)
{
	$cache = lt_cache();

	if (!is_object($cache) || !method_exists($cache, 'set')) {
		return false;
	}

	return $cache->set(lt_cache_raw_key($key), $value, 0, (int) $ttl);
}

function lt_cache_raw_delete($key)
{
	$cache = lt_cache();

	if (!is_object($cache) || !method_exists($cache, 'delete')) {
		return false;
	}

	return $cache->delete(lt_cache_raw_key($key), 0);
}

function lt_cache_namespace_version($namespace)
{
	$namespace = lt_cache_namespace_normalize($namespace);
	$versionKey = 'nsver:'.$namespace;
	$version = lt_cache_raw_get($versionKey);

	if (!is_numeric($version) || (int) $version < 1) {
		$version = 1;
		lt_cache_raw_set($versionKey, $version, 30 * 24 * 60 * 60);
	}

	return (int) $version;
}

function lt_cache_key($key, $namespace = 'default')
{
	$namespace = lt_cache_namespace_normalize($namespace);
	$key = trim((string) $key);
	$key = preg_replace('~\s+~', '-', $key);

	return lt_cache_raw_key($namespace.':v'.lt_cache_namespace_version($namespace).':'.$key);
}

function lt_cache_get($key, $namespace = 'default')
{
	$cache = lt_cache();

	if (!is_object($cache) || !method_exists($cache, 'get')) {
		return false;
	}

	return $cache->get(lt_cache_key($key, $namespace));
}

function lt_cache_set($key, $value, $ttl = 0, $namespace = 'default')
{
	$cache = lt_cache();

	if (!is_object($cache) || !method_exists($cache, 'set')) {
		return false;
	}

	return $cache->set(lt_cache_key($key, $namespace), $value, 0, (int) $ttl);
}

function lt_cache_delete($key, $namespace = 'default')
{
	$cache = lt_cache();

	if (!is_object($cache) || !method_exists($cache, 'delete')) {
		return false;
	}

	return $cache->delete(lt_cache_key($key, $namespace), 0);
}

function lt_cache_remember($key, $ttl, $callback, $namespace = 'default')
{
	$value = lt_cache_get($key, $namespace);
	if (false !== $value) {
		return $value;
	}

	if (!is_callable($callback)) {
		return false;
	}

	$value = call_user_func($callback);
	lt_cache_set($key, $value, (int) $ttl, $namespace);

	return $value;
}

function lt_cache_invalidate_namespace($namespace)
{
	$namespace = lt_cache_namespace_normalize($namespace);
	$versionKey = 'nsver:'.$namespace;
	$nextVersion = lt_cache_namespace_version($namespace) + 1;
	lt_cache_raw_set($versionKey, $nextVersion, 30 * 24 * 60 * 60);

	return $nextVersion;
}

function lt_cache_delete_by_prefix($namespace)
{
	return lt_cache_invalidate_namespace($namespace);
}

function lt_rate_limit_identifier($identifier = '')
{
	$identifier = trim((string) $identifier);

	if ($identifier !== '') {
		return $identifier;
	}

	return (string) ($_SERVER['REMOTE_ADDR'] ?? 'cli');
}

function lt_rate_limit_hit($scope, $identifier, $limit, $windowSeconds)
{
	$scope = lt_cache_namespace_normalize($scope);
	$identifier = lt_rate_limit_identifier($identifier);
	$limit = max(1, (int) $limit);
	$windowSeconds = max(1, (int) $windowSeconds);
	$key = 'hit:'.$scope.':'.md5($identifier);
	$bucket = lt_cache_get($key, 'ratelimit');
	$now = time();

	if (!is_array($bucket) || empty($bucket['reset_at']) || (int) $bucket['reset_at'] <= $now) {
		$bucket = array(
			'count' => 0,
			'reset_at' => ($now + $windowSeconds),
		);
	}

	$bucket['count'] = (int) ($bucket['count'] ?? 0) + 1;
	$ttl = max(1, (int) $bucket['reset_at'] - $now);
	lt_cache_set($key, $bucket, $ttl, 'ratelimit');

	return array(
		'limit' => $limit,
		'count' => (int) $bucket['count'],
		'remaining' => max(0, $limit - (int) $bucket['count']),
		'reset_at' => (int) $bucket['reset_at'],
		'blocked' => ((int) $bucket['count'] > $limit),
	);
}
