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
require __DIR__ . '/app/system/init.php';
require __DIR__ . '/app/system/functions/functions.benc.php';

function download_content_disposition_filename($filename, $fallbackId)
{
	$filename = trim((string) $filename);
	$filename = str_replace(array("\r", "\n", "\0"), '', $filename);
	$filename = str_replace(array('"', '\\'), '', $filename);
	$filename = preg_replace('~[\/]+~', '_', $filename);
	$filename = trim((string) $filename);

	if ($filename === '') {
		$filename = 'torrent-'.(int) $fallbackId.'.torrent';
	}

	$ascii = (function_exists('iconv') ? iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $filename) : '');
	if (!is_string($ascii) || trim($ascii) === '') {
		$ascii = 'torrent-'.(int) $fallbackId.'.torrent';
	}

	$ascii = preg_replace('~[^A-Za-z0-9._ -]+~', '_', $ascii);
	$ascii = preg_replace('~\s+~', ' ', trim((string) $ascii));
	$ascii = trim($ascii, '. ');
	if ($ascii === '') {
		$ascii = 'torrent-'.(int) $fallbackId.'.torrent';
	}

	return 'attachment; filename="'.$ascii.'"; filename*=UTF-8\'\''.rawurlencode($filename);
}


if(!$PRIV['details_view']) {
	err($language['default_1'] , $language['details_29'] , 1);
}

lt_torrent_status_ensure_schema();

//ID торрента
$id = (int) ($_GET['id'] ?? 0);
$magnet = !empty($_GET['magnet']);
$db->query('SELECT *  FROM torrents WHERE id="'.$id.'"');
if(!$db->num_rows()) {
	err($language['default_1'] , $language['download_1'] , 1);
}
$arr = $db->get_row();

//Проверяем видимость с учетом soft moderation
$torrentStatus = lt_torrent_status_normalize($arr['status'] ?? 'approved');
$isTorrentOwner = (!empty($USER['id']) && (int) ($arr['id_user'] ?? 0) === (int) $USER['id']);
$canDownloadModerated = (
	$torrentStatus === 'approved'
	|| lt_torrent_can_moderate($USER)
	|| ($isTorrentOwner && in_array($torrentStatus, array('pending', 'need_fix'), true))
);

if (!$canDownloadModerated) {
	err($language['default_1'] , $language['download_2'] , 1);
}

//Проверяем на legacy-бан
if($arr['banned'] && !$PRIV['details_banned_view'] && !lt_torrent_can_moderate($USER) && $torrentStatus === 'approved') {
	err($language['default_1'] , $language['download_2'] , 1);
}

if(!$arr['infohash']) {
	err($language['default_1'] ,'У данного релиза нет торрент-файла' , 1);
}




/////////////////////////////////////////////////
//Защитный код
/////////////////////////////////////////////////
if(!empty($config['captcha']) && $config['reCaptcha_download']) {
	//Обрабока
	if($_POST) {
		$resp = lt_captcha_check_answer();

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
		echo lt_captcha_get_html('download');
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


//Путь к торрент - файлу
if(!$magnet) {

	$file_path = 'public/downloads/torrents/'.$id.".torrent";
	if (!is_file($file_path) || !is_readable($file_path) ) {
		err($language['default_1'] , sprintf($language['download_5'] , $id) , 1);
	}
}

$useLocalRetracker = (!$USER || !isset($USER['download_local_retracker']) || !empty($USER['download_local_retracker']));
$announce_urls_list = lt_torrent_site_announce_urls($USER ?: null, $useLocalRetracker);

//Учитываем , что пользователь скачал данный релиз
$db->pquery('UPDATE torrents SET downloaded = (downloaded + 1) WHERE id=?', 'i', [$id]);

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
$contentDisposition = download_content_disposition_filename((string) ($arr['filename'] ?? ''), $id);

//Выдаем на сохранение torrent - файл
$dict = lt_torrent_decode_file($file_path);
if (!is_array($dict)) {
	err($language['default_1'], 'Torrent-файл поврежден или не читается', 1);
}
$dict = put_announce_urls($dict,$announce_urls_list);


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
header ("Content-Disposition: ".$contentDisposition);
header ("Content-Type: application/x-bittorrent");


echo benc($dict);
?>
