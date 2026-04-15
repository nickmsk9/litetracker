<?php
/*
===================================================================
LiteTracker Source
===================================================================
by jenaDI
-------------------------------------------------------------------
Назначение: Конфиг
===================================================================
*/

//Если ты не гнида черножопая , не удаляй эти строки
//Если ты взял за основу мой движок , пиши "mod by Name"
define('LITETRACKER' , 1);
define('LITETRACKER_VERSION' , '1.0.1 alpha'); 
define('LITETRACKER_NAME' , 'Evolution!');
$litetrackerYears = date('Y') > 2026 ? '2026 - '.date('Y') : '2026';
define('LITETRACKER_COPYRIGHT' , '<a href="http://litetracker.ru/" target="_blank">LiteTracker '.LITETRACKER_NAME.' </a> (release '.LITETRACKER_VERSION.') &copy; '.$litetrackerYears.' <br> <a href="https://t.me/nswbt" target="_blank">Никита Севальнев</a> ');
?>
