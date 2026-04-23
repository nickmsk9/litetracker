<?php
/*
===================================================================
LiteTracker Source
===================================================================
by Nick
-------------------------------------------------------------------
Назначение: Скачивание торрента
===================================================================
*/

//Подключаем главный системный файл
require 'system/init.php';
require 'system/functions/functions.benc.php';


if(!$PRIV['details_view']) {
	err($language['default_1'] , $language['details_29'] , 1);
}


//ID торрента
$id = (int)$_GET['id'];
$db->query('SELECT *  FROM torrents WHERE id="'.$id.'"');
if(!$db->num_rows()) {
	err($language['default_1'] , $language['download_1'] , 1);
}
$arr = $db->get_row();

//Проверяем на бан
if($arr['banned'] && !$PRIV['details_banned_view']) {
	err($language['default_1'] , $language['download_2'] , 1);
}

if(!$arr['infohash']) {
	err($language['default_1'] ,'У данного релиза нет торрент-файла' , 1);
}

//Проверяем рейтинг (fix 0.3.0)
if($USER['bad_rating'] && $PRIV['bad_rating'] && $USER['id'] != $arr['id_user']) {
	$bad_rating =  ($config['bad_rating'] - 1);
	err($language['download_3'] , sprintf($language['download_4'] , $bad_rating) , 1);
}



/////////////////////////////////////////////////
//Защитный код
/////////////////////////////////////////////////
if($config['reCaptcha'] && $config['reCaptcha_download']) {
	//Обрабока
	if($_POST) {
		$resp = recaptcha_check_answer ($config['reCaptcha_privatekey'],
									$_SERVER["REMOTE_ADDR"],
									$_POST["recaptcha_challenge_field"],
									$_POST["recaptcha_response_field"]);

		if (!$resp->is_valid) {
			// What happens when the CAPTCHA was entered incorrectly
			err($language['default_1'] , $language['captcha_2'] , 1);
		}
	}

	//Вывод формы
	if(!isset($resp)) {
		head($language['download_9'] );
		begin_frame($language['download_9']);
		msg($language['default_7'] , sprintf($language['download_10'] , $id));

		echo '<form method="post">';
		echo '<table cellpadding="3">';
		echo '<tr><td>';
		echo recaptcha_get_html($config['reCaptcha_publickey']);
		echo '</td></tr>';
		echo '<tr><td>';
		echo '<input type="submit" value="'.$language['download_11'].'"> ';
		echo '</td></tr>';
		echo '</table>';
		echo '</form>';
		end_frame();
		foot();
		die();
	}

}



if ($_GET['magnet']) $magnet = true; else $magnet=false;

//Путь к торрент - файлу
if(!$magnet) {

	$file_path = 'public/downloads/torrents/'.$id.".torrent";
	if (!is_file($file_path) || !is_readable($file_path) ) {
		err($language['default_1'] , sprintf($language['download_5'] , $id) , 1);
	}
}

//Получаем ссылку
$normalizeAnnounceUrl = function ($url) {
	$url = trim((string) $url);
	if ($url === '') {
		return '';
	}

	$parts = @parse_url($url);
	if (!$parts || empty($parts['host'])) {
		return rtrim($url, '/');
	}

	$scheme = strtolower((string) ($parts['scheme'] ?? ''));
	$host = strtolower((string) $parts['host']);
	$port = (isset($parts['port']) ? ':'.(int) $parts['port'] : '');
	$path = rtrim((string) ($parts['path'] ?? ''), '/');

	return $scheme.'://'.$host.$port.$path;
};

$announce_urls_list = array() ;
$announceBaseUrl = trim((string) ($config['announce_url'] ?? 'https://localhost:443/announce.php'));
$localRetrackerUrl = trim((string) ($config['local_retracker_url'] ?? $announceBaseUrl));
$normalizedLocalRetrackerUrl = $normalizeAnnounceUrl($localRetrackerUrl);

if($USER)
	$announce_urls_list[] = $announceBaseUrl.(strpos($announceBaseUrl, '?') === false ? '?' : '&')."passkey=".$USER['passkey'];
else
	$announce_urls_list[] = $announceBaseUrl;

$useLocalRetracker = (!$USER || !isset($USER['download_local_retracker']) || !empty($USER['download_local_retracker']));
if ($useLocalRetracker && $localRetrackerUrl !== '' && !in_array($localRetrackerUrl, $announce_urls_list)) {
	$announce_urls_list[] = $localRetrackerUrl;
}

$announce_sql = $db->query("SELECT tracker FROM trackers WHERE torrent='".$id."' AND tracker<>'localhost'");
while ($announceRow = $db->get_array($announce_sql)) {
	$announce = (string) ($announceRow[0] ?? '');
	if (!$useLocalRetracker && $normalizedLocalRetrackerUrl !== '' && $normalizeAnnounceUrl($announce) === $normalizedLocalRetrackerUrl) {
		continue;
	}

	if (!in_array($announce, $announce_urls_list)) {
		$announce_urls_list[] = $announce;
	}
}

//ReTrackers
$retrackers = get_retrackers();
if ($retrackers) foreach ($retrackers as $announce)
if (
	!(!$useLocalRetracker && $normalizedLocalRetrackerUrl !== '' && $normalizeAnnounceUrl($announce) === $normalizedLocalRetrackerUrl) &&
	!in_array($announce,$announce_urls_list)
) $announce_urls_list[] = $announce;

//Учитываем , что пользователь скачал данный релиз
$db->query('UPDATE torrents SET downloaded = (downloaded + 1) WHERE id="'.$db->safesql($id).'"');

//Magnet
if($magnet) {

	if(!$PRIV['download_magnet']) {
		err($language['default_1'] , $language['download_7'] , 1);
	}

	$link = make_magnet($arr['infohash'],$arr['name'],$announce_urls_list);
	header('Location: '.$link, true, 302);
	exit;

}


if(!$PRIV['download_torrent']) {
	err($language['default_1'] , $language['download_8'] , 1);
}

//Filename
$filename = str_replace(array(',', ';'), '', $arr['filename']);

//Выдаем на сохранение torrent - файл
$dict = bdec_file($file_path, (1024*1024));
put_announce_urls($dict,$announce_urls_list);


$dict['type'] = 'dictionary';
$siteBaseUrl = rtrim((string) ($config['site_url'] ?? ''), '/');
if ($siteBaseUrl === '') {
	$scheme = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http');
	$host = trim((string) ($_SERVER['HTTP_HOST'] ?? ''));
	if ($host !== '') {
		$siteBaseUrl = $scheme.'://'.$host;
	}
}

$publisher = get_user_info((int) ($arr['id_user'] ?? 0));
$publisherName = trim((string) ($publisher['name'] ?? 'LiteTracker'));
$detailsUrl = ($siteBaseUrl !== '' ? $siteBaseUrl : '').'/details.php?id='.(int) $id;
$publisherUrl = ($siteBaseUrl !== '' ? $siteBaseUrl : '').'/profile.php?id='.(int) ($arr['id_user'] ?? 0);

$dict['value']['comment']=bdec(benc_str($detailsUrl));
$dict['value']['created by']=bdec(benc_str($publisherName));
$dict['value']['publisher']=bdec(benc_str($publisherName));
$dict['value']['publisher.utf-8']=bdec(benc_str($publisherName));
$dict['value']['publisher-url']=bdec(benc_str($publisherUrl));
$dict['value']['publisher-url.utf-8']=bdec(benc_str($publisherUrl));


//Заголовки
header ("Expires: Tue, 1 Jan 1980 00:00:00 GMT");
header ("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
header ("Cache-Control: no-store, no-cache, must-revalidate");
header ("Cache-Control: post-check=0, pre-check=0", false);
header ("Pragma: no-cache");
header ("Accept-Ranges: bytes");
header ("Connection: close");
header ("Content-Transfer-Encoding: binary");
header ("Content-Disposition: attachment; filename=\"".$filename."\"");
header ("Content-Type: application/x-bittorrent");


echo benc($dict);
?>
