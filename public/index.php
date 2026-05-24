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
$relativePath = rawurldecode($relativePath);

if (strpos($relativePath, "\0") !== false || strpos($relativePath, '..') !== false || preg_match('~(?:^|/)\.[^/]*~', $relativePath)) {
	http_response_code(400);
	exit('Bad Request');
}

$blockedPathPattern = '~^(?:app|storage|docs|database|system|logs|docker-data|docker|vendor|tests|node_modules|\.git)(?:/|$)~i';
$blockedFilePattern = '~^(?:\.env(?:\..*)?|composer\..*|package(?:-lock)?\.json|phpunit\.xml|docker-compose(?:\.[^/]+)?\.ya?ml|Dockerfile|README\.md)$~i';

if (preg_match($blockedPathPattern, $relativePath) || preg_match($blockedFilePattern, $relativePath)) {
	http_response_code(403);
	exit('Forbidden');
}

$publicEntrypoints = array_fill_keys(array(
	'404.php',
	'admin.php',
	'announce.php',
	'autoclean.php',
	'avatars.php',
	'browse.php',
	'categories.php',
	'check_release.php',
	'comments.last.php',
	'comments.take.php',
	'complaint.php',
	'copyright.php',
	'details.php',
	'disclaimer.php',
	'donate.php',
	'download.php',
	'edit.php',
	'edit_priv.php',
	'exit.php',
	'faq.php',
	'feedback.php',
	'index.php',
	'ip.util.php',
	'language.php',
	'login.php',
	'messages.php',
	'multitracker_accounts.php',
	'my.book.php',
	'my.friends.php',
	'my.mail.php',
	'my.releases.php',
	'my.setting.php',
	'my.setting.take.php',
	'news.php',
	'notifications.php',
	'notify.php',
	'profile.php',
	'rating.php',
	'rss.php',
	'rules.php',
	'scrape.php',
	'search_query.php',
	'sessions.php',
	'shop.php',
	'signup.php',
	'static_pages.php',
	'update.peers.php',
	'upload.php',
	'user_add.php',
	'userdetails.php',
	'users.php',
	'wall_reports.php',
	'ajax/captcha.php',
	'ajax/comments.php',
	'ajax/profile.php',
	'ajax/tags.php',
	'api/bookmarks.php',
	'api/metadata_search.php',
	'api/notifications/archive.php',
	'api/notifications/count.php',
	'api/notifications/list.php',
	'api/notifications/mark_all_read.php',
	'api/notifications/mark_read.php',
	'api/ratings.php',
	'api/tags_suggest.php',
), true);

if (!isset($publicEntrypoints[$relativePath])) {
	http_response_code(404);
	if (is_file($projectRoot.'/404.php')) {
		chdir($projectRoot);
		$_SERVER['DOCUMENT_ROOT'] = $projectRoot;
		require $projectRoot.'/404.php';
		exit;
	}
	exit('Not Found');
}

$targetBase = $projectRoot;
if (strpos($relativePath, 'ajax/') === 0 || strpos($relativePath, 'api/') === 0) {
	$targetBase = $projectRoot.'/public';
}

$targetPath = $targetBase.'/'.$relativePath;
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
