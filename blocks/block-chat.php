<?php
/*
===================================================================
LiteTracker Source
===================================================================
by Nick
-------------------------------------------------------------------
Назначение: Чат
===================================================================
*/

global $config ,$PRIV , $USER ,$language;

$basePathSource = (string) ($_SERVER['PHP_SELF'] ?? '/');
$basePath = trim((string) dirname($basePathSource), '/\\');
$basePath = preg_replace('~[^a-zA-Z0-9_./-]+~', '', $basePath);
$basePath = ($basePath === '' || $basePath === '.' ? '' : '/'.$basePath);

//////////////////////////////////////////////////////////////////
//Вывод чата
//////////////////////////////////////////////////////////////////
if($PRIV['chat_view']) {
	$chatAjaxUrl = $basePath.'/ajax/chat.php';
	$chatJsUrl = $basePath.'/public/js/chat.js';
	echo '<script type="text/javascript">window.LT_CHAT_AJAX_URL = '.json_encode($chatAjaxUrl, JSON_UNESCAPED_UNICODE).';</script>';
	echo '<script type="text/javascript" src="'.htmlspecialchars($chatJsUrl, ENT_QUOTES, 'UTF-8').'"></script>';
	require 'templates/'.$config['template'].'/blocks/block.chat.php';

}
?>
