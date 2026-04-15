<?
/*
===================================================================
LiteTracker Source
===================================================================
by jenaDI
-------------------------------------------------------------------
Назначение: Последние новости
===================================================================
*/

global $memcache , $db , $config;
if (false === ($news_array = $memcache->get('news')))
	{
		$query =  $db->query("SELECT * FROM news
							WHERE ADDDATE(date, INTERVAL 10 DAY) > NOW() ORDER BY date DESC  LIMIT 3");
		$news_cache = array();

		while ($cache_data = $db->get_row($query) )
			$news_cache[] = $cache_data;	
		
		
		$memcache->set('news', $news_cache , 0, 15* 60);
		$news_array = $news_cache;
	}
	
if($news_array)  {	
	require 'templates/'.$config['template'].'/blocks/block.news.php';
}
?>

