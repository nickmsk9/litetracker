<?php
/*
===================================================================
LiteTracker Source
===================================================================
by jenaDI
-------------------------------------------------------------------
Назначение: Нагрузка на сервер
===================================================================
*/

global $memcache , $db , $language;

if (false === ($load_in_server = $memcache->get('load_in_server') ) ) {
			$sql = $db->query("SELECT  userid   FROM peers GROUP BY userid");
			$connected = $db->num_rows($sql);

			$avgload = get_server_load();
				
			if (strtolower(substr(PHP_OS, 0, 3)) != 'win') {
				$percent = $avgload * 4;
			}else {
				$percent = $avgload;
			}	
				
			if ($percent <= 50) {
				$pic = "loadbargreen.gif";
			}	
			elseif($percent <= 70) {
				$pic = "loadbaryellow.gif";
			}else {
				$pic = "loadbarred.gif";
			}
			
			$width = $percent * 4;
			$load_in_server =   "<center>
			<table class=\"main\" border=\"0\" width=\"402\"><tr><td style=\"padding: 0px; background-repeat: repeat-x\" title=\"Нагрузка: ".$percent."%, Средняя (LA): ".$avgload."\">"
			."<img height=\"15\" width=\"".$width."\" src=\"public/images/".$pic."\" alt=\"Нагрузка: ".$percent."%, Средняя (LA): ".$avgload."\" title=\"Нагрузка: ".$percent."%, Средняя (LA): ".$avgload."\">"
			."</td></tr></table>"
			."<b>".sprintf($language['load_in_server_2'] ,$connected)."</b></center>";
			$memcache->set('load_in_server', $load_in_server  , 0, (15 * 60));
}


begin_frame($language['load_in_server_1']);
echo $load_in_server;
end_frame();

?>