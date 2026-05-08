<?php
/*
===================================================================
Назначение: Просмотр релиза
===================================================================
*/

//Подключаем главный системный файл
require 'system/init.php';

$GLOBALS['LITETRACKER_HIDE_TOP_BLOCKS'] = true;
$GLOBALS['LITETRACKER_HIDE_BOTTOM_BLOCKS'] = true;
$GLOBALS['LITETRACKER_HIDE_STANDARD_SIDEBAR'] = true;

function lt_details_lower($value)
{
	$value = trim((string) $value);

	if ($value === '') {
		return '';
	}

	return (function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value));
}

function lt_details_label_key($label)
{
	$label = strip_tags((string) $label);
	$label = str_replace(':', '', $label);
	$label = preg_replace('/\s+/u', ' ', trim($label));

	return lt_details_lower($label);
}

function lt_details_info_heading($categoryName)
{
	$name = lt_details_lower($categoryName);
	if ($name === '') {
		return 'Информация о релизе';
	}

	$map = array(
		'аниме' => 'Информация об аниме',
		'фильмы' => 'Информация о фильме',
		'телешоу' => 'Информация о телешоу',
		'музыка' => 'Информация о релизе',
		'игры' => 'Информация об игре',
		'программы' => 'Информация о программе',
	);

	return (!empty($map[$name]) ? $map[$name] : 'Информация о релизе');
}

function lt_details_format_date_label($date)
{
	$timestamp = strtotime((string) $date);
	if (!$timestamp) {
		return trim((string) convent_date((string) $date));
	}

	static $months = array(
		1 => 'января',
		2 => 'февраля',
		3 => 'марта',
		4 => 'апреля',
		5 => 'мая',
		6 => 'июня',
		7 => 'июля',
		8 => 'августа',
		9 => 'сентября',
		10 => 'октября',
		11 => 'ноября',
		12 => 'декабря',
	);

	return date('j', $timestamp).' '.$months[(int) date('n', $timestamp)].' в '.date('H:i', $timestamp);
}

function lt_details_render_text_html($text)
{
	$html = trim((string) format_comment((string) $text));
	$html = preg_replace('~^(?:<br\s*/?>\s*)+|(?:\s*<br\s*/?>)+$~i', '', $html);

	return $html;
}

function lt_details_local_summary($text, $maxLength = 320)
{
	$text = trim(preg_replace('/\s+/u', ' ', strip_tags((string) format_comment((string) $text))));
	if ($text === '') {
		return '';
	}

	$sentences = preg_split('/(?<=[.!?])\s+/u', $text, 3);
	$summary = trim(implode(' ', array_slice((array) $sentences, 0, 2)));
	if ($summary === '') {
		$summary = $text;
	}

	if (function_exists('mb_strlen') && mb_strlen($summary, 'UTF-8') > $maxLength) {
		return rtrim(mb_substr($summary, 0, $maxLength, 'UTF-8')).'...';
	}

	if (strlen($summary) > $maxLength) {
		return rtrim(substr($summary, 0, $maxLength)).'...';
	}

	return $summary;
}

function lt_details_collect_screens($torrent)
{
	$result = array();

	for ($index = 1; $index <= 4; $index++) {
		$name = trim((string) ($torrent['screen_'.$index] ?? ''));
		if ($name === '') {
			continue;
		}

		if (is_file('public/downloads/screens/'.$name)) {
			$path = 'public/downloads/screens/'.$name;
		} else {
			$path = $name;
		}

		$result[] = array(
			'id' => $index,
			'path' => $path,
			'title' => 'Скриншот №'.$index,
		);
	}

	return $result;
}

function lt_details_parse_description($text)
{
	$text = (string) $text;
	$lines = preg_split('/\r\n|\r|\n/', $text);
	$sections = array(
		array(
			'label' => '',
			'items' => array(),
		),
	);
	$intro = array();
	$currentSection = 0;
	$currentItem = -1;

	foreach ($lines as $line) {
		$line = trim((string) $line);
		if ($line === '') {
			if ($currentItem >= 0) {
				$currentValue = $sections[$currentSection]['items'][$currentItem]['value'];
				if ($currentValue !== '' && substr($currentValue, -1) !== "\n") {
					$sections[$currentSection]['items'][$currentItem]['value'] .= "\n";
				}
			}
			continue;
		}

		if (preg_match('/^\[u\](.+?)\[\/u\]$/iu', $line, $match)) {
			$sections[] = array(
				'label' => trim((string) $match[1]),
				'items' => array(),
			);
			$currentSection = count($sections) - 1;
			$currentItem = -1;
			continue;
		}

		if (preg_match('/^\[b\](.+?)\[\/b\]\s*(.*)$/iu', $line, $match)) {
			$label = trim((string) $match[1]);
			if (substr($label, -1) === ':') {
				$label = rtrim(substr($label, 0, -1));
			}

			$sections[$currentSection]['items'][] = array(
				'label' => $label,
				'value' => trim((string) $match[2]),
			);
			$currentItem = count($sections[$currentSection]['items']) - 1;
			continue;
		}

		if ($currentItem >= 0) {
			$currentValue = $sections[$currentSection]['items'][$currentItem]['value'];
			$sections[$currentSection]['items'][$currentItem]['value'] = trim($currentValue."\n".$line);
			continue;
		}

		$intro[] = $line;
	}

	return array(
		'intro' => $intro,
		'sections' => $sections,
	);
}

function lt_details_has_item($sections, $labelKeys)
{
	$labelKeys = (array) $labelKeys;

	foreach ((array) $sections as $section) {
		foreach ((array) ($section['items'] ?? array()) as $item) {
			if (in_array(lt_details_label_key($item['label'] ?? ''), $labelKeys, true)) {
				return true;
			}
		}
	}

	return false;
}

function lt_details_extract_item(&$sections, $labelKeys)
{
	$labelKeys = (array) $labelKeys;

	foreach ($sections as $sectionIndex => $section) {
		foreach ((array) ($section['items'] ?? array()) as $itemIndex => $item) {
			if (!in_array(lt_details_label_key($item['label'] ?? ''), $labelKeys, true)) {
				continue;
			}

			$value = trim((string) ($item['value'] ?? ''));
			unset($sections[$sectionIndex]['items'][$itemIndex]);
			$sections[$sectionIndex]['items'] = array_values($sections[$sectionIndex]['items']);

			return $value;
		}
	}

	return '';
}

function lt_details_append_item(&$sections, $sectionLabel, $label, $value)
{
	$value = trim((string) $value);
	if ($value === '') {
		return;
	}

	$sectionLabel = trim((string) $sectionLabel);
	$sectionIndex = null;

	foreach ($sections as $index => $section) {
		if (trim((string) ($section['label'] ?? '')) === $sectionLabel) {
			$sectionIndex = $index;
			break;
		}
	}

	if ($sectionIndex === null) {
		$sections[] = array(
			'label' => $sectionLabel,
			'items' => array(),
		);
		$sectionIndex = count($sections) - 1;
	}

	$sections[$sectionIndex]['items'][] = array(
		'label' => trim((string) $label),
		'value' => $value,
	);
}

function lt_details_rating_table_ready()
{
	global $db;
	static $ready = null;

	if ($ready !== null) {
		return $ready;
	}

	$tableName = 'torrent_ratings';
	$row = $db->super_query("SHOW TABLES LIKE '".$db->safesql($tableName)."'");
	if (!empty($row)) {
		$ready = true;
		return true;
	}

	$db->query(
		"CREATE TABLE IF NOT EXISTS `".$tableName."` (
			`id` int unsigned NOT NULL AUTO_INCREMENT,
			`torrent_id` int unsigned NOT NULL,
			`user_id` int unsigned NOT NULL,
			`rating` tinyint unsigned NOT NULL DEFAULT '0',
			`ip` varchar(64) NOT NULL DEFAULT '',
			`date` datetime NOT NULL,
			PRIMARY KEY (`id`),
			UNIQUE KEY `torrent_user` (`torrent_id`, `user_id`),
			KEY `torrent_rating` (`torrent_id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3"
	);

	$row = $db->super_query("SHOW TABLES LIKE '".$db->safesql($tableName)."'");
	$ready = !empty($row);

	return $ready;
}

function lt_details_views_table_ready()
{
	global $db;
	static $ready = null;

	if ($ready !== null) {
		return $ready;
	}

	$tableName = 'torrent_views';
	if (lt_table_exists($tableName)) {
		$ready = true;
		return true;
	}

	$db->query(
		"CREATE TABLE IF NOT EXISTS `".$tableName."` (
			`id` int unsigned NOT NULL AUTO_INCREMENT,
			`torrent_id` int unsigned NOT NULL,
			`user_id` int unsigned NOT NULL DEFAULT '0',
			`visitor_hash` char(40) NOT NULL DEFAULT '',
			`date` datetime NOT NULL,
			PRIMARY KEY (`id`),
			UNIQUE KEY `torrent_visitor` (`torrent_id`, `visitor_hash`),
			KEY `torrent_id` (`torrent_id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3"
	);

	$row = $db->super_query("SHOW TABLES LIKE '".$db->safesql($tableName)."'");
	$ready = !empty($row);
	return $ready;
}

function lt_details_register_view($torrentId)
{
	global $db, $USER;

	$torrentId = (int) $torrentId;
	if ($torrentId <= 0 || !lt_details_views_table_ready()) {
		return 0;
	}

	$userId = (!empty($USER['id']) ? (int) $USER['id'] : 0);
	$visitorKey = ($userId > 0 ? 'user:'.$userId : 'guest:'.getip().'|'.($_SERVER['HTTP_USER_AGENT'] ?? ''));
	$visitorHash = sha1($visitorKey.'|'.COOKIE_SALT);

	$db->query(
		"INSERT IGNORE INTO torrent_views (torrent_id, user_id, visitor_hash, date)
		VALUES (".$torrentId.", ".$userId.", '".$db->safesql($visitorHash)."', NOW())"
	);

	$row = $db->super_query("SELECT COUNT(*) AS cnt FROM torrent_views WHERE torrent_id = ".$torrentId);
	return (int) ($row['cnt'] ?? 0);
}


if(!empty($USER) && !$PRIV['details_view']) {
	err($language['default_1'] , $language['details_29'] , 1);
}

//Номер торрента
$id = (int)$_GET['id'];



//Запрос к таблице torrents
$db->query("SELECT t.* ,
			COALESCE(SUM(tr.seeders), 0) AS seeders ,
			COALESCE(SUM(tr.leechers), 0) AS leechers ,
			COALESCE(SUM(CASE WHEN tr.tracker='localhost' THEN tr.seeders ELSE 0 END), 0) AS local_seeders_count ,
			COALESCE(SUM(CASE WHEN tr.tracker='localhost' THEN tr.leechers ELSE 0 END), 0) AS local_leechers_count ,
			COALESCE(SUM(CASE WHEN tr.tracker<>'localhost' THEN tr.seeders ELSE 0 END), 0) AS external_seeders_count ,
			COALESCE(SUM(CASE WHEN tr.tracker<>'localhost' THEN tr.leechers ELSE 0 END), 0) AS external_leechers_count ,
			COALESCE(SUM(CASE WHEN tr.tracker<>'localhost' THEN 1 ELSE 0 END), 0) AS external_tracker_count ,
			t.multi,
			IF((SELECT SUM(seeders) FROM trackers WHERE torrent = t.id AND tracker='localhost' GROUP BY tracker) > 0 , true , false) AS local_seeders
			FROM torrents AS t
			LEFT JOIN trackers AS tr ON  tr.torrent = t.id
			WHERE t.id = '".$id."'
			GROUP BY t.id
			");


if(!$db->num_rows() ) {
	err($language['default_1'] , $language['details_19'] , 1);
}

$arr = $db->get_row();

$details_rating_cookie_name = 'lt_torrent_rating_'.(int) $id;
$details_rating_table_ready = lt_details_rating_table_ready();
$details_rating_user_value = 0;

if ($details_rating_table_ready && !empty($USER['id'])) {
	$detailsUserRatingRow = $db->super_query(
		"SELECT rating FROM torrent_ratings WHERE torrent_id = ".(int) $id." AND user_id = ".(int) $USER['id']." LIMIT 1"
	);
	$details_rating_user_value = (int) ($detailsUserRatingRow['rating'] ?? 0);
}

if ($details_rating_user_value <= 0 && empty($USER['id'])) {
	$details_rating_user_value = (int) ($_COOKIE[$details_rating_cookie_name] ?? 0);
}

$details_rating_can_vote = (!empty($USER['id']) && $details_rating_table_ready && $details_rating_user_value <= 0);

if (isset($_GET['rating'])) {
	$ratingValue = (int) $_GET['rating'];

	if (!$USER) {
		header('Location: login.php?referer='.rawurlencode('details.php?id='.(int) $id));
		die();
	}

	if ($ratingValue < 1 || $ratingValue > 5) {
		header('Location: details.php?id='.(int) $id);
		die();
	}

	if (!$details_rating_table_ready) {
		header('Location: details.php?id='.(int) $id);
		die();
	}

	$existingRating = $db->super_query(
		"SELECT id, rating FROM torrent_ratings WHERE torrent_id = ".(int) $id." AND user_id = ".(int) $USER['id']." LIMIT 1"
	);

	if (empty($existingRating['id'])) {
		$db->query(
			"INSERT INTO torrent_ratings (torrent_id, user_id, rating, ip, date)
			VALUES (
				".(int) $id.",
				".(int) $USER['id'].",
				".(int) $ratingValue.",
				'".$db->safesql((string) getip())."',
				NOW()
			)"
		);
		$details_rating_user_value = $ratingValue;
	} else {
		$details_rating_user_value = (int) ($existingRating['rating'] ?? 0);
	}

	lt_set_cookie($details_rating_cookie_name, (string) max(1, $details_rating_user_value), time() + 31536000, false, 'Lax');
	$memcached->delete('torrent_'.(int) $id, 0);
	header('Location: details.php?id='.(int) $id.'&rated=1');
	die();
}

if($arr['banned'] && !$PRIV['details_banned_view']) {
	err($language['default_1'] , $language['details_20'] , 1);
}


/////////////////////////////////////////////////////////
//Информация о файлах
/////////////////////////////////////////////////////////
if(isset($_GET['files'])) {

	$sql  = $db->query("SELECT * FROM files WHERE id_torrent  = ".$id." ORDER BY id");
	if(!$db->num_rows($sql)) {
		err("Ошибка" , "Извините , но наша система не нашла файлов" , 1);
	}

	head('Информация о файлах' , true);
	msg('Здесь показаны все файлы  , которые были найдены в торренте' , '<a href="javascript:history.go(-1);">Вернуться к деталям</a>');
	begin_frame('Информация о файлах');

	//Перебираем в цикле
	echo '<table width="50%" align="center">';
	echo '<tr>
	<td width="10%"><b>Файл</b></td>
	<td ><b>Размер</b></td>
	</tr>';
	while($row = $db->get_row($sql) ) {
		echo '<tr>
			<td>'.htmlspecialchars($row['filename'], ENT_QUOTES, 'UTF-8').'</td><td><b>'.mksize($row['size']).'</b></td>
		</tr>';
	}
	echo '</table>';

	end_frame();
	foot();
	die();
}

/////////////////////////////////////////////////////////
//Информация о пирах
/////////////////////////////////////////////////////////
if(isset($_GET['peers']) ) {
	$sql = $db->query("SELECT * FROM peers  WHERE torrent=".$id." ORDER BY seeder DESC");
	if(!$db->num_rows($sql) ) {
		err($language['default_1']  , 'Никаких соединений не обнаружено' , 1);
	}

	head('Информация о соединениях');
	begin_frame('Информация о соединениях');
	msg('Здесь показаны все соединение , которые контролирует наш трекер' , '<a href="javascript:history.go(-1);">Вернуться к деталям</a>');
	echo '<table>';
	echo '<tr>
	'.($PRIV['ip_util'] ? '<td><b>IP</b></td>' : '').'
	<td><b>Пользователь</b></td>
	<td><b>Раздал</b></td>
	<td><b>Скачал</b></td>
	<td><b>Начало</b></td>
	<td><b>Конец</b></td>
	<td><b>Посл. активность</b></td>
	<td><b>Клиент</b></td>
	<td><b>Статус</b></td>
	</tr>';

	while($row = $db->get_row($sql) ) {
		$user = get_user_info($row['userid']);

		echo '<tr>';
		if($PRIV['ip_util']) {
			echo '<td><a href="ip.util.php?ip='.$row['ip'].'">'.$row['ip'].'</a></td>';
		}
		echo '<td>'.($user ? '<a href="'.profile_href($user['id']).'">'.get_user_color($user['class'] , $user['name']).'</a>' : 'Гость').'</td>';
		echo '<td><font color="green">'.mksize($row['uploaded']).'</font></td>';
		echo '<td><font color="red">'.mksize($row['downloaded']).'</font></td>';
		echo '<td>'.convent_date($row['started']).'</td>';
		echo '<td>'.($row['prev_action'] != '0000-00-00 00:00:00' ? convent_date($row['prev_action']) : 'Не известно').'</td>';
		echo '<td>'.convent_date($row['last_action']).'</td>';
		echo '<td>'.htmlspecialchars($row['agent']).'</td>';
		echo '<td>'.($row['seeder'] ? '<img src="public/images/up.png">Раздающий' : '<img src="public/images/down.png">Качающий').'</td>';
		echo '</tr>';
	}

	echo '</table>';
	end_frame();
	foot();
	die();
}


/////////////////////////////////////////////////////////
//Информация о трекерах
/////////////////////////////////////////////////////////
if(isset($_GET['trackers']) && $arr['multi']) {
	$sql = $db->query("SELECT * FROM trackers WHERE tracker <> 'localhost' AND  torrent=".$id);
	if(!$db->num_rows($sql) ) {
		err($language['default_1']  , 'Трекеров не найдено' , 1);
	}

	head('Информация о трекерах');
	begin_frame('Информация о трекерах');
	msg('Данные могут не соответствовать настоящим' , '<a href="javascript:history.go(-1);">Вернуться к деталям</a>');
	echo '<table>';
	echo '<tr><td><b>Трекер</b></td><td><b>Раздают</b></td><td><b>Качают</b></td><td><b>Дата обновление</b></td></tr>';

	while($row = $db->get_row($sql) ) {
		echo '<tr>';
		echo '<td>'.$row['tracker'].'</td>';
		echo '<td>'.$row['seeders'].'</td>';
		echo '<td>'.$row['leechers'].'</td>';
		echo '<td>'.convent_date(get_date_time($row['lastchecked'])).'</td>';
		echo '</tr>';
	}

	echo '</table>';
	end_frame();
	foot();
	die();
}



/////////////////////////////////////////////////////////
//Общее
/////////////////////////////////////////////////////////
//ID релиза
$id = $arr['id'];
//Обложка
$image = ($arr['image'] ? 'public/downloads/images/'.$arr['image'] : 'public/images/default_avatar.gif');
//Имя релиза
$torrent_name_plain = trim((string) $arr['name']);
$name = htmlspecialchars($torrent_name_plain, ENT_QUOTES, 'UTF-8');

//Хеш релиза
$infohash = $arr['infohash'];

//Время добавления
$date = convent_date($arr['added']);

//Забанен
if($arr['banned']) {
	$banned = '<font color="red">'.$language['default_2'].'</font>';
}else {
	$banned = '<font color="green">'.$language['default_3'].'</font>';
}



//Информация о Категории
$category = categories_array($arr['id_category']);

//Имя категории
$cat_name_plain = trim((string) ($category['name'] ?? ''));
$cat_name = htmlspecialchars($cat_name_plain, ENT_QUOTES, 'UTF-8');

//ID Категории
$cat_id = $category['id'];

//Картинка Категории
$cat_image = $category['image'];

if (!empty($arr['image']) && is_file('public/downloads/images/'.$arr['image'])) {
	$image = 'public/downloads/images/'.$arr['image'];
} elseif (!empty($cat_image) && is_file('public/images/categories/'.$cat_image)) {
	$image = 'public/images/categories/'.$cat_image;
} else {
	$image = 'public/images/default_avatar.gif';
}


//Теги
$tags = tags_echo($arr['tags']);
//Взяли
$downloaded = number_format($arr['downloaded']);
//Скачали
$completed = number_format($arr['completed']);
//Размер
$size = mksize($arr['size']);

//Определяем тип релиза
$type_seeders_array = array();
if($arr['multi']) {
	$type_seeders_array[] = '<font color="red"><b>'.$language['details_21'].'</b></a></font>';
}
if($arr['local_seeders']) {
	$type_seeders_array[] = '<font color="green"><b>'.$language['details_22'].'</b></font>';
}
$type_seeders = implode('+' , $type_seeders_array);


$descr = format_comment($arr['descr']);


/////////////////////////////////////////////////////////
//Пользователь
/////////////////////////////////////////////////////////
$user = get_user_info($arr['id_user']);
//ID пользователя
$id_user = $user['id'];
//Имя пользователя
$user_name = $user['name'];
//Класс пользователя
$user_class = $user['class'];



/////////////////////////////////////////////////////////
//Пиры
/////////////////////////////////////////////////////////
//Раздают
$seeders_count = max(0, (int) ($arr['seeders'] ?? 0));
$seeders = number_format($seeders_count);
//Качают
$leechers_count = max(0, (int) ($arr['leechers'] ?? 0));
$leechers = number_format($leechers_count);
//Пиры
$peers = number_format($seeders_count + $leechers_count);

$details_tracker_rows = array();
$details_external_tracker_count = (int) ($arr['external_tracker_count'] ?? 0);
$details_tracker_update_href = '';
if ($details_external_tracker_count > 0) {
	$details_tracker_update_href = 'update.peers.php?id='.(int) $id.'&return='.rawurlencode('details.php?id='.(int) $id);
	$trackerSql = $db->query("SELECT tracker, seeders, leechers, lastchecked, state FROM trackers WHERE tracker <> 'localhost' AND torrent=".(int) $id." ORDER BY seeders DESC, leechers DESC, tracker ASC");
	while ($trackerRow = $db->get_row($trackerSql)) {
		$lastChecked = (int) ($trackerRow['lastchecked'] ?? 0);
		$details_tracker_rows[] = array(
			'tracker' => (string) ($trackerRow['tracker'] ?? ''),
			'seeders' => number_format(max(0, (int) ($trackerRow['seeders'] ?? 0))),
			'leechers' => number_format(max(0, (int) ($trackerRow['leechers'] ?? 0))),
			'lastchecked' => ($lastChecked > 0 ? convent_date(get_date_time($lastChecked)) : 'ещё не проверялся'),
			'state' => trim((string) ($trackerRow['state'] ?? '')),
		);
	}
}


//Мульти
$multi  = $arr['multi'];

//Добавить/Удалить закладку
$count_b = array('count' => 0);
if($USER) {
	$count_b = $db->super_query("SELECT COUNT(*) AS count FROM books WHERE id_torrent=".$id." AND id_user=".$USER['id']."");
	if(!$count_b['count']) {
		$book = '<a class="proleft" href="my.book.php?id='.$id.'&act=add">'.$language['details_25'].'</a>';
	} else {
		$book = '<a class="proleft" href="my.book.php?id='.$id.'&act=delete">'.$language['details_26'].'</a>';
	}
}

$screens = lt_details_collect_screens($arr);
$category_badge = lt_details_lower($cat_name_plain);
$details_created_label = lt_details_format_date_label($arr['added']);
$details_updated_label = lt_details_format_date_label($arr['last_action']);
$details_comment_count = 0;

if (lt_table_exists('comments_torrents')) {
	$commentCountRow = $db->super_query("SELECT COUNT(*) AS cnt FROM comments_torrents WHERE id_torrents = ".(int) $id);
	$details_comment_count = (int) ($commentCountRow['cnt'] ?? 0);
}

$details_file_rows = array();
if (!empty($USER['id']) && (int) ($arr['num_files'] ?? 0) > 0) {
	$fileSql = $db->query("SELECT filename, size FROM files WHERE id_torrent = ".(int) $id." ORDER BY id");
	while ($fileRow = $db->get_row($fileSql)) {
		$details_file_rows[] = array(
			'name' => (string) ($fileRow['filename'] ?? ''),
			'size' => mksize((float) ($fileRow['size'] ?? 0)),
		);
	}
}

$details_views_count = lt_details_register_view((int) $id);

$details_rating_votes = 0;
$details_rating_score = 0;

if ($details_rating_table_ready) {
	$detailsRatingStats = $db->super_query(
		"SELECT COUNT(*) AS cnt, COALESCE(SUM(rating), 0) AS total_rating FROM torrent_ratings WHERE torrent_id = ".(int) $id
	);
	$details_rating_votes = (int) ($detailsRatingStats['cnt'] ?? 0);
	if ($details_rating_votes > 0) {
		$details_rating_score = round(((float) ($detailsRatingStats['total_rating'] ?? 0) / $details_rating_votes), 1);
	}
}

$details_rating_feedback = '';
if (!empty($_GET['rated']) && $details_rating_user_value > 0) {
	$details_rating_feedback = 'Спасибо, ваша оценка учтена.';
} elseif (!empty($USER['id']) && $details_rating_user_value > 0) {
	$details_rating_feedback = 'Вы уже оценили эту раздачу.';
} elseif (!empty($USER['id']) && !$details_rating_table_ready) {
	$details_rating_feedback = 'Голосование временно недоступно.';
} elseif (!$USER) {
	$details_rating_feedback = 'Чтобы оценить раздачу, войдите в аккаунт.';
}

$details_status_badges = array();
if ($arr['banned']) {
	$details_status_badges[] = array('label' => 'Заблокирован', 'class' => 'details-badge-danger');
}
if ($arr['multi']) {
	$details_status_badges[] = array('label' => 'Мультитрекерная раздача', 'class' => 'details-badge-multitracker');
}
if ($arr['local_seeders']) {
	$details_status_badges[] = array('label' => 'Локальные сиды', 'class' => 'details-badge-success');
}

$details_parsed = lt_details_parse_description((string) $arr['descr']);
$details_sections = array_values((array) ($details_parsed['sections'] ?? array()));
$details_description_text = lt_details_extract_item($details_sections, array('описание', 'описание релиза', 'содержание', 'сюжет'));
$details_update_reason = lt_details_extract_item($details_sections, array('причина'));

if ($details_description_text === '' && !empty($details_parsed['intro'])) {
	$details_description_text = implode("\n", $details_parsed['intro']);
}

$details_main_autofill = array(
	array('keys' => array('страна'), 'label' => 'Страна', 'value' => lt_torrent_metadata_format('country', $arr['countries'] ?? '')),
	array('keys' => array('тип'), 'label' => 'Тип', 'value' => lt_torrent_metadata_format('type', $arr['content_type'] ?? '')),
	array('keys' => array('жанр'), 'label' => 'Жанр', 'value' => lt_torrent_metadata_format('genre', $arr['genres'] ?? '')),
);

foreach ($details_main_autofill as $item) {
	if ($item['value'] !== '' && !lt_details_has_item($details_sections, $item['keys'])) {
		lt_details_append_item($details_sections, '', $item['label'], $item['value']);
	}
}

$details_extra_autofill = array(
	array('keys' => array('субтитры'), 'label' => 'Субтитры', 'value' => lt_torrent_metadata_format('subtitles', $arr['subtitles'] ?? '')),
	array('keys' => array('язык', 'аудио'), 'label' => 'Язык', 'value' => lt_torrent_metadata_format('language', $arr['languages'] ?? '')),
	array('keys' => array('инфо'), 'label' => 'Инфо', 'value' => lt_torrent_metadata_format('info', $arr['meta_info'] ?? '')),
);

foreach ($details_extra_autofill as $item) {
	if ($item['value'] !== '' && !lt_details_has_item($details_sections, $item['keys'])) {
		lt_details_append_item($details_sections, 'Дополнительно', $item['label'], $item['value']);
	}
}

$details_main_items = array();
$details_extra_sections = array();
foreach ($details_sections as $section) {
	$items = array_values(array_filter((array) ($section['items'] ?? array()), function ($item) {
		return trim((string) ($item['value'] ?? '')) !== '';
	}));
	if (!$items) {
		continue;
	}

	$sectionLabel = trim((string) ($section['label'] ?? ''));
	if ($sectionLabel === '' && !$details_main_items) {
		$details_main_items = $items;
		continue;
	}

	if ($sectionLabel === '') {
		$details_main_items = array_merge($details_main_items, $items);
		continue;
	}

	$details_extra_sections[] = array(
		'label' => $sectionLabel,
		'items' => $items,
	);
}

$details_description_html = ($details_description_text !== '' ? lt_details_render_text_html($details_description_text) : '');
$details_summary_text = '';
$details_has_structured_content = (!empty($details_main_items) || !empty($details_extra_sections));
$details_can_edit = ($PRIV['edit_release'] || (!empty($USER['id']) && $USER['id'] == $id_user));
$details_download_href = ($infohash && $PRIV['download_torrent'] ? 'download.php?id='.$id : '');
$details_magnet_href = ($infohash && $PRIV['download_magnet'] ? 'download.php?id='.$id.'&magnet=1' : '');
$details_edit_href = ($details_can_edit ? 'edit.php?id='.$id : '');
$details_bookmark_href = '';
$details_bookmark_label = $language['details_25'];
$details_bookmarked = false;
$details_guest_register_href = '';
$details_guest_login_href = '';
$details_guest_notice = 'Чтобы скачать этот торрент, вам необходимо зарегистрироваться или войти на сайт.';

if (!empty($USER['id'])) {
	$details_bookmark_href = 'my.book.php?id='.$id.'&act='.($count_b['count'] ? 'delete' : 'add');
	$details_bookmark_label = ($count_b['count'] ? $language['details_26'] : $language['details_25']);
	$details_bookmarked = !empty($count_b['count']);
} else {
	$details_guest_register_href = (!empty($config['registeronline']) ? 'signup.php?referer='.rawurlencode('details.php?id='.$id) : '');
	$details_guest_login_href = 'login.php?referer='.rawurlencode('details.php?id='.$id);
}

$details_has_update = (!empty($arr['last_action']) && $arr['last_action'] !== '0000-00-00 00:00:00' && $arr['last_action'] !== $arr['added']);

//Заголовок
head($torrent_name_plain);

//Выводим статусы
comment_status();

//Редактирование
if(!empty($_GET['edit']) && $_GET['edit'] == '1') {
	msg($language['details_24']);
}

//Подключаем шаблон
require 'templates/'.$config['template'].'/tpl.details.php';

//Подвал
stdfoot();
?>
