<?php
/*
===================================================================
LiteTracker Source
===================================================================
by Nick
-------------------------------------------------------------------
Назначение: Выход
===================================================================
*/

//Подключаем главный системный файл
require __DIR__ . '/app/system/init.php';

//Проверяем пользователя
if(!$USER) {
	head($language['template_11']);
	msg($language['default_1'] , $language['default_6'] );
	foot();
	die();
}

if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
	head($language['template_11']);
	msg($language['default_1'], 'Выход доступен только через кнопку выхода в меню.', 1);
	foot();
	die();
}

if (!lt_csrf_validate('logout')) {
	head($language['template_11']);
	msg($language['default_1'], 'Защитный токен устарел. Обновите страницу и попробуйте снова.', 1);
	foot();
	die();
}

//Удаляем cookies
logout_cookie();

header('Location:index.php');
die();
?>
