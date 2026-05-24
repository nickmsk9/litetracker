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
require __DIR__ . '/app/system/init.php';

if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
	err($language['default_1'] , 'Смена языка доступна только через POST-запрос.');
}

if (!lt_csrf_validate('language_change')) {
	err($language['default_1'] , 'Защитный токен устарел. Обновите страницу и попробуйте снова.');
}

$selectedLanguage = trim((string) ($_POST['language'] ?? ''));
if(!is_language($selectedLanguage)) {
	err($language['default_1'] , $language['default_6']);
}

lt_set_cookie('language', $selectedLanguage, 0x7fffffff, false, 'Lax');

header('Location:index.php');
die();
?>
