<?php
/*
===================================================================
LiteTracker Source
===================================================================
by Nick
-------------------------------------------------------------------
Назначение: Нагрузка на сервер
===================================================================
*/

global $memcache , $db , $language;

if (false === ($load_in_server = $memcache->get('load_in_server_v2') ) ) {
			$sql = $db->query("SELECT  userid   FROM peers GROUP BY userid");
			$connected = $db->num_rows($sql);

			$avgload = get_server_load();

			if (strtolower(substr(PHP_OS, 0, 3)) != 'win') {
				$percent = $avgload * 4;
			}else {
				$percent = $avgload;
			}

			$state = ($percent <= 50 ? 'green' : ($percent <= 70 ? 'yellow' : 'red'));
			$percent_label = max(0, min(100, round($percent)));
			$load_in_server = '<div class="load-widget">'
			.'<div class="load-widget-text">Текущая нагрузка сервера и активность подключений.</div>'
			.'<div class="load-widget-progress" title="Нагрузка: '.$percent.'%, Средняя (LA): '.$avgload.'">'
			.'<div class="load-widget-progress-label"><strong>Нагрузка: '.$percent_label.'% (LA: '.$avgload.')</strong></div>'
			.'<div class="load-widget-progress-bar load-widget-progress-bar-'.$state.'" style="width: 100%;"></div>'
			.'</div>'
			.'<div class="load-widget-meta">'.sprintf($language['load_in_server_2'] ,$connected).'</div>'
			.'<div class="load-widget-action"><a href="browse.php?act=all" class="load-widget-button">Открыть каталог</a></div>'
			.'</div>';
			$memcache->set('load_in_server_v2', $load_in_server  , 0, (15 * 60));
	}


begin_frame($language['load_in_server_1']);
echo $load_in_server;
end_frame();

?>
