<?php

if (PHP_SAPI !== 'cli') {
	fwrite(STDERR, "CLI only\n");
	exit(1);
}

$rootDir = dirname(__DIR__, 2);
$_SERVER['DOCUMENT_ROOT'] = $rootDir;
$_SERVER['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
$_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/app/tools/seed_demo_activity.php';

require $rootDir.'/system/init.php';

const DEMO_TORRENT_PREFIX = '[DEMO] ';
const DEMO_TORRENT_COUNT = 20;
const DEMO_PASSWORD = 'demo12345';

function demo_seed_escape_csv(array $values)
{
	$values = array_values(array_filter(array_map('trim', $values), function ($value) {
		return $value !== '';
	}));

	return implode(',', $values);
}

function demo_seed_pick(array $values, $index)
{
	if (!$values) {
		return '';
	}

	return $values[$index % count($values)];
}

function demo_seed_datetime($timestamp)
{
	return date('Y-m-d H:i:s', (int) $timestamp);
}

function demo_seed_bencode($value)
{
	if (is_int($value)) {
		return 'i'.$value.'e';
	}

	if (is_string($value)) {
		return strlen($value).':'.$value;
	}

	if (!is_array($value)) {
		return demo_seed_bencode((string) $value);
	}

	$isList = (array_keys($value) === range(0, count($value) - 1));
	if ($isList) {
		$result = 'l';
		foreach ($value as $item) {
			$result .= demo_seed_bencode($item);
		}

		return $result.'e';
	}

	ksort($value, SORT_STRING);
	$result = 'd';
	foreach ($value as $key => $item) {
		$result .= demo_seed_bencode((string) $key);
		$result .= demo_seed_bencode($item);
	}

	return $result.'e';
}

function demo_seed_make_torrent_payload($title, $size)
{
	$pieceLength = 262144;
	$pieceCount = max(1, (int) ceil($size / $pieceLength));
	$pieces = '';

	for ($index = 0; $index < $pieceCount; $index++) {
		$pieces .= sha1($title.'|'.$size.'|'.$index, true);
	}

	$info = array(
		'length' => (int) $size,
		'name' => $title.'.mkv',
		'piece length' => $pieceLength,
		'pieces' => $pieces,
	);

	$payload = array(
		'announce' => 'https://bt.localhost/announce',
		'comment' => 'Synthetic local test torrent for LiteTracker UI checks.',
		'created by' => 'LiteTracker demo seeder',
		'creation date' => time(),
		'info' => $info,
	);

	return array(
		'content' => demo_seed_bencode($payload),
		'infohash' => sha1(demo_seed_bencode($info)),
	);
}

function demo_seed_ensure_directory($path)
{
	if (is_dir($path)) {
		return true;
	}

	$created = mkdir($path, 0777, true);
	return $created || is_dir($path);
}

function demo_seed_remove_asset_variants($baseDir, $pattern)
{
	foreach (glob(rtrim($baseDir, '/').'/'.$pattern) ?: array() as $filePath) {
		if (is_file($filePath)) {
			unlink($filePath);
		}
	}
}

function demo_seed_copy_category_asset($categoryImage, $targetBaseName, $targetDir)
{
	$sourcePath = '';
	$imageName = trim((string) $categoryImage);
	if ($imageName !== '') {
		$sourcePath = dirname(__DIR__).'/public/images/categories/'.$imageName;
	}

	if ($sourcePath === '' || !is_file($sourcePath)) {
		return '';
	}

	$extension = strtolower((string) pathinfo($sourcePath, PATHINFO_EXTENSION));
	if ($extension === '') {
		return '';
	}

	if (!demo_seed_ensure_directory($targetDir)) {
		return '';
	}

	$targetName = $targetBaseName.'.'.$extension;
	$targetPath = rtrim($targetDir, '/').'/'.$targetName;
	copy($sourcePath, $targetPath);

	return $targetName;
}

function demo_seed_cleanup($db)
{
	$demoEmails = array(
		'akisora@demo.local',
		'miorain@demo.local',
		'rentori@demo.local',
		'yukinova@demo.local',
		'kaizen@demo.local',
		'namifox@demo.local',
	);

	$emailList = array();
	foreach ($demoEmails as $email) {
		$emailList[] = "'".$db->safesql($email)."'";
	}

	$demoUserIds = array();
	$userSql = $db->query("SELECT id FROM users WHERE email IN (".implode(',', $emailList).")");
	while ($row = $db->get_row($userSql)) {
		$demoUserIds[] = (int) $row['id'];
	}
	$db->free($userSql);

	$demoTorrentIds = array();
	$torrentSql = $db->query("SELECT id FROM torrents WHERE name LIKE '".$db->safesql(DEMO_TORRENT_PREFIX)."%'");
	while ($row = $db->get_row($torrentSql)) {
		$demoTorrentIds[] = (int) $row['id'];
	}
	$db->free($torrentSql);

	if ($demoTorrentIds) {
		$torrentList = implode(',', $demoTorrentIds);
		$db->query("DELETE FROM comments_torrents WHERE id_torrents IN (".$torrentList.")");
		$db->query("DELETE FROM books WHERE id_torrent IN (".$torrentList.")");
		$db->query("DELETE FROM peers WHERE torrent IN (".$torrentList.")");
		$db->query("DELETE FROM snatched WHERE torrent IN (".$torrentList.")");
		$db->query("DELETE FROM trackers WHERE torrent IN (".$torrentList.")");
		$db->query("DELETE FROM files WHERE id_torrent IN (".$torrentList.")");
		if (lt_table_exists('torrent_ratings')) {
			$db->query("DELETE FROM torrent_ratings WHERE torrent_id IN (".$torrentList.")");
		}
		$db->query("DELETE FROM torrents WHERE id IN (".$torrentList.")");

		foreach ($demoTorrentIds as $torrentId) {
			demo_seed_remove_asset_variants(dirname(__DIR__).'/public/downloads/images', $torrentId.'.*');
			demo_seed_remove_asset_variants(dirname(__DIR__).'/public/downloads/screens', $torrentId.'_*.*');
			$_p = dirname(__DIR__).'/public/downloads/torrents/'.$torrentId.'.torrent';
			if (is_file($_p)) { unlink($_p); }
		}
	}

	if ($demoUserIds) {
		$userList = implode(',', $demoUserIds);
		$db->query("DELETE FROM comments_users WHERE id_users IN (".$userList.") OR id_user IN (".$userList.")");
		$db->query("DELETE FROM sessions WHERE user_id IN (".$userList.")");
		$db->query("DELETE FROM users_blacklist WHERE user_id IN (".$userList.") OR blocked_user_id IN (".$userList.")");
		$db->query("DELETE FROM users WHERE id IN (".$userList.")");
	}

	return array(
		'users_removed' => count($demoUserIds),
		'torrents_removed' => count($demoTorrentIds),
	);
}

function demo_seed_insert_user($db, $user)
{
	$passwordHash = password_hash(DEMO_PASSWORD, PASSWORD_BCRYPT);
	$name = $db->safesql($user['name']);
	$email = $db->safesql($user['email']);
	$profileText = $db->safesql($user['profile_text']);
	$passkey = md5($user['email'].'|'.microtime(true).'|'.mt_rand());
	$added = $db->safesql($user['added']);
	$lastAccess = $db->safesql($user['last_access']);
	$birthday = $db->safesql($user['birthday_date']);
	$ip = (int) sprintf('%u', ip2long($user['ip']));

	$db->query(
		"INSERT INTO users
		(name, avatar, email, password, password_code, ip, class, last_access, added, passkey, uploaded, downloaded, bad_rating, money, bonus, sex, birthday_date, profile_text, notify_comments, download_local_retracker, theme_dark, banned, last_chat, num_messages, num_friends, voice, confirm)
		VALUES
		('{$name}', '', '{$email}', '".$db->safesql($passwordHash)."', '', {$ip}, 1, '{$lastAccess}', '{$added}', '{$passkey}', ".(int) $user['uploaded'].", ".(int) $user['downloaded'].", 0, ".(int) $user['money'].", ".(float) $user['bonus'].", 1, '{$birthday}', '{$profileText}', 1, 1, 0, 0, 0, 0, 0, ".(float) $user['bonus'].", 1)"
	);

	return array(
		'id' => (int) $db->insert_id(),
		'name' => $user['name'],
		'email' => $user['email'],
		'passkey' => $passkey,
		'ip' => $ip,
	);
}

function demo_seed_insert_session($db, $userId, $ip, $lastAccess)
{
	$db->query(
		"INSERT INTO sessions (session_id, user_id, last_access, ip, user_agent, php_self)
		VALUES ('".md5('demo-session|'.$userId.'|'.$lastAccess)."', ".(int) $userId.", '".$db->safesql($lastAccess)."', ".(int) $ip.", 'LiteTracker Demo Seeder', '/index.php')"
	);
}

function demo_seed_insert_torrent($db, $torrent)
{
	$db->query(
		"INSERT INTO torrents
		(banned, name, filename, size, downloaded, descr, infohash, tags, id_category, id_user, added, image, multi, completed, last_action, screen_1, screen_2, screen_3, screen_4, video_vkontakte, news, content_type, subtitles, languages, genres, meta_info, countries, type, num_files)
		VALUES
		('0', '".$db->safesql($torrent['name'])."', '".$db->safesql($torrent['filename'])."', ".(int) $torrent['size'].", ".(int) $torrent['downloaded'].", '".$db->safesql($torrent['descr'])."', '".$db->safesql($torrent['infohash'])."', '".$db->safesql($torrent['tags'])."', ".(int) $torrent['id_category'].", ".(int) $torrent['id_user'].", '".$db->safesql($torrent['added'])."', '".$db->safesql($torrent['image'])."', '0', ".(int) $torrent['completed'].", '".$db->safesql($torrent['last_action'])."', '".$db->safesql($torrent['screen_1'])."', '', '', '', '', ".(int) $torrent['news'].", '".$db->safesql($torrent['content_type'])."', '".$db->safesql($torrent['subtitles'])."', '".$db->safesql($torrent['languages'])."', '".$db->safesql($torrent['genres'])."', '".$db->safesql($torrent['meta_info'])."', '".$db->safesql($torrent['countries'])."', 'single', ".(int) $torrent['num_files'].")"
	);

	return (int) $db->insert_id();
}

function demo_seed_insert_torrent_file_rows($db, $torrentId, $title, $size, $numFiles)
{
	$numFiles = max(1, (int) $numFiles);
	$remaining = (int) $size;

	for ($index = 1; $index <= $numFiles; $index++) {
		$fileSize = ($index === $numFiles ? $remaining : max(524288000, (int) floor($remaining / ($numFiles - $index + 1))));
		$remaining -= $fileSize;
		$fileName = sprintf('%s.part%02d.mkv', preg_replace('~[^a-z0-9]+~i', '.', strtolower($title)), $index);

		$db->query(
			"INSERT INTO files (id_torrent, filename, size)
			VALUES (".(int) $torrentId.", '".$db->safesql($fileName)."', ".(int) $fileSize.")"
		);
	}
}

function demo_seed_insert_tracker($db, $torrentId, $seeders, $leechers)
{
	$db->query(
		"INSERT INTO trackers (torrent, tracker, seeders, leechers, lastchecked, state)
		VALUES (".(int) $torrentId.", 'localhost', ".(int) $seeders.", ".(int) $leechers.", ".time().", '')"
	);
}

function demo_seed_insert_peer($db, $torrentId, $user, $torrentSize, $isSeeder, $timestamp, $index)
{
	$downloaded = ($isSeeder ? (int) $torrentSize : (int) floor($torrentSize * (0.15 + (0.55 * (($index % 5) / 4)))));
	$uploaded = ($isSeeder ? $downloaded + mt_rand(524288000, 3221225472) : mt_rand(104857600, 2147483648));
	$toGo = ($isSeeder ? 0 : max(0, (int) $torrentSize - $downloaded));
	$started = demo_seed_datetime($timestamp - mt_rand(1800, 7200));
	$lastAction = demo_seed_datetime($timestamp);
	$prevAction = demo_seed_datetime($timestamp - mt_rand(120, 1200));
	$peerId = substr('-LTDEMO-'.md5($torrentId.'|'.$user['id'].'|'.$index), 0, 20);

	$db->query(
		"INSERT INTO peers (torrent, peer_id, ip, port, uploaded, downloaded, uploadoffset, downloadoffset, to_go, seeder, started, last_action, prev_action, connectable, userid, agent, finishedat, passkey)
		VALUES
		(".(int) $torrentId.", '".$db->safesql($peerId)."', '".$db->safesql(long2ip((int) $user['ip']))."', ".(int) (51000 + $index).", ".(int) $uploaded.", ".(int) $downloaded.", 0, 0, ".(int) $toGo.", ".($isSeeder ? 1 : 0).", '".$db->safesql($started)."', '".$db->safesql($lastAction)."', '".$db->safesql($prevAction)."', 1, ".(int) $user['id'].", 'LiteTracker Demo Seeder', ".($isSeeder ? (int) $timestamp : 0).", '".$db->safesql($user['passkey'])."')"
	);
}

function demo_seed_insert_snatched($db, $torrentId, $userId, $timestamp, $size, $finished)
{
	$downloaded = ($finished ? (int) $size : (int) floor($size * 0.65));
	$uploaded = ($finished ? (int) floor($size * 1.2) : (int) floor($size * 0.15));

	$db->query(
		"INSERT INTO snatched (userid, torrent, uploaded, downloaded, startedat, completedat, finished)
		VALUES (".(int) $userId.", ".(int) $torrentId.", ".(int) $uploaded.", ".(int) $downloaded.", ".(int) ($timestamp - mt_rand(3600, 172800)).", ".($finished ? (int) $timestamp : 0).", ".($finished ? 1 : 0).")"
	);
}

function demo_seed_insert_bookmark($db, $torrentId, $userId, $date)
{
	$db->query(
		"INSERT INTO books (id_torrent, id_user, date)
		VALUES (".(int) $torrentId.", ".(int) $userId.", '".$db->safesql($date)."')"
	);
}

function demo_seed_insert_torrent_comment($db, $torrentId, $userId, $date, $text, $parentId = 0)
{
	$db->query(
		"INSERT INTO comments_torrents (id_torrents, id_user, date, text, parent_id, id_user_edit, date_edit)
		VALUES (".(int) $torrentId.", ".(int) $userId.", '".$db->safesql($date)."', '".$db->safesql($text)."', ".(int) $parentId.", ".(int) $userId.", '".$db->safesql($date)."')"
	);

	return (int) $db->insert_id();
}

function demo_seed_insert_wall_comment($db, $profileUserId, $userId, $date, $text, $parentId = 0)
{
	$db->query(
		"INSERT INTO comments_users (id_users, id_user, date, text, parent_id, id_user_edit, date_edit)
		VALUES (".(int) $profileUserId.", ".(int) $userId.", '".$db->safesql($date)."', '".$db->safesql($text)."', ".(int) $parentId.", 0, NULL)"
	);

	return (int) $db->insert_id();
}

function demo_seed_insert_rating($db, $torrentId, $userId, $rating, $ip, $date)
{
	if (!lt_table_exists('torrent_ratings')) {
		return;
	}

	$db->query(
		"INSERT INTO torrent_ratings (torrent_id, user_id, rating, ip, date)
		VALUES (".(int) $torrentId.", ".(int) $userId.", ".(int) $rating.", '".$db->safesql(long2ip((int) $ip))."', '".$db->safesql($date)."')"
	);
}

function demo_seed_update_torrent_assets($db, $torrentId, $imageName, $screenName, $infohash)
{
	$db->query(
		"UPDATE torrents
		SET image = '".$db->safesql($imageName)."',
			screen_1 = '".$db->safesql($screenName)."',
			infohash = '".$db->safesql($infohash)."'
		WHERE id = ".(int) $torrentId
	);
}

$cleanupStats = demo_seed_cleanup($db);

$userSeeds = array(
	array('name' => 'AkiSora', 'email' => 'akisora@demo.local', 'profile_text' => 'Люблю сезонные онгоинги, коллекционирую любимые опенинги и чаще всего отвечаю в комментариях вечером.', 'ip' => '10.20.0.11', 'birthday_date' => '1998-03-12'),
	array('name' => 'MioRain', 'email' => 'miorain@demo.local', 'profile_text' => 'Собираю аккуратные релизы с субтитрами и проверяю, чтобы в описании было всё по делу.', 'ip' => '10.20.0.12', 'birthday_date' => '1999-07-19'),
	array('name' => 'RenTori', 'email' => 'rentori@demo.local', 'profile_text' => 'Слежу за новыми фильмами и спецвыпусками, люблю быстрые отзывы без лишнего шума.', 'ip' => '10.20.0.13', 'birthday_date' => '1997-11-05'),
	array('name' => 'YukiNova', 'email' => 'yukinova@demo.local', 'profile_text' => 'Смотрю приключения и фантастику, обычно проверяю стену и закладки утром.', 'ip' => '10.20.0.14', 'birthday_date' => '2000-01-27'),
	array('name' => 'KaiZen', 'email' => 'kaizen@demo.local', 'profile_text' => 'Тестовый пользователь для проверки рейтингов, комментариев и списка активных раздач.', 'ip' => '10.20.0.15', 'birthday_date' => '1996-05-08'),
	array('name' => 'NamiFox', 'email' => 'namifox@demo.local', 'profile_text' => 'Чаще всего отмечаю релизы в закладки и отвечаю коротко, но по существу.', 'ip' => '10.20.0.16', 'birthday_date' => '2001-09-14'),
);

$createdUsers = array();
$now = time();

foreach ($userSeeds as $index => $userSeed) {
	$userSeed['added'] = demo_seed_datetime($now - (86400 * (45 + ($index * 11))));
	$userSeed['last_access'] = demo_seed_datetime($now - mt_rand(60, 7200));
	$userSeed['uploaded'] = mt_rand(60, 260) * 1073741824;
	$userSeed['downloaded'] = mt_rand(8, 45) * 1073741824;
	$userSeed['money'] = mt_rand(50, 550);
	$userSeed['bonus'] = mt_rand(90, 420) / 10;
	$createdUsers[] = demo_seed_insert_user($db, $userSeed);
}

foreach ($createdUsers as $index => $user) {
	if ($index < 4) {
		demo_seed_insert_session($db, $user['id'], $user['ip'], demo_seed_datetime($now - mt_rand(30, 600)));
	}
}

$animeTitles = array(
	'Ночной Архив',
	'Сад Комет',
	'Город Тихих Масок',
	'Почтальон Из Облаков',
	'Клинок Летнего Дождя',
	'Пульс Стеклянной Башни',
	'Последний Кадр Рассвета',
	'Радио На Краю Моря',
	'Хроники Янтарной Станции',
	'Тетрадь Полярного Ветра',
	'Дом Для Заблудших Звезд',
	'Фонарь На Перроне 7',
	'Сигнал Из Лунной Бухты',
	'Механика Снов',
	'Письма С Поднебесья',
	'Второе Июльское Небо',
	'Маршрут До Созвездия',
	'Ласточка И Часовщик',
	'Эхо В Оранжерее',
	'Северный Экспресс До Весны',
);

$descriptions = array(
	'История о команде школьников, которые случайно открывают доступ к закрытому архиву воспоминаний и пытаются понять, почему город начал забывать собственное прошлое.',
	'Небольшая студия озвучки получает шанс спасти любимый сериал, но для этого героям приходится объединиться с людьми, которых они раньше обходили стороной.',
	'После странного метеоритного дождя привычные маршруты города меняются, а каждая ночь приносит новые правила и новые обещания.',
	'Главная героиня работает на воздушной почте и однажды получает письмо, адресованное человеку, исчезнувшему много лет назад.',
	'Команда курьеров на магнитной железной дороге сталкивается с таинственным пассажиром, который знает о них больше, чем положено.',
	'Молодой механик собирает устройство для записи снов, и очень быстро становится понятно, что некоторые чужие сны совсем не хотят оставаться снами.',
);

$commentRoots = array(
	'Очень аккуратный релиз, спасибо за оформление.',
	'Забрал в закладки, позже отпишусь после просмотра.',
	'Описание получилось цепляющим, сразу захотелось открыть детали.',
	'Сортировка по раздающим теперь смотрится живее, этот релиз как раз в тему.',
	'Понравилось, что есть и комментарии, и немного активности на стенах.',
	'Для тестов карточка отличная, есть на чём проверить интерфейс.',
);

$commentReplies = array(
	'Да, тоже отметил себе, особенно удобно смотреть в компактном виде.',
	'Согласен, на такой карточке сразу видно метаданные и активность.',
	'Я ещё рейтинг проверил, всё обновляется как ожидалось.',
	'Позже напишу впечатления, но пока выглядит очень убедительно.',
	'Хороший пример для проверки страницы деталей и профиля автора.',
	'Тоже заметил, что список комментариев стал куда полезнее для тестов.',
);

$wallMessages = array(
	'Заглянул на стену, интерфейс ответов работает отлично.',
	'Оставляю тестовый след, чтобы было что проверять в профиле.',
	'Неплохой профиль, особенно когда есть живая стена и свежие даты.',
	'Смотрел твои последние раздачи, карточки выглядят стабильно.',
	'Добавил пару релизов в закладки, позже приду с отзывом.',
	'Проверяю быстрые ответы на стене, всё выглядит аккуратно.',
);

$categoryRow = $db->super_query("SELECT id, name, image FROM categories WHERE name = 'Аниме' LIMIT 1");
$categoryId = (int) ($categoryRow['id'] ?? 6);
$categoryName = (string) ($categoryRow['name'] ?? 'Аниме');
$categoryImage = (string) ($categoryRow['image'] ?? '6.gif');

$createdTorrentIds = array();

for ($index = 0; $index < DEMO_TORRENT_COUNT; $index++) {
	$title = DEMO_TORRENT_PREFIX.$animeTitles[$index];
	$releaseType = demo_seed_pick(array('tv', 'movie', 'ova', 'special'), $index);
	$quality = demo_seed_pick(array('1080p', '720p', 'WEB-DL', 'BDRip 1080p'), $index);
	$duration = demo_seed_pick(array('24 мин', '25 мин', '47 мин', '1 ч 28 мин'), $index);
	$episodes = ($releaseType === 'movie' ? '1 из 1' : demo_seed_pick(array('10 из 10', '12 из 12', '13 из 13', '8 из 8'), $index));
	$year = 2024 + ($index % 3);
	$size = (($releaseType === 'movie' ? mt_rand(8, 18) : mt_rand(2, 7)) * 1073741824) + mt_rand(104857600, 943718400);
	$addedTs = $now - (($index + 1) * 3660);
	$lastActionTs = $addedTs + mt_rand(900, 14400);
	$uploader = $createdUsers[$index % count($createdUsers)];
	$genres = array_slice(array('adventure', 'fantasy', 'drama', 'comedy', 'science_fiction', 'animation', 'mystic'), $index % 3, 3);
	$languages = array('japanese', 'russian');
	$subtitles = array('russian', 'english');
	$metaInfo = array_slice(array('licensed', 'hevc', 'uncensored', 'has_torrent'), 0, 1 + ($index % 2));
	$descriptionValues = array(
		'Год выхода' => (string) $year,
		'Количество эпизодов' => $episodes,
		'Продолжительность' => $duration,
		'Режиссер' => demo_seed_pick(array('Морита Сюн', 'Окада Рина', 'Фудзивара Кэй', 'Хосино Ая'), $index),
		'Описание' => demo_seed_pick($descriptions, $index),
		'Формат' => 'MKV',
		'Качество' => $quality,
		'Видео' => demo_seed_pick(array('H.264, 1920x1080, ~6500 Кбит/с', 'H.265, 1920x1080, ~4200 Кбит/с', 'H.264, 1280x720, ~3500 Кбит/с'), $index),
	);

	$descr = lt_torrent_description_build($categoryName, $descriptionValues);
	$torrentPayload = demo_seed_make_torrent_payload($title, $size);
	$fileCount = ($releaseType === 'movie' ? 1 : (($index % 3) + 1));

	$torrentId = demo_seed_insert_torrent($db, array(
		'name' => $title,
		'filename' => 'demo-release-'.sprintf('%02d', $index + 1).'.torrent',
		'size' => $size,
		'downloaded' => mt_rand(60, 540),
		'descr' => $descr,
		'infohash' => $torrentPayload['infohash'],
		'tags' => 'демо,'.strtolower($categoryName).','.implode(',', array('приключения', 'фантастика', 'сезон')),
		'id_category' => $categoryId,
		'id_user' => $uploader['id'],
		'added' => demo_seed_datetime($addedTs),
		'image' => '',
		'completed' => mt_rand(10, 120),
		'last_action' => demo_seed_datetime($lastActionTs),
		'screen_1' => '',
		'news' => ($index < 6 ? 1 : 0),
		'content_type' => $releaseType,
		'subtitles' => demo_seed_escape_csv($subtitles),
		'languages' => demo_seed_escape_csv($languages),
		'genres' => demo_seed_escape_csv($genres),
		'meta_info' => demo_seed_escape_csv($metaInfo),
		'countries' => 'japan',
		'num_files' => $fileCount,
	));

	$coverName = demo_seed_copy_category_asset($categoryImage, (string) $torrentId, $rootDir.'/public/downloads/images');
	$screenName = demo_seed_copy_category_asset($categoryImage, $torrentId.'_0', $rootDir.'/public/downloads/screens');
	demo_seed_update_torrent_assets($db, $torrentId, $coverName, $screenName, $torrentPayload['infohash']);

	demo_seed_ensure_directory($rootDir.'/public/downloads/torrents');
	file_put_contents($rootDir.'/public/downloads/torrents/'.$torrentId.'.torrent', $torrentPayload['content']);

	demo_seed_insert_torrent_file_rows($db, $torrentId, $animeTitles[$index], $size, $fileCount);

	$seeders = 2 + ($index % 4);
	$leechers = $index % 3;
	demo_seed_insert_tracker($db, $torrentId, $seeders, $leechers);

	for ($peerIndex = 0; $peerIndex < ($seeders + $leechers); $peerIndex++) {
		$peerUser = $createdUsers[($index + $peerIndex) % count($createdUsers)];
		$isSeeder = ($peerIndex < $seeders);
		demo_seed_insert_peer($db, $torrentId, $peerUser, $size, $isSeeder, $lastActionTs - mt_rand(30, 600), $peerIndex + 1);
	}

	$snatchCount = min(count($createdUsers), 3 + ($index % 3));
	for ($snatchIndex = 0; $snatchIndex < $snatchCount; $snatchIndex++) {
		$snatchUser = $createdUsers[($index + $snatchIndex + 1) % count($createdUsers)];
		demo_seed_insert_snatched($db, $torrentId, $snatchUser['id'], $lastActionTs - mt_rand(600, 43200), $size, ($snatchIndex < 2));
	}

	for ($bookmarkIndex = 0; $bookmarkIndex < 2; $bookmarkIndex++) {
		$bookmarkUser = $createdUsers[($index + $bookmarkIndex + 2) % count($createdUsers)];
		if ($bookmarkUser['id'] === $uploader['id']) {
			continue;
		}

		demo_seed_insert_bookmark($db, $torrentId, $bookmarkUser['id'], demo_seed_datetime($lastActionTs - mt_rand(120, 3600)));
	}

	$rootCommentId = demo_seed_insert_torrent_comment(
		$db,
		$torrentId,
		$createdUsers[($index + 1) % count($createdUsers)]['id'],
		demo_seed_datetime($lastActionTs - mt_rand(900, 2400)),
		demo_seed_pick($commentRoots, $index)
	);
	demo_seed_insert_torrent_comment(
		$db,
		$torrentId,
		$createdUsers[($index + 2) % count($createdUsers)]['id'],
		demo_seed_datetime($lastActionTs - mt_rand(300, 1200)),
		demo_seed_pick($commentReplies, $index),
		$rootCommentId
	);
	demo_seed_insert_torrent_comment(
		$db,
		$torrentId,
		$createdUsers[($index + 3) % count($createdUsers)]['id'],
		demo_seed_datetime($lastActionTs - mt_rand(120, 900)),
		demo_seed_pick($commentRoots, $index + 2)
	);

	for ($ratingIndex = 0; $ratingIndex < 3; $ratingIndex++) {
		$ratingUser = $createdUsers[($index + $ratingIndex + 1) % count($createdUsers)];
		demo_seed_insert_rating($db, $torrentId, $ratingUser['id'], 4 + (($index + $ratingIndex) % 2), $ratingUser['ip'], demo_seed_datetime($lastActionTs - mt_rand(60, 1800)));
	}

	$createdTorrentIds[] = $torrentId;
}

foreach ($createdUsers as $index => $profileUser) {
	$rootWallCommentId = demo_seed_insert_wall_comment(
		$db,
		$profileUser['id'],
		$createdUsers[($index + 1) % count($createdUsers)]['id'],
		demo_seed_datetime($now - mt_rand(1200, 5400)),
		demo_seed_pick($wallMessages, $index)
	);
	demo_seed_insert_wall_comment(
		$db,
		$profileUser['id'],
		$createdUsers[($index + 2) % count($createdUsers)]['id'],
		demo_seed_datetime($now - mt_rand(300, 1800)),
		demo_seed_pick($wallMessages, $index + 2),
		$rootWallCommentId
	);
	demo_seed_insert_wall_comment(
		$db,
		$profileUser['id'],
		$createdUsers[($index + 3) % count($createdUsers)]['id'],
		demo_seed_datetime($now - mt_rand(60, 900)),
		demo_seed_pick($wallMessages, $index + 4)
	);
}

echo "Cleanup: removed ".$cleanupStats['users_removed']." demo users and ".$cleanupStats['torrents_removed']." demo torrents.\n";
echo "Created ".count($createdUsers)." demo users and ".count($createdTorrentIds)." demo torrents.\n";
echo "Demo password for all seeded users: ".DEMO_PASSWORD."\n";
echo "Users:\n";
foreach ($createdUsers as $user) {
	echo " - ".$user['name']." <".$user['email'].">\n";
}
