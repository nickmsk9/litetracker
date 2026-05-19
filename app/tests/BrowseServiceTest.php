<?php

namespace {

defined('CMS') || define('CMS', true);
defined('DB_PREFIX') || define('DB_PREFIX', '');
defined('COOKIE_SALT') || define('COOKIE_SALT', 'test-cookie-salt');
defined('DEBUG') || define('DEBUG', false);
defined('DEBUG_SQL') || define('DEBUG_SQL', false);
defined('COLLATE') || define('COLLATE', 'utf8mb4');

$GLOBALS['config'] = $GLOBALS['config'] ?? array('sitename' => 'TestTracker');
$GLOBALS['language'] = $GLOBALS['language'] ?? array();

if (!function_exists('sqlwildcardesc')) {
	function sqlwildcardesc($x)
	{
		return addcslashes((string) $x, "%_\\'");
	}
}

if (!function_exists('categories_array')) {
	function categories_array($id = 0)
	{
		return array(
			array('id' => 1, 'name' => 'Movies', 'image' => '1.gif'),
			array('id' => 2, 'name' => 'Music', 'image' => '2.gif'),
		);
	}
}

if (!function_exists('lt_torrent_status_filter_sql')) {
	function lt_torrent_status_filter_sql($user, $alias = 't')
	{
		return $alias.".status = 'approved'";
	}
}

if (!function_exists('lt_torrent_can_moderate')) {
	function lt_torrent_can_moderate($user)
	{
		return false;
	}
}

if (!function_exists('pager')) {
	function pager($rpp, $count, $href, $opts = array())
	{
		return array('', '');
	}
}

if (!function_exists('lt_cache_remember')) {
	function lt_cache_remember($key, $ttl, $callback, $namespace = '')
	{
		return $callback();
	}
}

if (!function_exists('lt_cache_key_tags_popular')) {
	function lt_cache_key_tags_popular($limit)
	{
		return 'tags:popular:'.$limit;
	}
}

if (!function_exists('lt_cache_key_tags_ns')) {
	function lt_cache_key_tags_ns()
	{
		return 'tags';
	}
}

if (!function_exists('lt_cache_key_user_ns')) {
	function lt_cache_key_user_ns()
	{
		return 'users';
	}
}

if (!function_exists('lt_cache_get')) {
	function lt_cache_get($key, $namespace = '')
	{
		return false;
	}
}

if (!function_exists('lt_cache_set')) {
	function lt_cache_set($key, $value, $ttl, $namespace = '')
	{
		return true;
	}
}

if (!function_exists('lt_table_exists')) {
	function lt_table_exists($table)
	{
		return true;
	}
}

if (!function_exists('lt_rate_limit_hit')) {
	function lt_rate_limit_hit($scope, $identifier, $limit, $windowSeconds)
	{
		return array('blocked' => !empty($GLOBALS['__browse_rate_limit_blocked']));
	}
}

require_once dirname(__DIR__, 2) . '/app/system/functions/functions.upload.php';
require_once dirname(__DIR__, 2) . '/app/system/functions/functions.tags.php';
require_once dirname(__DIR__, 2) . '/app/core/http.php';
require_once dirname(__DIR__, 2) . '/app/core/browse.php';
}

namespace LiteTracker\Tests {

use PHPUnit\Framework\TestCase;

final class BrowseServiceTest extends TestCase
{
	protected function setUp(): void
	{
		$GLOBALS['USER'] = array('id' => 7);
		$GLOBALS['PRIV'] = array('upload' => true);
		$GLOBALS['config']['releases_news'] = 7;
		$GLOBALS['db'] = new BrowseFakeDb();
	}

	public function testBrowseWithoutParametersBuildsDefaultModel(): void
	{
		$model = \browse_build_page_model(array(), array('REMOTE_ADDR' => '127.0.0.1'));

		$this->assertArrayHasKey('vars', $model);
		$this->assertSame('', $model['vars']['search']);
		$this->assertSame('date', $model['vars']['sort']);
		$this->assertSame('compact', $model['vars']['view']);
		$this->assertSame(1, count($model['vars']['rows']));
	}

	public function testSearchAddsSearchClauseAndRecordsQuery(): void
	{
		$db = $GLOBALS['db'];
		$model = \browse_build_page_model(array('search' => 'matrix'), array('QUERY_STRING' => 'search=matrix'));

		$this->assertArrayHasKey('vars', $model);
		$this->assertTrue($db->hasQueryContaining("t.name LIKE '%matrix%'"));
		$this->assertTrue($db->hasQueryContaining('INSERT INTO search_query'));
	}

	public function testAjaxSuggestReturnsJsonPayload(): void
	{
		$model = \browse_build_page_model(array('ajax' => '1', 'mode' => 'suggest', 'q' => 'm'), array());

		$this->assertArrayHasKey('json', $model);
		$this->assertSame(1, $model['json']['ok']);
		$this->assertContains('matrix', $model['json']['popular']);
		$this->assertSame(array(
			array('id' => 1, 'name' => 'Movies'),
			array('id' => 2, 'name' => 'Music'),
		), $model['json']['categories']);
	}

	public function testCategoryFilterAddsCategoryCondition(): void
	{
		$db = $GLOBALS['db'];
		\browse_build_page_model(array('id_category' => '2'), array());

		$this->assertTrue($db->hasQueryContaining('t.id_category = 2'));
	}

	public function testInvalidSortFallsBackToDate(): void
	{
		$model = \browse_build_page_model(array('sort' => 'wat'), array());

		$this->assertSame('date', $model['vars']['sort']);
	}

	public function testRateLimitBranchUsesBlockedFlag(): void
	{
		$GLOBALS['__browse_rate_limit_blocked'] = true;
		$model = \browse_build_page_model(array('ajax' => '1', 'search' => 'matrix'), array());
		unset($GLOBALS['__browse_rate_limit_blocked']);

		$this->assertArrayHasKey('json', $model);
		$this->assertSame(0, $model['json']['ok']);
	}
}

final class BrowseFakeDb
{
	/** @var array<string, array<int, array<string, mixed>>> */
	private array $results = array();
	/** @var array<int, string> */
	public array $queries = array();
	private int $nextResult = 1;

	public function safesql($value)
	{
		return addslashes((string) $value);
	}

	public function query($sql)
	{
		$this->queries[] = $sql;
		$id = 'r'.$this->nextResult++;
		$this->results[$id] = $this->rowsFor($sql);
		return $id;
	}

	public function get_row($result)
	{
		if (empty($this->results[$result])) {
			return false;
		}
		return array_shift($this->results[$result]);
	}

	public function super_query($sql)
	{
		$this->queries[] = $sql;
		return array('count' => 0);
	}

	public function free($result): void
	{
	}

	public function hasQueryContaining(string $needle): bool
	{
		foreach ($this->queries as $query) {
			if (strpos($query, $needle) !== false) {
				return true;
			}
		}
		return false;
	}

	private function rowsFor(string $sql): array
	{
		if (strpos($sql, 'FROM search_query') !== false && strpos($sql, 'GROUP BY text') !== false) {
			return array(array('text' => 'matrix'));
		}
		if (strpos($sql, 'FROM search_query') !== false) {
			return array(array('text' => 'matrix recent'));
		}
		if (strpos($sql, 'FROM tags') !== false) {
			return array(array('name' => 'matrix', 'tag_count' => 3));
		}
		if (strpos($sql, 'SELECT t.') !== false && strpos($sql, 'AS meta_') !== false) {
			return array(array(
				'meta_genres' => 'action',
				'meta_countries' => '',
				'meta_languages' => '',
				'meta_subtitles' => '',
				'meta_type' => '',
			));
		}
		if (strpos($sql, 'FROM torrents AS t') !== false) {
			return array(array(
				'id' => 10,
				'id_category' => 1,
				'id_user' => 7,
				'name' => 'Matrix',
				'total_count' => 1,
			));
		}
		return array();
	}
}
}
