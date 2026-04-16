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

$config  = array(
'sitename' => 'LiteTracker Engine' , //Название сайта
'gzip' => 1 , //Использовать gzip-сжатие
'template' => 'default', //Шаблон сайта
'lang' => 'Russian', //Язык сайта
'siteonline' => 1, //Сайт открыт - 1 / Сайт закрыт - 0
'rewrite' => 0, //ЧПУ

//Рейтинг
'bad_rating' => 5 , //Рейтинг , который на грани плохого . Если у пользователя рейтинг ниже , то ему закрываются некоторые функции
'days_rating' => 30 , //Через какое время будет записываться плохой рейтинг (в днях)
//Деньги
'begin_money' => '3', //Начальный деньги при регистрации
'wmz_number' => 'Z223695950388' , //Кошелек WMZ
'wmr_number' => 'R266587927979' , //Кошелек WMR
'project_help_text' => 'Оплата аренды сервера, принимаем любую помощь.' ,
'project_help_period' => '' ,
'project_help_current' => 5873 ,
'project_help_goal' => 4900 ,
'project_help_button_label' => 'Помочь проекту' ,
'project_help_button_href' => '' ,

'registeronline' => 1, //Регистрация открыта
'announce_url' => (((int) ($_SERVER['SERVER_PORT'] ?? 80) === 443) ? 'https://' : 'http://').htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'localhost', ENT_QUOTES, 'UTF-8').'/announce.php' , //Адрес URL. Не менять , если не знаешь что это такое
'announce_interval' => 30*60 ,

'max_size_image' => 5*1024*1024, //Макс размер загружаемой картинки

//Чат
'chat_limit' => 5, //Интервал отправки чата
'chat_limit_text' => 550, //Лимит текста 
'chat_load_in_server' => 60, //Максимальная нагрузка сервера , при которой будет работать чат (искл:VIP, Администрация)


//Модули поиска
'search_forum' => 0 , //Включить форумно-видовой вывод
'search_video' => 0 , //Включить модуль "Видео" в поиске  
'search_image' => 0 , //Включить модуль "Картинки" в поиске 
'search_video_lenght' => 0 , //Количество символов , при котором будут выводится видео
'search_image_lenght' => 0 , //Количество символов , при котором будут выводится видео
					  


//Новинка
'releases_news' => 30 , //В течение какого времени релиз считается "новикой" (n дней)


//Привязка cookies к домену
'cookies_mode' => 0 ,

//Система голосов
//голоса начисляются за "чистый" час раздачи . За 1 мин раздачи , пользователь получает {voice_price}/60 голосов
'voice_price' => 3 , //Количество голосов получаемых пользователем

//reCaptcha
'reCaptcha' => 0 , //Использовать reCaptcha
'reCaptcha_publickey' => '6Ldxf8USAAAAANwkMPdxN5yJRrrkyPuB1UTVnpoM ' , //Ваш publickey
'reCaptcha_privatekey' => '6Ldxf8USAAAAAF_qfi_PhrhpbT5iItUzuVyrNU7W ' , //Ваш publickey

//reCaptcha for LiteTracker
'reCaptcha_login' => 0 , //Использовать для входа
'reCaptcha_signup' => 1 , //Использовать для регистрации
'reCaptcha_download' => 0 , //Использовать для скачивания


//Настройка отправки писем
'mail' => array(
			'use' => 1 , //Использовать e-mail функции
			'type' => 'mail' , //Тип отправки почты 
								   //mail - по умолчанию , отправка функцией mail
								   //smtp - отправка smtp
			'from' => 'admin@litetracker.ru'  , //Какой e-mail указывать
			'from_name' => 'Torrent - Tracker', 
			//Если используете SMTP
			'host' => '' , //Хост сервера
			'port' => 25 , //Порт сервера
			'login' => '' , //Логин
			'password' => '' ,  //Пароль
			
			//Модули трекера
			'signup' => 0 , //Использовать подтверждение по e-mail
			
		),
		
'crontab' => 0 , //Использовать планировщик заданий cronNNLite 
					//При использовании данной функции требуется программа cronNNLite или добавить задание в etc/crontab
					//[Внимание! При включение данной фукнции, все части трекера (к примеру : обновление, автоочистка) отключаются]
					//0,15,30,45   *   *   *   *   root   /usr/bin/wget -O /dev/null -q http://site.com/autoclean.php > /dev/null 2>&1
					//0/10   *   *   *   *   root   /usr/bin/wget -O /dev/null -q http://site.com/update.peers.php > /dev/null 2>&1

					
					
'sql_log_file' => 'logs/mysql_log_'.date("M_D_Y").'.log' , //Файл с логами ошибок mySQL

'blocks_use' => 1 , //Использовать блоки ?
);


//Jткладка sql - запросов
define('DEGUB_SQL' , 0);

//Настройка cookies
define ("COOKIE_SALT", '[default]'); 
define ("COOKIE_ID", 'id_user'); //Название ID
define ("COOKIE_PASSWORD", 'id_password'); //Название PASSWORD
?>
