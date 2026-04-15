<?php
/*
===================================================================
LiteTracker Source
===================================================================
by jenaDI
-------------------------------------------------------------------
Назначение: Чат
===================================================================
*/

global $config ,$PRIV , $USER ,$language;

//////////////////////////////////////////////////////////////////
//Вывод чата
//////////////////////////////////////////////////////////////////
if($PRIV['chat_view']) {
	echo '<script type="text/javascript" src="public/js/chat.js"></script>';
	echo '<link rel="stylesheet" href="public/css/chat.css" type="text/css" media="screen" charset="utf-8">';
	require 'templates/'.$config['template'].'/blocks/block.chat.php';
	
}
?>
