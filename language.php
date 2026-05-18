<?php
/*
===================================================================
LiteTracker Source
===================================================================
by Nick
-------------------------------------------------------------------
Назначение: Смена языка
===================================================================
*/


//Подключаем главный системный файл
require 'system/init.php';

$language = $_POST['language'];
if(!is_language($language)) {
	err($language['default_1'] , $language['default_6']);
}

lt_set_cookie('language', $language, 0x7fffffff, false, 'Lax');

header('Location:index.php');
die();
?>
