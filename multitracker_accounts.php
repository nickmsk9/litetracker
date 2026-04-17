<?
/*
===================================================================
LiteTracker Source
===================================================================
by jenaDI
-------------------------------------------------------------------
Назначение: Отлов мультитрекерных аккаунтов (by webnet)
===================================================================
*/

//Подключаем главный системный файл
require 'system/init.php';

//Только Администраторам , Модераторам
if(!$PRIV['multitracker_accounts']) {
	err('Ошибка' , 'Доступ закрыт' , 1);
}

//Заголовок
head('Мультитрекерные аккаунты');

$count = 0;
$array = array();
$res = $db->query("SELECT GROUP_CONCAT(DISTINCT p.torrent) AS torrents, GROUP_CONCAT(DISTINCT p.ip) AS ips, p.userid, u.name FROM peers p LEFT JOIN users u ON p.userid = u.id  GROUP BY p.userid ORDER BY p.torrent;");
while ($row = mysql_fetch_assoc($res) ) {
    if (count(explode(",",$row["ips"])) > 1 && count(explode(",",$row["torrents"])) < 5) {
        $array[] = array
        (
            "id" => $row["userid"],
            "name" => $row["name"],
            "ips" => explode(",",$row["ips"]), 
            "torrents" => explode(",",$row["torrents"]),
        );
        $count++;
    }
}
$res = $db->query("SELECT id, name FROM torrents");
while ($row = mysql_fetch_assoc($res)) $torrents[$row["id"]] = $row["name"];
 
begin_frame('Мультитрекерные аккаунты');
msg('Найдено '.$count.' мультитрекерных аккаунтов');

if($count) {
	print "<link href=\"public/css/torrenttable.css\" rel=\"StyleSheet\" type=\"text/css\"><br><table widtd=100% cellpadding=5 class='tt'>
	<tr class='header'>
	<td><b>Пользователь</b></td>
	<td><b>IP's</b></td>
	<td><b>Торренты</b></td>
	</tr>
	";
	foreach ($array as $v) 
	{
		print "<tr><td>".($v['id'] == 0 ? ' Гость' : "<a href=\"".profile_href($v["id"])."\">".$v["name"]."</a>")."</td><td>";
	 
		foreach ($v["ips"] as $ip)
			print "<a href=/ip.util.php?ip=".$ip.">".$ip."</a><br />";
	 
		print "</td><td>";
	 
		foreach ($v["torrents"] as $t)
			print "<a target=_blank href=/details.php?id=".$t."&dllist=1#seeders>".$torrents[$t]."</a><br />";
	 
		print "</td></tr>";
	}
	print "</table>";
}
end_frame();
 


//Подвал
foot();


?>
