<?php
declare(strict_types=1);

$projectRoot = dirname(__DIR__);
$requestPath = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
$requestPath = (is_string($requestPath) ? $requestPath : '/');
$requestPath = '/'.ltrim($requestPath, '/');

if ($requestPath === '/public') {
	$requestPath = '/';
} elseif (strpos($requestPath, '/public/') === 0) {
	$requestPath = '/'.substr($requestPath, 8);
}

$relativePath = ltrim($requestPath, '/');
if ($relativePath === '') {
	$relativePath = 'index.php';
}

if (strpos($relativePath, "\0") !== false || strpos($relativePath, '..') !== false) {
	http_response_code(400);
	exit('Bad Request');
}

$blockedPathPattern = '~^(?:database|system|logs|docker-data|docker|vendor|tests|\.git)(?:/|$)~i';
$blockedFilePattern = '~^(?:\.env(?:\..*)?|composer\.(?:json|lock)|docker-compose(?:\.[^/]+)?\.ya?ml|Dockerfile)$~i';

if (preg_match($blockedPathPattern, $relativePath) || preg_match($blockedFilePattern, $relativePath)) {
	http_response_code(403);
	exit('Forbidden');
}

$targetPath = $projectRoot.'/'.$relativePath;
$targetReal = realpath($targetPath);
$rootReal = realpath($projectRoot);

if ($targetReal === false || $rootReal === false || strpos($targetReal, $rootReal.DIRECTORY_SEPARATOR) !== 0 || !is_file($targetReal) || substr($targetReal, -4) !== '.php') {
	http_response_code(404);
	if (is_file($projectRoot.'/404.php')) {
		chdir($projectRoot);
		$_SERVER['DOCUMENT_ROOT'] = $projectRoot;
		require $projectRoot.'/404.php';
		exit;
	}
	exit('Not Found');
}

chdir($projectRoot);
$_SERVER['DOCUMENT_ROOT'] = $projectRoot;
require $targetReal;
