<?php
/*
===================================================================
LiteTracker Source
===================================================================
by jenaDI
-------------------------------------------------------------------
Назначение: Глобальный блок категорий
===================================================================
*/
global $config, $rewrite;
$cache_result = categories_array();
$categories = '';
foreach ($cache_result AS $cat) {
	$categories .=  '<a class="proleft" href="'.$rewrite->encode('browse.php?id_category='.$cat['id']).'">'.htmlspecialchars($cat['name']).'</a>';
}	

require 'templates/'.$config['template'].'/blocks/block.categories.php';
?>
