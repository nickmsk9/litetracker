<?php
/*
===================================================================
LiteTracker Source
===================================================================
by Nick
-------------------------------------------------------------------
Назначение: Подготовка данных страницы details.php
===================================================================
*/

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

function lt_details_load_torrent($torrentId)
{
	global $db;

	$torrentId = (int) $torrentId;
	if ($torrentId <= 0) {
		return array();
	}

	return $db->super_query(
		"SELECT t.*,
			COALESCE(trs.seeders, 0) AS seeders,
			COALESCE(trs.leechers, 0) AS leechers,
			COALESCE(trs.local_seeders_count, 0) AS local_seeders_count,
			COALESCE(trs.local_leechers_count, 0) AS local_leechers_count,
			COALESCE(trs.external_seeders_count, 0) AS external_seeders_count,
			COALESCE(trs.external_leechers_count, 0) AS external_leechers_count,
			COALESCE(trs.external_tracker_count, 0) AS external_tracker_count,
			IF(COALESCE(trs.local_seeders_count, 0) > 0, true, false) AS local_seeders
		FROM torrents AS t
		LEFT JOIN (
			SELECT
				torrent,
				SUM(GREATEST(seeders, 0)) AS seeders,
				SUM(GREATEST(leechers, 0)) AS leechers,
				SUM(CASE WHEN tracker = 'localhost' THEN GREATEST(seeders, 0) ELSE 0 END) AS local_seeders_count,
				SUM(CASE WHEN tracker = 'localhost' THEN GREATEST(leechers, 0) ELSE 0 END) AS local_leechers_count,
				SUM(CASE WHEN tracker <> 'localhost' THEN GREATEST(seeders, 0) ELSE 0 END) AS external_seeders_count,
				SUM(CASE WHEN tracker <> 'localhost' THEN GREATEST(leechers, 0) ELSE 0 END) AS external_leechers_count,
				SUM(CASE WHEN tracker <> 'localhost' THEN 1 ELSE 0 END) AS external_tracker_count
			FROM trackers
			WHERE torrent = ".$torrentId."
			GROUP BY torrent
		) AS trs ON trs.torrent = t.id
		WHERE t.id = ".$torrentId."
		LIMIT 1"
	);
}

function lt_details_check_access($torrent)
{
	global $USER, $PRIV, $language;

	if (!empty($USER) && empty($PRIV['details_view'])) {
		err($language['default_1'], $language['details_29'], 1);
	}

	if (empty($torrent['id'])) {
		err($language['default_1'], $language['details_19'], 1);
	}

	if (!empty($torrent['banned']) && empty($PRIV['details_banned_view'])) {
		err($language['default_1'], $language['details_20'], 1);
	}
}

function lt_details_prepare_rating($torrentId)
{
	global $db, $USER;

	$torrentId = (int) $torrentId;
	$cookieName = 'lt_torrent_rating_'.$torrentId;
	$tableReady = lt_details_rating_table_ready();
	$userValue = 0;

	if ($tableReady && !empty($USER['id'])) {
		$row = $db->super_query(
			"SELECT rating FROM torrent_ratings WHERE torrent_id = ".$torrentId." AND user_id = ".(int) $USER['id']." LIMIT 1"
		);
		$userValue = (int) ($row['rating'] ?? 0);
	}

	if ($userValue <= 0 && empty($USER['id'])) {
		$userValue = (int) ($_COOKIE[$cookieName] ?? 0);
	}

	$votes = 0;
	$score = 0;
	if ($tableReady) {
		$stats = $db->super_query(
			"SELECT COUNT(*) AS cnt, COALESCE(SUM(rating), 0) AS total_rating FROM torrent_ratings WHERE torrent_id = ".$torrentId
		);
		$votes = (int) ($stats['cnt'] ?? 0);
		if ($votes > 0) {
			$score = round(((float) ($stats['total_rating'] ?? 0) / $votes), 1);
		}
	}

	$feedback = '';
	$ratedState = trim((string) ($_GET['rated'] ?? ''));
	if ($ratedState === '1' && $userValue > 0) {
		$feedback = 'Спасибо, ваша оценка учтена.';
	} elseif ($ratedState === 'exists' && $userValue > 0) {
		$feedback = 'Вы уже оценили эту раздачу.';
	} elseif (!empty($USER['id']) && $userValue > 0) {
		$feedback = 'Вы уже оценили эту раздачу.';
	} elseif (!empty($USER['id']) && !$tableReady) {
		$feedback = 'Голосование временно недоступно.';
	} elseif (!$USER) {
		$feedback = 'Чтобы оценить раздачу, войдите в аккаунт.';
	}

	return array(
		'cookie_name' => $cookieName,
		'table_ready' => $tableReady,
		'user_value' => $userValue,
		'can_vote' => (!empty($USER['id']) && $tableReady && $userValue <= 0),
		'votes' => $votes,
		'score' => $score,
		'feedback' => $feedback,
	);
}

function lt_details_handle_rating_request($torrentId, array &$rating)
{
	global $db, $USER, $memcached;

	if (!isset($_GET['rating'])) {
		return;
	}

	$torrentId = (int) $torrentId;
	$ratingValue = (int) $_GET['rating'];

	if (!$USER) {
		header('Location: login.php?referer='.rawurlencode('details.php?id='.$torrentId));
		die();
	}

	if ($ratingValue < 1 || $ratingValue > 5 || empty($rating['table_ready'])) {
		header('Location: details.php?id='.$torrentId);
		die();
	}

	$db->query(
		"INSERT INTO torrent_ratings (torrent_id, user_id, rating, ip, date)
		SELECT
			".$torrentId.",
			".(int) $USER['id'].",
			".$ratingValue.",
			'".$db->safesql((string) getip())."',
			NOW()
		FROM DUAL
		WHERE NOT EXISTS (
			SELECT 1 FROM torrent_ratings
			WHERE torrent_id = ".$torrentId." AND user_id = ".(int) $USER['id']."
			LIMIT 1
		)"
	);

	$ratedState = 'exists';
	if ((int) $db->affected_rows() > 0) {
		$rating['user_value'] = $ratingValue;
		$ratedState = '1';
	} else {
		$existingRating = $db->super_query(
			"SELECT rating FROM torrent_ratings WHERE torrent_id = ".$torrentId." AND user_id = ".(int) $USER['id']." LIMIT 1"
		);
		$rating['user_value'] = (int) ($existingRating['rating'] ?? 0);
	}

	if ((int) $rating['user_value'] > 0) {
		lt_set_cookie($rating['cookie_name'], (string) (int) $rating['user_value'], time() + 31536000, false, 'Lax');
	}
	$memcached->delete('torrent_'.$torrentId, 0);
	header('Location: details.php?id='.$torrentId.'&rated='.$ratedState);
	die();
}

function lt_details_prepare_file_rows($torrent)
{
	global $db, $USER;

	$torrentId = (int) ($torrent['id'] ?? 0);
	$rows = array();

	if (empty($USER['id']) || $torrentId <= 0 || (int) ($torrent['num_files'] ?? 0) <= 0) {
		return $rows;
	}

	$sql = $db->query("SELECT filename, size FROM files WHERE id_torrent = ".$torrentId." ORDER BY id");
	while ($fileRow = $db->get_row($sql)) {
		$rows[] = array(
			'name' => (string) ($fileRow['filename'] ?? ''),
			'size' => mksize((float) ($fileRow['size'] ?? 0)),
		);
	}

	return $rows;
}

function lt_details_render_files_page($torrentId)
{
	global $db;

	$torrentId = (int) $torrentId;
	$sql = $db->query("SELECT * FROM files WHERE id_torrent = ".$torrentId." ORDER BY id");
	if (!$db->num_rows($sql)) {
		err("Ошибка", "Извините , но наша система не нашла файлов", 1);
	}

	head('Информация о файлах', true);
	msg('Здесь показаны все файлы  , которые были найдены в торренте', '<a href="javascript:history.go(-1);">Вернуться к деталям</a>');
	begin_frame('Информация о файлах');

	echo '<table width="50%" align="center">';
	echo '<tr>
	<td width="10%"><b>Файл</b></td>
	<td ><b>Размер</b></td>
	</tr>';
	while ($row = $db->get_row($sql)) {
		echo '<tr>
			<td>'.htmlspecialchars($row['filename'], ENT_QUOTES, 'UTF-8').'</td><td><b>'.mksize($row['size']).'</b></td>
		</tr>';
	}
	echo '</table>';

	end_frame();
	foot();
	die();
}

function lt_details_render_peers_page($torrentId)
{
	global $db, $PRIV, $language;

	$torrentId = (int) $torrentId;
	$sql = $db->query("SELECT * FROM peers WHERE torrent=".$torrentId." ORDER BY seeder DESC");
	if (!$db->num_rows($sql)) {
		err($language['default_1'], 'Никаких соединений не обнаружено', 1);
	}

	head('Информация о соединениях');
	begin_frame('Информация о соединениях');
	msg('Здесь показаны все соединение , которые контролирует наш трекер', '<a href="javascript:history.go(-1);">Вернуться к деталям</a>');
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

	while ($row = $db->get_row($sql)) {
		$user = get_user_info($row['userid']);

		echo '<tr>';
		if ($PRIV['ip_util']) {
			echo '<td><a href="ip.util.php?ip='.$row['ip'].'">'.$row['ip'].'</a></td>';
		}
		echo '<td>'.($user ? '<a href="'.profile_href($user['id']).'">'.get_user_color($user['class'], $user['name']).'</a>' : 'Гость').'</td>';
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

function lt_details_render_trackers_page($torrentId)
{
	global $db, $language;

	$torrentId = (int) $torrentId;
	$sql = $db->query("SELECT * FROM trackers WHERE tracker <> 'localhost' AND torrent=".$torrentId);
	if (!$db->num_rows($sql)) {
		err($language['default_1'], 'Трекеров не найдено', 1);
	}

	head('Информация о трекерах');
	begin_frame('Информация о трекерах');
	msg('Данные могут не соответствовать настоящим', '<a href="javascript:history.go(-1);">Вернуться к деталям</a>');
	echo '<table>';
	echo '<tr><td><b>Трекер</b></td><td><b>Раздают</b></td><td><b>Качают</b></td><td><b>Дата обновление</b></td></tr>';

	while ($row = $db->get_row($sql)) {
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

function lt_details_prepare_tracker_rows($torrent)
{
	global $db;

	$torrentId = (int) ($torrent['id'] ?? 0);
	$rows = array();
	$externalCount = (int) ($torrent['external_tracker_count'] ?? 0);

	if ($torrentId <= 0 || $externalCount <= 0) {
		return array(
			'rows' => $rows,
			'external_count' => $externalCount,
			'update_href' => '',
		);
	}

	$trackerSql = $db->query(
		"SELECT tracker, GREATEST(seeders, 0) AS seeders, GREATEST(leechers, 0) AS leechers, lastchecked, state
		FROM trackers
		WHERE tracker <> 'localhost' AND torrent = ".$torrentId."
		ORDER BY seeders DESC, leechers DESC, tracker ASC"
	);
	while ($trackerRow = $db->get_row($trackerSql)) {
		$lastChecked = (int) ($trackerRow['lastchecked'] ?? 0);
		$rows[] = array(
			'tracker' => (string) ($trackerRow['tracker'] ?? ''),
			'seeders' => number_format(max(0, (int) ($trackerRow['seeders'] ?? 0))),
			'leechers' => number_format(max(0, (int) ($trackerRow['leechers'] ?? 0))),
			'lastchecked' => ($lastChecked > 0 ? convent_date(get_date_time($lastChecked)) : 'ещё не проверялся'),
			'state' => trim((string) ($trackerRow['state'] ?? '')),
		);
	}

	return array(
		'rows' => $rows,
		'external_count' => $externalCount,
		'update_href' => 'update.peers.php?id='.$torrentId.'&return='.rawurlencode('details.php?id='.$torrentId),
	);
}

function lt_details_prepare_bookmark($torrentId)
{
	global $db, $USER, $config, $language;

	$torrentId = (int) $torrentId;
	$count = array('count' => 0);
	if (!empty($USER['id'])) {
		$count = $db->super_query("SELECT COUNT(*) AS count FROM books WHERE id_torrent=".$torrentId." AND id_user=".(int) $USER['id']);
		return array(
			'legacy_html' => '<a class="proleft" href="my.book.php?id='.$torrentId.'&act='.(!empty($count['count']) ? 'delete' : 'add').'">'.(!empty($count['count']) ? $language['details_26'] : $language['details_25']).'</a>',
			'href' => 'my.book.php?id='.$torrentId.'&act='.(!empty($count['count']) ? 'delete' : 'add'),
			'label' => (!empty($count['count']) ? $language['details_26'] : $language['details_25']),
			'bookmarked' => !empty($count['count']),
			'guest_register_href' => '',
			'guest_login_href' => '',
			'guest_notice' => 'Чтобы скачать этот торрент, вам необходимо зарегистрироваться или войти на сайт.',
		);
	}

	return array(
		'legacy_html' => '',
		'href' => '',
		'label' => $language['details_25'],
		'bookmarked' => false,
		'guest_register_href' => (!empty($config['registeronline']) ? 'signup.php?referer='.rawurlencode('details.php?id='.$torrentId) : ''),
		'guest_login_href' => 'login.php?referer='.rawurlencode('details.php?id='.$torrentId),
		'guest_notice' => 'Чтобы скачать этот торрент, вам необходимо зарегистрироваться или войти на сайт.',
	);
}

function lt_details_prepare_description_view($torrent, $categoryName)
{
	$parsed = lt_details_parse_description((string) ($torrent['descr'] ?? ''));
	$sections = array_values((array) ($parsed['sections'] ?? array()));
	$descriptionText = lt_details_extract_item($sections, array('описание', 'описание релиза', 'содержание', 'сюжет'));
	$updateReason = lt_details_extract_item($sections, array('причина'));

	if ($descriptionText === '' && !empty($parsed['intro'])) {
		$descriptionText = implode("\n", $parsed['intro']);
	}

	$mainAutofill = array(
		array('keys' => array('страна'), 'label' => 'Страна', 'value' => lt_torrent_metadata_format('country', $torrent['countries'] ?? '')),
		array('keys' => array('тип'), 'label' => 'Тип', 'value' => lt_torrent_metadata_format('type', $torrent['content_type'] ?? '')),
		array('keys' => array('жанр'), 'label' => 'Жанр', 'value' => lt_torrent_metadata_format('genre', $torrent['genres'] ?? '')),
	);

	foreach ($mainAutofill as $item) {
		if ($item['value'] !== '' && !lt_details_has_item($sections, $item['keys'])) {
			lt_details_append_item($sections, '', $item['label'], $item['value']);
		}
	}

	$extraAutofill = array(
		array('keys' => array('субтитры'), 'label' => 'Субтитры', 'value' => lt_torrent_metadata_format('subtitles', $torrent['subtitles'] ?? '')),
		array('keys' => array('язык', 'аудио'), 'label' => 'Язык', 'value' => lt_torrent_metadata_format('language', $torrent['languages'] ?? '')),
		array('keys' => array('инфо'), 'label' => 'Инфо', 'value' => lt_torrent_metadata_format('info', $torrent['meta_info'] ?? '')),
	);

	foreach ($extraAutofill as $item) {
		if ($item['value'] !== '' && !lt_details_has_item($sections, $item['keys'])) {
			lt_details_append_item($sections, 'Дополнительно', $item['label'], $item['value']);
		}
	}

	$mainItems = array();
	$extraSections = array();
	foreach ($sections as $section) {
		$items = array_values(array_filter((array) ($section['items'] ?? array()), function ($item) {
			return trim((string) ($item['value'] ?? '')) !== '';
		}));
		if (!$items) {
			continue;
		}

		$sectionLabel = trim((string) ($section['label'] ?? ''));
		if ($sectionLabel === '' && !$mainItems) {
			$mainItems = $items;
			continue;
		}

		if ($sectionLabel === '') {
			$mainItems = array_merge($mainItems, $items);
			continue;
		}

		$extraSections[] = array(
			'label' => $sectionLabel,
			'items' => $items,
		);
	}

	return array(
		'info_title' => lt_details_info_heading($categoryName),
		'description_text' => $descriptionText,
		'description_html' => ($descriptionText !== '' ? lt_details_render_text_html($descriptionText) : ''),
		'update_reason' => $updateReason,
		'main_items' => $mainItems,
		'extra_sections' => $extraSections,
		'summary_text' => '',
		'has_structured_content' => (!empty($mainItems) || !empty($extraSections)),
	);
}

function lt_details_prepare_view_model($torrent, array $rating)
{
	global $USER, $PRIV, $language;

	$id = (int) ($torrent['id'] ?? 0);
	$category = categories_array((int) ($torrent['id_category'] ?? 0));
	$catNamePlain = trim((string) ($category['name'] ?? ''));
	$catImage = (string) ($category['image'] ?? '');
	$user = get_user_info((int) ($torrent['id_user'] ?? 0));
	$user = ($user ?: array('id' => 0, 'name' => '', 'class' => 0));
	$trackerView = lt_details_prepare_tracker_rows($torrent);
	$bookmark = lt_details_prepare_bookmark($id);
	$description = lt_details_prepare_description_view($torrent, $catNamePlain);
	$descriptionHtml = $description['description_html'];
	if ($descriptionHtml === '' && empty($description['has_structured_content'])) {
		$descriptionHtml = format_comment((string) ($torrent['descr'] ?? ''));
	}

	if (!empty($torrent['image']) && is_file('public/downloads/images/'.$torrent['image'])) {
		$image = 'public/downloads/images/'.$torrent['image'];
	} elseif ($catImage !== '' && is_file('public/images/categories/'.$catImage)) {
		$image = 'public/images/categories/'.$catImage;
	} else {
		$image = 'public/images/default_avatar.gif';
	}

	$seedersCount = max(0, (int) ($torrent['seeders'] ?? 0));
	$leechersCount = max(0, (int) ($torrent['leechers'] ?? 0));
	$typeSeeders = array();
	if (!empty($torrent['multi'])) {
		$typeSeeders[] = '<font color="red"><b>'.$language['details_21'].'</b></a></font>';
	}
	if (!empty($torrent['local_seeders'])) {
		$typeSeeders[] = '<font color="green"><b>'.$language['details_22'].'</b></font>';
	}

	$statusBadges = array();
	if (!empty($torrent['banned'])) {
		$statusBadges[] = array('label' => 'Заблокирован', 'class' => 'details-badge-danger');
	}
	if (!empty($torrent['multi'])) {
		$statusBadges[] = array('label' => 'Мультитрекерная раздача', 'class' => 'details-badge-multitracker');
	}
	if (!empty($torrent['local_seeders'])) {
		$statusBadges[] = array('label' => 'Локальные сиды', 'class' => 'details-badge-success');
	}

	$idUser = (int) ($user['id'] ?? 0);
	$infohash = (string) ($torrent['infohash'] ?? '');
	$canEdit = (!empty($PRIV['edit_release']) || (!empty($USER['id']) && (int) $USER['id'] === $idUser));

	return array(
		'arr' => $torrent,
		'id' => $id,
		'image' => $image,
		'torrent_name_plain' => trim((string) ($torrent['name'] ?? '')),
		'name' => htmlspecialchars(trim((string) ($torrent['name'] ?? '')), ENT_QUOTES, 'UTF-8'),
		'infohash' => $infohash,
		'date' => convent_date((string) ($torrent['added'] ?? '')),
		'banned' => (!empty($torrent['banned']) ? '<font color="red">'.$language['default_2'].'</font>' : '<font color="green">'.$language['default_3'].'</font>'),
		'category' => $category,
		'cat_name_plain' => $catNamePlain,
		'cat_name' => htmlspecialchars($catNamePlain, ENT_QUOTES, 'UTF-8'),
		'cat_id' => (int) ($category['id'] ?? 0),
		'cat_image' => $catImage,
		'tags' => tags_echo((string) ($torrent['tags'] ?? '')),
		'downloaded' => number_format((int) ($torrent['downloaded'] ?? 0)),
		'completed' => number_format((int) ($torrent['completed'] ?? 0)),
		'size' => mksize((float) ($torrent['size'] ?? 0)),
		'type_seeders' => implode('+', $typeSeeders),
		'descr' => format_comment((string) ($torrent['descr'] ?? '')),
		'user' => $user,
		'id_user' => $idUser,
		'user_name' => (string) ($user['name'] ?? ''),
		'user_class' => (int) ($user['class'] ?? 0),
		'seeders_count' => $seedersCount,
		'seeders' => number_format($seedersCount),
		'leechers_count' => $leechersCount,
		'leechers' => number_format($leechersCount),
		'peers' => number_format($seedersCount + $leechersCount),
		'multi' => $torrent['multi'] ?? 0,
		'book' => $bookmark['legacy_html'],
		'screens' => lt_details_collect_screens($torrent),
		'category_badge' => lt_details_lower($catNamePlain),
		'details_created_label' => lt_details_format_date_label($torrent['added'] ?? ''),
		'details_updated_label' => lt_details_format_date_label($torrent['last_action'] ?? ''),
		'details_file_rows' => lt_details_prepare_file_rows($torrent),
		'details_views_count' => lt_details_register_view($id),
		'details_rating_votes' => $rating['votes'],
		'details_rating_score' => $rating['score'],
		'detailsRatingPercent' => max(0, min(100, ((float) $rating['score'] / 5) * 100)),
		'details_rating_user_value' => $rating['user_value'],
		'details_rating_can_vote' => $rating['can_vote'],
		'details_rating_feedback' => $rating['feedback'],
		'details_rating_table_ready' => $rating['table_ready'],
		'details_status_badges' => $statusBadges,
		'details_description_text' => $description['description_text'],
		'details_description_html' => $description['description_html'],
		'detailsDescriptionHtml' => $descriptionHtml,
		'detailsInfoTitle' => $description['info_title'],
		'details_summary_text' => $description['summary_text'],
		'details_has_structured_content' => $description['has_structured_content'],
		'details_main_items' => $description['main_items'],
		'details_extra_sections' => $description['extra_sections'],
		'details_update_reason' => $description['update_reason'],
		'details_can_edit' => $canEdit,
		'details_download_href' => ($infohash !== '' && !empty($PRIV['download_torrent']) ? 'download.php?id='.$id : ''),
		'details_magnet_href' => ($infohash !== '' && !empty($PRIV['download_magnet']) ? 'download.php?id='.$id.'&magnet=1' : ''),
		'details_edit_href' => ($canEdit ? 'edit.php?id='.$id : ''),
		'details_bookmark_href' => $bookmark['href'],
		'details_bookmark_label' => $bookmark['label'],
		'details_bookmarked' => $bookmark['bookmarked'],
		'details_guest_register_href' => $bookmark['guest_register_href'],
		'details_guest_login_href' => $bookmark['guest_login_href'],
		'details_guest_notice' => $bookmark['guest_notice'],
		'details_has_update' => (!empty($torrent['last_action']) && $torrent['last_action'] !== '0000-00-00 00:00:00' && $torrent['last_action'] !== $torrent['added']),
		'details_tracker_rows' => $trackerView['rows'],
		'details_external_tracker_count' => $trackerView['external_count'],
		'details_tracker_update_href' => $trackerView['update_href'],
	);
}
