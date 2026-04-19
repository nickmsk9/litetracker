<?php
/*
===================================================================
LiteTracker Source
===================================================================
by Nick
-------------------------------------------------------------------
Назначение: Видео ВКонтакте
===================================================================
*/

if (false === ($video_cache = $memcached->get('video_vkontakte')))
{
	$query =  $db->query("SELECT * FROM torrents  WHERE video_vkontakte != '' ORDER BY RAND() LIMIT 1") or sqlerr(__FILE__, __LINE__);
	$video_cache = array();

	while ($cache_data = $db->get_row() )
		$video_cache[] = $cache_data;

	//Ставим кеш на 24 часа
	$memcached->set('video_vkontakte', $video_cache , 0, 24*60*60);

}

if($video_cache) {
	begin_frame($language['video_1']);
	echo '<table width="100%" align="center"><tr>';
	foreach($video_cache AS $video_row) {
		echo '<td align="center"><a href="details.php?id='.$video_row['id'].'" class="proleft">'.htmlspecialchars($video_row['name']).'</a><br><iframe src="'.htmlspecialchars($video_row['video_vkontakte']).'" width="100%" height="360" frameborder="0"></iframe></td>';
	}
	echo '</tr></table>';

	end_frame();
}
?>
