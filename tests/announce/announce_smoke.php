<?php
/*
 * LiteTracker announce smoke harness.
 *
 * CLI-only, dependency-free, local rehearsal helper. It invokes announce.php and
 * scrape.php in isolated PHP subprocesses with synthetic GET/server variables.
 */

if (PHP_SAPI !== 'cli') {
	fwrite(STDERR, "This smoke runner is CLI-only.\n");
	exit(2);
}

$root = dirname(__DIR__, 2);
chdir($root);

require_once $root.'/system/config/config.mysql.php';

$state = array(
	'root' => $root,
	'tests' => array(),
	'peer_ids' => array(),
	'torrent' => null,
	'user' => null,
	'tracker_snapshot' => null,
	'torrent_snapshot' => null,
);

function p15_line($status, $name, $message = '')
{
	$suffix = ($message !== '' ? ' - '.$message : '');
	echo sprintf("%-4s %s%s\n", $status, $name, $suffix);
}

function p15_db()
{
	static $mysqli = null;
	global $mysql;

	if ($mysqli instanceof mysqli) {
		return $mysqli;
	}

	$mysqli = @new mysqli($mysql['host'], $mysql['user'], $mysql['password'], $mysql['db']);
	if ($mysqli->connect_errno) {
		throw new RuntimeException('DB connect failed: '.$mysqli->connect_error);
	}
	$mysqli->set_charset($mysql['charset'] ?? 'utf8mb4');

	return $mysqli;
}

function p15_row($sql)
{
	$res = p15_db()->query($sql);
	if (!$res instanceof mysqli_result) {
		throw new RuntimeException('Query failed: '.p15_db()->error);
	}
	$row = $res->fetch_assoc();
	$res->free();

	return $row ?: null;
}

function p15_exec($sql)
{
	if (!p15_db()->query($sql)) {
		throw new RuntimeException('Query failed: '.p15_db()->error);
	}
}

function p15_escape($value)
{
	return p15_db()->real_escape_string((string) $value);
}

function p15_bdecode($data, &$pos = 0)
{
	$len = strlen($data);
	if ($pos >= $len) {
		throw new RuntimeException('Unexpected end of bencode payload');
	}

	$char = $data[$pos];
	if ($char === 'i') {
		$end = strpos($data, 'e', $pos);
		if ($end === false) {
			throw new RuntimeException('Unterminated bencode integer');
		}
		$number = substr($data, $pos + 1, $end - $pos - 1);
		$pos = $end + 1;
		return (int) $number;
	}

	if ($char === 'l') {
		$pos++;
		$list = array();
		while ($pos < $len && $data[$pos] !== 'e') {
			$list[] = p15_bdecode($data, $pos);
		}
		$pos++;
		return $list;
	}

	if ($char === 'd') {
		$pos++;
		$dict = array();
		while ($pos < $len && $data[$pos] !== 'e') {
			$key = p15_bdecode($data, $pos);
			$dict[(string) $key] = p15_bdecode($data, $pos);
		}
		$pos++;
		return $dict;
	}

	if (ctype_digit($char)) {
		$colon = strpos($data, ':', $pos);
		if ($colon === false) {
			throw new RuntimeException('Invalid bencode string');
		}
		$size = (int) substr($data, $pos, $colon - $pos);
		$pos = $colon + 1;
		$value = substr($data, $pos, $size);
		$pos += $size;
		return $value;
	}

	throw new RuntimeException('Invalid bencode token at offset '.$pos);
}

function p15_decode_response($payload)
{
	$pos = 0;
	$value = p15_bdecode((string) $payload, $pos);
	if ($pos !== strlen((string) $payload)) {
		throw new RuntimeException('Trailing bytes after bencode payload');
	}
	return $value;
}

function p15_peer_id($tag)
{
	global $state;
	$id = substr('-P15SMK-'.$tag.str_repeat('X', 20), 0, 20);
	$state['peer_ids'][] = $id;
	return $id;
}

function p15_query(array $params)
{
	return http_build_query($params, '', '&', PHP_QUERY_RFC3986);
}

function p15_run_target($target, array $params)
{
	global $state;

	$code = <<<'PHP'
$root = getenv('P15_ROOT');
$target = getenv('P15_TARGET');
$query = getenv('P15_QUERY');
chdir($root);
parse_str($query, $_GET);
$_REQUEST = $_GET;
$_POST = array();
$_COOKIE = array();
$_SERVER['DOCUMENT_ROOT'] = $root;
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['QUERY_STRING'] = $query;
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/'.$target.($query !== '' ? '?'.$query : '');
$_SERVER['PHP_SELF'] = '/'.$target;
$_SERVER['SCRIPT_NAME'] = '/'.$target;
$_SERVER['HTTP_USER_AGENT'] = 'Transmission/3.00';
unset($_SERVER['HTTP_COOKIE'], $_SERVER['HTTP_ACCEPT_LANGUAGE'], $_SERVER['HTTP_ACCEPT_CHARSET']);
require $root.'/'.$target;
PHP;

	$cmd = array(PHP_BINARY, '-d', 'variables_order=EGPCS', '-r', $code);
	$descriptors = array(
		0 => array('pipe', 'r'),
		1 => array('pipe', 'w'),
		2 => array('pipe', 'w'),
	);
	$env = array_merge($_ENV, array(
		'P15_ROOT' => $state['root'],
		'P15_TARGET' => $target,
		'P15_QUERY' => p15_query($params),
	));
	$proc = proc_open($cmd, $descriptors, $pipes, $state['root'], $env);
	if (!is_resource($proc)) {
		throw new RuntimeException('Failed to start PHP subprocess');
	}
	fclose($pipes[0]);
	$stdout = stream_get_contents($pipes[1]);
	$stderr = stream_get_contents($pipes[2]);
	fclose($pipes[1]);
	fclose($pipes[2]);
	$exit = proc_close($proc);

	return array('exit' => $exit, 'stdout' => $stdout, 'stderr' => $stderr);
}

function p15_base_params($peerId, array $overrides = array())
{
	global $state;

	return array_merge(array(
		'info_hash' => pack('H*', $state['torrent']['infohash']),
		'peer_id' => $peerId,
		'port' => 51413,
		'uploaded' => 0,
		'downloaded' => 0,
		'left' => (int) $state['torrent']['size'],
		'compact' => 0,
		'numwant' => 10,
	), $overrides);
}

function p15_assert_bencoded_dict($name, $result)
{
	if ($result['exit'] !== 0) {
		throw new RuntimeException('subprocess exit '.$result['exit'].': '.trim($result['stderr']));
	}
	$decoded = p15_decode_response($result['stdout']);
	if (!is_array($decoded)) {
		throw new RuntimeException($name.' did not return a bencoded dictionary');
	}
	return $decoded;
}

function p15_record($name, $fn)
{
	global $state;

	try {
		$message = $fn();
		$state['tests'][] = array($name, 'PASS', (string) $message);
		p15_line('PASS', $name, (string) $message);
	} catch (RuntimeException $e) {
		$prefix = str_starts_with($e->getMessage(), 'SKIP:') ? 'SKIP' : 'FAIL';
		$message = ($prefix === 'SKIP' ? substr($e->getMessage(), 5) : $e->getMessage());
		$state['tests'][] = array($name, $prefix, $message);
		p15_line($prefix, $name, $message);
	}
}

function p15_cleanup()
{
	global $state;

	if ($state['peer_ids']) {
		$ids = array();
		foreach (array_unique($state['peer_ids']) as $peerId) {
			$ids[] = "'".p15_escape($peerId)."'";
		}
		p15_exec('DELETE FROM peers WHERE peer_id IN ('.implode(',', $ids).')');
	}

	if ($state['tracker_snapshot']) {
		$s = $state['tracker_snapshot'];
		p15_exec(
			"UPDATE trackers SET seeders=".(int) $s['seeders'].
			", leechers=".(int) $s['leechers'].
			", lastchecked=".(int) $s['lastchecked'].
			" WHERE torrent=".(int) $s['torrent']." AND tracker='localhost'"
		);
	}

	if ($state['torrent_snapshot']) {
		$s = $state['torrent_snapshot'];
		p15_exec(
			"UPDATE torrents SET completed=".(int) $s['completed'].
			", last_action='".p15_escape($s['last_action'])."'".
			" WHERE id=".(int) $s['id']
		);
	}
}

try {
	$state['torrent'] = p15_row("SELECT id, infohash, size, completed, last_action FROM torrents WHERE infohash <> '' ORDER BY id LIMIT 1");
	if (!$state['torrent']) {
		throw new RuntimeException('No local torrent with infohash found.');
	}

	$state['user'] = p15_row("SELECT id, passkey FROM users WHERE passkey IS NOT NULL AND passkey <> '' ORDER BY id LIMIT 1");
	$state['tracker_snapshot'] = p15_row(
		"SELECT torrent, seeders, leechers, lastchecked FROM trackers WHERE torrent=".(int) $state['torrent']['id']." AND tracker='localhost' LIMIT 1"
	);
	$state['torrent_snapshot'] = array(
		'id' => $state['torrent']['id'],
		'completed' => $state['torrent']['completed'],
		'last_action' => $state['torrent']['last_action'],
	);

	register_shutdown_function('p15_cleanup');

	echo "LiteTracker P15 announce smoke\n";
	echo "Torrent #".$state['torrent']['id']." infohash ".$state['torrent']['infohash']."\n\n";

	p15_record('invalid passkey returns safe failure', function () {
		$params = p15_base_params(p15_peer_id('BADPASS'), array(
			'passkey' => str_repeat('a', 31),
		));
		$decoded = p15_assert_bencoded_dict('invalid passkey', p15_run_target('announce.php', $params));
		if (empty($decoded['failure reason'])) {
			throw new RuntimeException('missing failure reason');
		}
		if (strpos((string) $decoded['failure reason'], str_repeat('a', 31)) !== false) {
			throw new RuntimeException('failure reason leaked passkey');
		}
		return 'failure reason present, token not leaked';
	});

	p15_record('invalid info_hash returns failure', function () {
		$params = p15_base_params(p15_peer_id('BADHASH'), array(
			'info_hash' => str_repeat("\x00", 20),
		));
		$decoded = p15_assert_bencoded_dict('invalid info_hash', p15_run_target('announce.php', $params));
		if (empty($decoded['failure reason'])) {
			throw new RuntimeException('missing failure reason');
		}
		return 'failure reason present';
	});

	p15_record('normal announce shape returns interval and peers', function () {
		$params = p15_base_params(p15_peer_id('NORMAL'), array('event' => 'stopped'));
		$decoded = p15_assert_bencoded_dict('normal announce', p15_run_target('announce.php', $params));
		if (!array_key_exists('interval', $decoded) || !array_key_exists('peers', $decoded)) {
			throw new RuntimeException('missing interval or peers');
		}
		return 'bencoded dictionary has interval and peers';
	});

	p15_record('compact=1 response shape', function () {
		$params = p15_base_params(p15_peer_id('COMPACT'), array('compact' => 1, 'event' => 'stopped'));
		$decoded = p15_assert_bencoded_dict('compact announce', p15_run_target('announce.php', $params));
		if (!array_key_exists('peers', $decoded) || !is_string($decoded['peers'])) {
			throw new RuntimeException('compact peers value is not a string');
		}
		if (strlen($decoded['peers']) % 6 !== 0) {
			throw new RuntimeException('compact peers length is not divisible by 6');
		}
		return 'compact peers string length '.strlen($decoded['peers']);
	});

	p15_record('event=started does not crash', function () {
		$params = p15_base_params(p15_peer_id('STARTED'), array('event' => 'started'));
		$decoded = p15_assert_bencoded_dict('started announce', p15_run_target('announce.php', $params));
		if (isset($decoded['failure reason'])) {
			throw new RuntimeException('failure reason: '.$decoded['failure reason']);
		}
		return 'accepted';
	});

	p15_record('event=stopped does not crash', function () {
		$params = p15_base_params(p15_peer_id('STOPPED'), array('event' => 'stopped'));
		$decoded = p15_assert_bencoded_dict('stopped announce', p15_run_target('announce.php', $params));
		if (isset($decoded['failure reason'])) {
			throw new RuntimeException('failure reason: '.$decoded['failure reason']);
		}
		return 'accepted';
	});

	p15_record('left=0 seeder logic does not crash', function () {
		$params = p15_base_params(p15_peer_id('SEEDER'), array('left' => 0));
		$decoded = p15_assert_bencoded_dict('seeder announce', p15_run_target('announce.php', $params));
		if (isset($decoded['failure reason'])) {
			throw new RuntimeException('failure reason: '.$decoded['failure reason']);
		}
		return 'accepted';
	});

	p15_record('scrape.php basic response', function () {
		global $state;
		$result = p15_run_target('scrape.php', array('info_hash' => pack('H*', $state['torrent']['infohash'])));
		$decoded = p15_assert_bencoded_dict('scrape', $result);
		if (empty($decoded['files']) || !is_array($decoded['files'])) {
			throw new RuntimeException('missing files dictionary');
		}
		return 'files dictionary present';
	});

	echo "\n";
	$failed = 0;
	foreach ($state['tests'] as $test) {
		if ($test[1] === 'FAIL') {
			$failed++;
		}
	}
	echo 'Result: '.($failed ? 'FAIL' : 'PASS').' ('.count($state['tests'])." scenarios, ".$failed." failed)\n";
	exit($failed ? 1 : 0);
} catch (Throwable $e) {
	fwrite(STDERR, 'P15 smoke setup failed: '.$e->getMessage()."\n");
	exit(2);
}
