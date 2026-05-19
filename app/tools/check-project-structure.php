<?php

if (PHP_SAPI !== 'cli') {
	fwrite(STDERR, "CLI only\n");
	exit(1);
}

$root = str_replace('\\', '/', dirname(__DIR__, 2));

$allowlist = array(
	'app',
	'public',
	'database',
	'storage',
	'docs',
	'cache',
	'logs',
	'system',
	'templates',
	'admin',
	'modules',
	'languages',
	'ajax',
	'api',
	'docker',
	'tests',
);
$transientIgnore = array(
	'node_modules',
	'vendor',
	'docker-data',
	'coverage',
	'tmp',
);

$entries = @scandir($root);
if (!is_array($entries)) {
	fwrite(STDERR, "ERROR: unable to read project root: ".$root."\n");
	exit(1);
}

$dirs = array();
foreach ($entries as $entry) {
	if ($entry === '.' || $entry === '..') {
		continue;
	}
	if ($entry[0] === '.') {
		continue;
	}
	$path = $root.'/'.$entry;
	if (!is_dir($path)) {
		continue;
	}
	if (in_array($entry, $transientIgnore, true)) {
		continue;
	}
	$dirs[] = $entry;
}

sort($dirs);
$unknown = array_values(array_diff($dirs, $allowlist));

if (!$unknown) {
	echo "OK: структура корневых директорий соответствует политике проекта.\n";
	exit(0);
}

echo "ОШИБКА: обнаружены неразрешённые директории в корне проекта:\n";
foreach ($unknown as $name) {
	echo " - ".$name."\n";
}

echo "Разрешённые корневые директории:\n";
foreach ($allowlist as $name) {
	echo " - ".$name."\n";
}
echo "Подсказка: новые модули нужно размещать внутри app/public/database/storage/docs.\n";

exit(1);
