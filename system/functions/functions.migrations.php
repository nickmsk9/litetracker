<?php
/*
===================================================================
Database Migration Management System
===================================================================
Purpose: Backend service for reading, validating, and executing DB migrations
===================================================================
*/

/**
 * Get path to migrations directory
 */
function lt_migrations_path()
{
	return __DIR__.'/../../database/migrations';
}

/**
 * Get path to schema_migrations table
 */
function lt_migrations_ensure_table()
{
	global $db;

	// Create schema_migrations table if not exists
	$sql = "CREATE TABLE IF NOT EXISTS `schema_migrations` (
		`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
		`migration` VARCHAR(255) NOT NULL,
		`checksum` CHAR(40) NOT NULL,
		`batch` INT UNSIGNED NOT NULL DEFAULT 0,
		`applied_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
		`execution_time_ms` INT UNSIGNED NOT NULL DEFAULT 0,
		`status` ENUM('pending', 'applied', 'failed', 'changed') NOT NULL DEFAULT 'pending',
		`error_message` LONGTEXT NULL,
		UNIQUE KEY `idx_migrations_name` (`migration`(100)),
		KEY `idx_migrations_status` (`status`),
		KEY `idx_migrations_batch` (`batch`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

	$db->query($sql, 0);

	return lt_table_exists('schema_migrations', true);
}

/**
 * Read and parse all migration files from directory
 * Returns array of migration metadata
 */
function lt_migrations_list_available()
{
	$migrationsPath = lt_migrations_path();
	$result = array();

	if (!is_dir($migrationsPath)) {
		return $result;
	}

	// Scan for .sql files
	$files = @scandir($migrationsPath);
	if (!$files || !is_array($files)) {
		return $result;
	}

	// Sort by filename (timestamps in name ensure order)
	sort($files);

	foreach ($files as $filename) {
		if (substr($filename, -4) !== '.sql') {
			continue;
		}

		$filePath = $migrationsPath.'/'.$filename;
		if (!is_file($filePath) || !is_readable($filePath)) {
			continue;
		}

		$content = file_get_contents($filePath);
		if ($content === false) {
			continue;
		}

		$result[] = array(
			'name' => $filename,
			'path' => $filePath,
			'size' => strlen($content),
			'checksum' => sha1($content),
			'content' => $content,
		);
	}

	return $result;
}

/**
 * Get migration status from database
 */
function lt_migrations_get_status($migrationName)
{
	global $db;

	if (!lt_migrations_ensure_table()) {
		return null;
	}

	$migrationName = trim((string) $migrationName);
	$sql = "SELECT * FROM `schema_migrations` WHERE migration = '".$db->safesql($migrationName)."' LIMIT 1";
	$result = $db->super_query($sql);

	return $result ?: null;
}

/**
 * Get all recorded migrations
 */
function lt_migrations_list_applied()
{
	global $db;

	if (!lt_migrations_ensure_table()) {
		return array();
	}

	$sql = "SELECT * FROM `schema_migrations` ORDER BY batch DESC, applied_at DESC";
	$result = $db->query($sql, 0);

	$rows = array();
	if ($result !== false) {
		while ($row = $db->get_row($result)) {
			$rows[] = $row;
		}
		$db->free($result);
	}

	return $rows;
}

/**
 * Get next batch number
 */
function lt_migrations_get_next_batch()
{
	global $db;

	if (!lt_migrations_ensure_table()) {
		return 1;
	}

	$sql = "SELECT MAX(batch) as max_batch FROM `schema_migrations` WHERE status = 'applied'";
	$result = $db->super_query($sql);
	$maxBatch = (int) ($result['max_batch'] ?? 0);

	return $maxBatch + 1;
}

/**
 * Mark migration as applied
 */
function lt_migrations_mark_applied($migrationName, $checksum, $batch, $executionTimeMs)
{
	global $db;

	if (!lt_migrations_ensure_table()) {
		return false;
	}

	$migrationName = trim((string) $migrationName);
	$checksum = trim((string) $checksum);
	$batch = (int) $batch;
	$executionTimeMs = (int) $executionTimeMs;

	// Check if exists
	$existing = lt_migrations_get_status($migrationName);
	if ($existing) {
		// Update existing
		$sql = "UPDATE `schema_migrations` SET "
			." status = 'applied'"
			." , checksum = '".$db->safesql($checksum)."'"
			." , batch = ".$batch
			." , execution_time_ms = ".$executionTimeMs
			." , error_message = NULL"
			." , applied_at = NOW()"
			." WHERE migration = '".$db->safesql($migrationName)."'";
	} else {
		// Insert new
		$sql = "INSERT INTO `schema_migrations` (migration, checksum, batch, execution_time_ms, status, applied_at)"
			." VALUES ("
			." '".$db->safesql($migrationName)."'"
			." , '".$db->safesql($checksum)."'"
			." , ".$batch
			." , ".$executionTimeMs
			." , 'applied'"
			." , NOW()"
			." )";
	}

	$db->query($sql, 0);

	return true;
}

/**
 * Mark migration as failed
 */
function lt_migrations_mark_failed($migrationName, $errorMessage)
{
	global $db;

	if (!lt_migrations_ensure_table()) {
		return false;
	}

	$migrationName = trim((string) $migrationName);
	$errorMessage = trim((string) $errorMessage);

	$sql = "INSERT INTO `schema_migrations` (migration, status, error_message) "
		." VALUES ('".$db->safesql($migrationName)."', 'failed', '".$db->safesql($errorMessage)."') "
		." ON DUPLICATE KEY UPDATE "
		." status = 'failed'"
		." , error_message = '".$db->safesql($errorMessage)."'";

	$db->query($sql, 0);

	return true;
}

/**
 * Compare file checksum with database
 * Returns: 'applied' | 'pending' | 'failed' | 'changed' | 'unknown'
 */
function lt_migrations_compare_status($migrationName, $currentChecksum)
{
	$status = lt_migrations_get_status($migrationName);

	if (!$status) {
		return 'pending';
	}

	if ($status['status'] === 'applied' && $status['checksum'] !== $currentChecksum) {
		return 'changed';
	}

	return $status['status'];
}

/**
 * Build combined migration list with status
 */
function lt_migrations_full_list()
{
	$available = lt_migrations_list_available();
	$applied = lt_migrations_list_applied();
	$appliedByName = array();

	foreach ($applied as $item) {
		$appliedByName[$item['migration']] = $item;
	}

	$result = array();

	foreach ($available as $availableMigration) {
		$name = $availableMigration['name'];
		$checksum = $availableMigration['checksum'];

		$status = lt_migrations_compare_status($name, $checksum);
		$appliedRecord = $appliedByName[$name] ?? null;

		$result[] = array(
			'name' => $name,
			'status' => $status,
			'checksum' => $checksum,
			'batch' => (int) ($appliedRecord['batch'] ?? 0),
			'applied_at' => $appliedRecord['applied_at'] ?? null,
			'execution_time_ms' => (int) ($appliedRecord['execution_time_ms'] ?? 0),
			'error_message' => $appliedRecord['error_message'] ?? null,
			'content' => $availableMigration['content'],
			'size' => $availableMigration['size'],
		);
	}

	return $result;
}

/**
 * Get list of pending migrations
 */
function lt_migrations_pending()
{
	$all = lt_migrations_full_list();
	$pending = array();

	foreach ($all as $migration) {
		if (in_array($migration['status'], array('pending', 'changed'), true)) {
			$pending[] = $migration;
		}
	}

	return $pending;
}

/**
 * Detect potentially dangerous SQL statements
 */
function lt_migrations_detect_dangerous_sql($sql)
{
	$dangerous = array(
		'DROP DATABASE',
		'DROP TABLE',
		'TRUNCATE',
		'DELETE FROM',
		'DROP SCHEMA',
	);

	$sqlUpper = strtoupper(trim($sql));

	foreach ($dangerous as $pattern) {
		if (strpos($sqlUpper, $pattern) === 0) {
			return $pattern;
		}
	}

	return null;
}

/**
 * Validate migration content
 */
function lt_migrations_validate($content, &$errors = array())
{
	$errors = array();

	if (empty($content)) {
		$errors[] = 'Миграция пуста';
		return false;
	}

	// Check for dangerous patterns
	$statements = preg_split('~;\\s*~', trim($content));

	foreach ($statements as $statement) {
		$statement = trim($statement);
		if (empty($statement)) {
			continue;
		}

		// Skip comments
		if (preg_match('~^\\s*--\\s*~', $statement)) {
			continue;
		}

		$dangerous = lt_migrations_detect_dangerous_sql($statement);
		if ($dangerous) {
			$errors[] = 'Обнаружено опасное выражение: '.$dangerous;
		}
	}

	return empty($errors);
}

/**
 * Execute migration (split statements by semicolon)
 */
function lt_migrations_execute($migrationName, $content, &$errorMessage = '')
{
	global $db;

	$errorMessage = '';

	// Validate
	$validationErrors = array();
	if (!lt_migrations_validate($content, $validationErrors)) {
		$errorMessage = implode('; ', $validationErrors);
		return false;
	}

	// Split statements
	$statements = preg_split('~;\\s*(?=\\n|$)~', trim($content));

	$startTime = microtime(true);

	foreach ($statements as $statement) {
		$statement = trim($statement);

		// Skip empty and comment lines
		if (empty($statement) || preg_match('~^\\s*--~', $statement)) {
			continue;
		}

		// Execute
		$result = $db->query($statement, 0);

		if ($result === false) {
			$errorMessage = $db->error;
			$executionTimeMs = (int) ((microtime(true) - $startTime) * 1000);
			lt_migrations_mark_failed($migrationName, $errorMessage);
			return false;
		}

		if (is_resource($result)) {
			$db->free($result);
		}
	}

	$executionTimeMs = (int) ((microtime(true) - $startTime) * 1000);
	$checksum = sha1($content);
	$batch = lt_migrations_get_next_batch();

	lt_migrations_mark_applied($migrationName, $checksum, $batch, $executionTimeMs);

	return true;
}

/**
 * Execute dry run (check if migrations would apply without actually running)
 */
function lt_migrations_dry_run($migrationNames = array())
{
	$migrations = lt_migrations_full_list();
	$result = array(
		'total' => 0,
		'pending' => 0,
		'changed' => 0,
		'failed' => 0,
		'applied' => 0,
		'details' => array(),
	);

	foreach ($migrations as $migration) {
		$result['total']++;

		if (!empty($migrationNames) && !in_array($migration['name'], $migrationNames, true)) {
			continue;
		}

		$status = $migration['status'];

		if ($status === 'pending') {
			$result['pending']++;
		} elseif ($status === 'changed') {
			$result['changed']++;
		} elseif ($status === 'failed') {
			$result['failed']++;
		} elseif ($status === 'applied') {
			$result['applied']++;
		}

		$result['details'][] = array(
			'name' => $migration['name'],
			'status' => $status,
			'batch' => $migration['batch'],
			'applied_at' => $migration['applied_at'],
			'execution_time_ms' => $migration['execution_time_ms'],
			'error_message' => $migration['error_message'],
		);
	}

	return $result;
}
