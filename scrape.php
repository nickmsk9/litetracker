<?
/*
===================================================================
LiteTracker
===================================================================
by Nick
-------------------------------------------------------------------
Назначение: Scrape - файл
===================================================================
*/

define ('IN_ANNOUNCE', true);
require_once('system/init.announce.php');
require_once('system/functions/functions.announce.php');
global $config;



$r = 'd5:files';


if (!isset($_GET["info_hash"]))
	die("fak off");
 if (get_magic_quotes_gpc())
  $hash = bin2hex(stripslashes($_GET["info_hash"]));
 else
  $hash = bin2hex($_GET["info_hash"]);
 if (strlen($_GET["info_hash"]) != 20)
  err("Invalid info-hash (".strlen($_GET["info_hash"]).")");




if (false === ($row = $memcache->get('scrape_'.$hash))) {
$res = mysql_query("SELECT torrents.id ,  torrents.infohash, torrents.completed, trackers.seeders, trackers.leechers FROM torrents LEFT JOIN trackers ON torrents.id=trackers.torrent WHERE torrents.infohash = " . sqlesc($hash)) or err(mysql_error());
$row = mysql_fetch_assoc($res);
$memcache->set('scrape_'.$hash, $row, 0, rand(100 , 300 ));
}

 $r .= 'd20:'.pack('H*', $row['infohash'])."d8:completei{$row['seeders']}e10:downloadedi{$row['completed']}e10:incompletei{$row['leechers']}eeee";


header("Pragma: no-cache");
print($r);


?>
