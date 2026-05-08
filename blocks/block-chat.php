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

$basePath = trim((string) dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/')), '/\\');
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
