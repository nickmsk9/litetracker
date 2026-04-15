<?php
// Для Docker по умолчанию: root без пароля и база lite.
$mysql = array(
	'host' => getenv('LITETRACKER_DB_HOST') ?: 'db',
	'user' => getenv('LITETRACKER_DB_USER') ?: 'root',
	'password' => getenv('LITETRACKER_DB_PASSWORD') ?: '',
	'db' => getenv('LITETRACKER_DB_NAME') ?: 'lite',
	'charset' => getenv('LITETRACKER_DB_CHARSET') ?: 'utf8mb4',
);
?>
