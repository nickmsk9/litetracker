<?
/*
===================================================================
LiteTracker Source
===================================================================
by Nick
-------------------------------------------------------------------
Назначение: Общий вид релизов
===================================================================
*/


/////////////////////////////////////////////////////////
//Общее
/////////////////////////////////////////////////////////
//ID релиза
$id = $arr['id'];
//Обложка
$image = $arr['image'];
//Имя релиза
$name = array();

//Добавлен
$date = convent_date($arr['added']);

$name[] = htmlspecialchars($arr['name']);
//Год
if($arr['year'] != 0) {
	$name[] = htmlspecialchars($arr['year']);
}
//Язык
if($arr['language'] != '') {
	$name[] = htmlspecialchars($arr['language']);
}
//Качество
if($arr['quality'] != '') {
	$name[] = htmlspecialchars($arr['quality']);
}
$name = implode(' / ' , $name);


//Информация о Категории
$category = categories_array($arr['id_category']);

//Имя категории
$cat_name = htmlspecialchars($category['name']);

//ID Категории
$cat_id = $category['id'];

//Картинка Категории
$cat_image = $category['image'];




//Теги
$tags = tags_echo($arr['tags']);
//Взяли
$downloaded = number_format($arr['downloaded']);
//Скачали
$completed = number_format($arr['completed']);

//Файлов
$num_files = number_format($arr['num_files']);
//Размер
$size = mksize($arr['size']);

//Видео ВКонтакте
$video_vkontakte = ($arr['video_vkontakte'] ? '<a href="video_vkontakte.php?id='.$id.'"><img src="public/images/binocular.png" title="'.$language['releases_14'].'"></a>' : '');

//Определяем тип релиза
$type_seeders_array = array();
if($arr['multi']) {
	$type_seeders_array[] = '<img src="public/images/refresh.png" border="0" alt="'.$language['releases_15'].'" title="'.$language['releases_15'].'">';
}
if($arr['local_seeders']) {
	$type_seeders_array[] = '<img src="public/images/ok.gif" border="0" alt="'.$language['releases_16'].'" title="'.$language['releases_16'].'">';
}
$type_seeders = ($type_seeders_array ? implode(' ' , $type_seeders_array) : '');





/////////////////////////////////////////////////////////
//Пользователь
/////////////////////////////////////////////////////////
$user = get_user_info($arr['id_user']);;
//ID пользователя
$id_user = $user['id'];
//Имя пользователя
$user_name = $user['name'];
//Класс пользователя
$user_class = $user['class'];



/////////////////////////////////////////////////////////
//Пиры
/////////////////////////////////////////////////////////
//Раздают
$seeders = number_format($arr['seeders']);
//Качают
$leechers = number_format($arr['leechers']);
//Пиры
$peers = number_format($seeders + $leechers);

//Новинка
$news =  ($arr['new_release'] ? 1 : 0);

$banned = ($arr['banned'] ? 1 : 0);
//Подключаем шаблон
require 'templates/'.$config['template'].'/tpl.releases.php';
?>
