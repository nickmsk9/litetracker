<?php
/*
===================================================================
LiteTracker
===================================================================
by jenaDI
-------------------------------------------------------------------
Назначение: Анонсер для связи клиента и трекера
===================================================================
*/
DEFINE('ANNOUNCE' , TRUE);
require 'system/init.announce.php';

//Добавляем все параметры в GLOBALS
foreach (array('info_hash','peer_id','event','ip','localip') as $x) {
	if(isset($_GET[$x]))
		$GLOBALS[$x] = '' . $_GET[$x];
}

foreach (array('port','downloaded','uploaded','left') as $x) {
	$GLOBALS[$x] = isset($_GET[$x]) ? (int) $_GET[$x] : 0;
}

//Экранируем info_hash и peer_id	
if (get_magic_quotes_gpc() ) {
    $info_hash = stripslashes($info_hash);
    $peer_id = stripslashes($peer_id);
}	

//Проверяем на существование параметров
foreach (array('info_hash','peer_id','port','downloaded','uploaded','left') as $x)
	if (!isset($GLOBALS[$x])) err(sprintf($language['announce_1'] , $x));
	
//Проверяем на валидность info_hash и peer_id	
foreach (array('info_hash','peer_id') as $x) {
	if (strlen($GLOBALS[$x]) != 20)
		err(sprintf($language['announce_2'] , $x , strlen($GLOBALS[$x]) , urlencode($GLOBALS[$x])));
}		


//Проверяем passkey 
//Если его не существует , то считает качающего как гостя
$passkey = (string) ($_GET['passkey'] ?? '');
if($passkey) {
	$GUEST = 0;
	if (strlen($passkey) != 32) {
		err(sprintf($language['announce_3'] , strlen($passkey) ,$passkey));	
	}
} else {
	$GUEST = 1;
}

$ip = getip();


//////////////////////////////////////////////////////////////////////////
//Баны по ip
//////////////////////////////////////////////////////////////////////////

$ip_ban = ip2long_db($ip); //IP адрес

//Бан по IP - адресу
if (false === ($ban_resource = $memcache->get('ip_bans_'.$ip_ban))) {
	$db->query("SELECT * FROM bans WHERE '".$ip_ban."'  >= first AND '".$ip_ban."' <= last");			
	$ban_resource = $db->get_row();
	$memcache->set('ip_bans_'.$ip_ban, $ban_resource  , 0, 1000);		
}

if($ban_resource) {
	err('Please note, your IP ('.long2ip($ip).') has been banned '.convent_date($ban_resource['date']).'');
}


$rsize = 50;
foreach(array('num want', 'numwant', 'num_want') as $k) {
	if (isset($_GET[$k]))
	{
		$rsize = (int) $_GET[$k];
		break;
	}
}


//Определяем клиент пользователя
$agent = $_SERVER['HTTP_USER_AGENT'] ?? '';


//Проверяем порт на валидность
if (!$port || $port > 0xffff) {
	err($language['announce_4']);
}	
if (!isset($event) ) {
	$event = '';
}	

//Если пользователь уже скаал торррент
//Помечаем его как раздающий
$seeder = ($left == 0) ? '1' : '0';


//Определяем парметры $_SERVER
if (function_exists('getallheaders') ) {
	$headers = getallheaders();
} else {
	$headers = emu_getallheaders();
}

//Запрещаем передавать Cookie ,  Accept-Language , Accept-Charset
if (isset($headers['Cookie']) || isset($headers['Accept-Language']) || isset($headers['Accept-Charset']) ) {
	err($language['announce_5']);
}

//Запрещаем использовать определенные клиенты
checkclient($peer_id);

//Проверяем passkey
if(!$GUEST) {
	$user_sql  = mysql_query("SELECT id, slots FROM users WHERE passkey = " .sqlesc($passkey)." LIMIT 1" ) or err('System:valid passkey');
	$user = mysql_fetch_assoc($user_sql);
	if(!$user) {
		err($language['announce_6']);
	}
}

//Определяем информацию у торрента
$info_hash = bin2hex($info_hash);

if(false === ($torrent = $memcache->get('infohash_'.$info_hash)) )
{
	$torrent_sql = mysql_query('SELECT torrents.id, banned,  (trackers.seeders + trackers.leechers) AS numpeers, UNIX_TIMESTAMP(added) AS ts FROM torrents LEFT JOIN trackers ON torrents.id=trackers.torrent WHERE infohash = "'.$info_hash.'" AND tracker="localhost"') or err('System:valid infohash');
	$torrent = mysql_fetch_assoc($torrent_sql);
	$memcache->set('infohash_'.$info_hash , $torrent , 0 , 400);
}
	
if (!$torrent) {
	err($language['announce_7']);
}	

//Определяем ID торрента
$torrentid = $torrent["id"];

//Поля , которые будут использоваться в запросах 
$fields = "seeder, peer_id, ip, port, uploaded, downloaded, userid";

//Количество пиров
$numpeers = $torrent["numpeers"];

//Если пиров больше чем установлен лимит, выбераем случайные записи с лимитом
$limit = '';
if ($numpeers > $rsize) {
	$limit = "ORDER BY RAND() LIMIT ".$rsize."";
}

//Запрос к пирам 
$peers_sql = mysql_query("SELECT ".$fields." FROM peers WHERE torrent = ".$torrentid." ".$limit."") or err('System:valid peers 1');


$resp = "d" . benc_str("interval") . "i" . $config['announce_interval'] . "e" . benc_str("peers") . (($compact = ($_GET['compact'] == 1)) ? '' : 'l');
$no_peer_id = ((int)$_GET['no_peer_id'] == 1);
$plist = '';
$trupdateset = array();

//Удаляем self
unset($self);

//Перебираем пиры и записываем их в ответ
while ($row = mysql_fetch_array($peers_sql) ) {
	if ($row['peer_id'] == $peer_id)  {
		$userid = $row['userid'];
		$self = $row;
		continue;
	}
	if($compact) {
		$peer_ip = explode('.', $row["ip"]);
		$plist .= pack("C*", $peer_ip[0], $peer_ip[1], $peer_ip[2], $peer_ip[3]). pack("n*", (int) $row["port"]);
	} else {
		$resp .= 'd' .
		benc_str('ip') . benc_str($row['ip']) .
		(!$no_peer_id ? benc_str("peer id") . benc_str($row["peer_id"]) : '') .
		benc_str('port') . 'i' . $row['port'] . 'e' . 'e';
	}
}
$resp .= ($compact ? benc_str($plist) : '') . (substr($peer_id, 0, 4) == '-BC0' ? "e7:privatei1ee" : "ee");

$selfwhere = "torrent = '".$torrentid."'  AND peer_id = ".sqlesc($peer_id)."";

if (!isset($self) ) {
	$res = mysql_query("SELECT ".$fields." FROM peers WHERE ".$selfwhere."") or err('(peers)Ошибка при выборке');
	$row = mysql_fetch_assoc($res);
	if ($row) {
		$userid = $row["userid"];
		$self = $row;
	}
}


$announce_wait = 15*60;
if (isset($self) && ($self['prevts'] > ($self['nowts'] - $announce_wait )) ) {
	err(sprintf($language['announce_8'] , $announce_wait));
}


///////////////////////////////////////////////////////////////////////
//Информация о скачаном / разданом
///////////////////////////////////////////////////////////////////////
if(!$GUEST) {
	if (!isset($self) ) {
		
		$valid = mysql_fetch_row(mysql_query("SELECT COUNT(*) FROM peers WHERE torrent=".$torrentid." AND passkey=" .sqlesc($passkey) ) ) or err('System:valid peers 2');
		if ($valid[0] >= 1 && $seeder == '0') {
			// err($valid[0]);
			mysql_query("DELETE FROM peers WHERE torrent=".$torrentid."  AND passkey=" .sqlesc($passkey) );
			mysql_query("UPDATE trackers SET leechers=(leechers-".$valid[0].") WHERE torrent=".$torrentid." AND tracker='localhost'");
			err($language['announce_9']);
		}	
		if ($valid[0] >= 3 && $seeder == '1') {
			mysql_query("DELETE FROM peers WHERE torrent=".$torrentid."  AND passkey=" .sqlesc($passkey) );
			mysql_query("UPDATE trackers SET seeders=(seeders-".$valid[0].") WHERE torrent=".$torrentid." AND tracker='localhost'");
			err($language['announce_9']);
		}
		
		$rz = mysql_query("SELECT id, uploaded, downloaded, class FROM users WHERE passkey=".sqlesc($passkey) ) or err('System:valid users 1');
		if (mysql_num_rows($rz) == 0) {
			err(sprintf($language['announce_10'] , $config['sitename']));
		}	
		
		$az = mysql_fetch_assoc($rz);
		$PRIV = get_priv_info($az['class']);
		
		$userid = $az["id"];
		
		
		if ($PRIV['bad_rating'] && $seeder == '0') {
			if(get_ratio($az['uploaded'] , $az['downloaded']) < $config['bad_rating']) {
				err(sprintf($language['announce_11'] , ($config['bad_rating'] - 1) ));
			}
		}
	} else {
		
		$upthis = max(0, $uploaded - $self['uploaded']);
		$downthis = max(0, $downloaded - $self['downloaded']);
		
		if ($upthis > 0 || $downthis > 0) {
			mysql_query('UPDATE users SET uploaded = uploaded + '.$upthis.', downloaded = downloaded + '.$downthis.' WHERE id='.$userid) or err('System:valid users 2');
		}
		

	}
}


///////////////////////////////////////////////////////////////////////
//Пакуем информацию
///////////////////////////////////////////////////////////////////////
$dt =sqlesc(date('Y-m-d H:i:s', time()));
$updateset = array();
$snatch_updateset = array();
if ($event == 'stopped') 
{
	if (isset($self)) 
	{
		mysql_query('DELETE FROM peers WHERE '.$selfwhere) or err('System:valid peers 3');
		if (mysql_affected_rows()) 
		{
			if ($self['seeder'])
				$trupdateset[] = 'seeders = IF(seeders > 0, seeders - 1, 0)';
			else
				$trupdateset[] = 'leechers = IF(leechers > 0, leechers - 1, 0)';
		}
	}
} 
else 
{
	if ($event == 'completed') 
	{
		$snatch_updateset[] = "finished = 1";
		$snatch_updateset[] = "completedat = $dt";
		$updateset[] = 'completed = completed + 1';
	}
	
	
	if (isset($self))
	{
		$downloaded2 = max(0, $downloaded - $self['downloaded']);
		$uploaded2 = max(0, $uploaded - $self['uploaded']);
		if ($downloaded2 > 0 || $uploaded2 > 0) 
		{
			$snatch_updateset[] = "uploaded = uploaded + $uploaded2";
			$snatch_updateset[] = "downloaded = downloaded + $downloaded2";
			
		}
		
		$prev_action = $self['last_action'];
		mysql_query("UPDATE peers SET uploaded = ".$uploaded.", downloaded = ".$downloaded.", uploadoffset = ".$uploaded2.", downloadoffset = ".$downloaded2.", to_go = ".$left.", last_action = NOW(),  seeder = '".$seeder."'"
			. ($seeder == "1" && $self["seeder"] != $seeder ? ", finishedat = ".time()." " : "") . " WHERE ".$selfwhere."") or err('System:valid peers 4');
		if (mysql_affected_rows() && $self['seeder'] != $seeder)
		{
			if ($seeder  == '1')
			{
				$trupdateset[] = 'seeders = seeders + 1';
				$trupdateset[] = 'leechers = IF(leechers > 0, leechers - 1, 0)';
			} 
			else
			{
				$trupdateset[] = 'leechers = leechers + 1';
				$trupdateset[] = 'seeders = IF(seeders > 0, seeders - 1, 0)';
			}
		}
		
	} 
	else 
	{
		
		if (portblacklisted($port))
			err('Port '.$port.' is blacklisted.');
		else 
		{
			$sockres = @fsockopen($ip, $port, $errno, $errstr, 5);
			
			if (!$sockres)
			{
				$connectable = '0';
			}
			else 
			{
				$connectable = '1';
				fclose($sockres);
			}
		}
		
/*	
		if(!$GUEST) {
			$res = mysql_query('SELECT finished, completedat FROM snatched WHERE torrent = '.$torrentid.' AND userid = '.$userid) or err('(snatched)Ошибка при выборке');
			$SN = mysql_fetch_assoc($res);
			
			
			if (!$SN)
				mysql_query("INSERT INTO snatched (torrent, userid, startdat) VALUES (".$torrentid.", ".$userid.", ".$dt.")") or err('(snatched)Ошибка при добавлении записи');
		}
*/
		
		$ret = mysql_query("INSERT INTO peers (connectable, torrent, peer_id, ip, port, uploaded, downloaded, to_go, started, last_action, seeder, userid, agent, uploadoffset, downloadoffset, passkey) VALUES ('$connectable', $torrentid, " . sqlesc($peer_id) . ", " . sqlesc($ip) . ", $port, $uploaded, $downloaded, $left, NOW() , NOW() , '$seeder', '$userid', " . sqlesc($agent) . ", $uploaded, $downloaded, " . sqlesc($passkey) . ")")  or err('(peers)Произошла ошибка при добавлении записи');
		if ($ret) 
		{
			if ($seeder == '1')
			{
				$trupdateset[] = 'seeders = seeders + 1';
			}
			else
			{
				$trupdateset[] = 'leechers = leechers + 1';
			}	
		}
	}
	
}

if ($seeder == '1') 
{
	$updateset[] = 'last_action = '.$dt;
}

if (count($trupdateset))
	mysql_query('UPDATE trackers SET ' . join(", ", $trupdateset) . ' WHERE torrent = '.$torrentid.' AND tracker="localhost"') or err('(trackers)Произошла ошибка при обновлении данных');

if (count($updateset))
	mysql_query('UPDATE torrents SET ' . join(", ", $updateset) . ' WHERE id = '.$torrentid) or err('(torrents)Произошла ошибка при обновлении данных');

if (count($snatch_updateset))
	mysql_query('UPDATE snatched SET ' . join(", ", $snatch_updateset) . ' WHERE torrent = '.$torrentid.' AND userid = '.$userid) or err('(snatched)Произошла ошибка при обновлении данных');


benc_resp_raw($resp);
?> 
