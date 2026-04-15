<?
/*
===================================================================
LiteTracker Source
===================================================================
by jenaDI
-------------------------------------------------------------------
Назначение: Граббер описания
===================================================================
*/

die('Стоп! Я не доделал! Если доделаете, то пишите на форум http://litetracker.ru');


//Подключаем главный системный файл
require 'system/init.php';

//Проверка авторизации
is_login();

//Кодировка
header("Content-Type: text/html; charset=".$language['charset']."");





//Название релиза
$name = (string)$_REQUEST['name'];
if($name == '') {
	die();
}
		
//Ссылка на сайт
$link = 'http://www.kinopoisk.ru';

//Ссылка на поиск
$link_search = $link.'/index.php?first=no&kp_query=';

//Ссылка на детали фильма
$link_details = $link.'/level/1/film/';


//Cookie
$cookie = '__utma=168025531.1979789155.1266433031.1104595361.1104599240.5; __utmz=168025531.1104517588.3.2.utmcsr=google|utmccn=(organic)|utmcmd=organic|utmctr=kinopoisk; last_visit=2010-06-30+20%3A13%3A04; my_perpages=a%3A0%3A%7B%7D; users_info[check_sh_bool]=none; search_last_date=2010-06-30; search_last_month=2010-06; __utmc=168025531; PHPSESSID=d0f1130556ced3097f2041622a70a91c; __utmb=168025531.9.10.1104599240; forum_data[reg_key]=25bfd816c731b9179a5f2ef5433b6a86; forum_data[login]=jenaDI; uid=1158699; mykp_button=edit_main';

//Заголовки
$opts = array(
		'http'=>array(
		'method'=>"GET",
		'header'=>"Accept-language: ru\r\n" .
              "Cookie: ".$cookie."\r\n"  ,
		'User-Agent' => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; ru; rv:1.9.0.4) Gecko/2008102920 AdCentriaIM/1.7 Firefox/3.0.4'	  
		),
		
);

$headers = stream_context_create($opts);	

///////////////////////////////////////////////////////////////////////
//1 шаг
//---------------------------------------------------------------------
//Ищем и определяем id 
///////////////////////////////////////////////////////////////////////

//Открываем соединение
$getContent = file_get_contents($link_search.urlencode($name), false ,  $headers);

if(!$getContent)
	die('Ошибка соединения');
//Определяем id 
preg_match('/\\/level\\/1\\/film\\/(\d+)/i', $getContent, $descriptionID); 


///////////////////////////////////////////////////////////////////////
//2 шаг
//---------------------------------------------------------------------
//Извлекаем описание
///////////////////////////////////////////////////////////////////////


// Открываем соединение
$getContentDescr = file_get_contents($link_details.$descriptionID[1].'/', true, $headers);
echo $getContentDescr;
if(!$getContentDescr) {
	die('Ошибка соединения с деталями');
}



// Извлекаем описание
preg_match('#<span class=\"_reachbanner_\">(.*?)</span>#si', $getContentDescr, $description); 


?>
