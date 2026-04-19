<?
/*
===================================================================
LiteTracker Source
===================================================================
by Nick
-------------------------------------------------------------------
Назначение: Просмотр видео ВКонтакте
===================================================================
*/


//Подключаем главный системный файл
require 'system/init.php';
require 'system/functions.comments.php';

$id = (int)$_GET['id'];
$db->query("SELECT video_vkontakte , name FROM torrents WHERE video_vkontakte != '' AND id=".$id);
if(!$db->num_rows()) {
	err($language['default_1'] , $language['video_vkontakte_5']);
}
$arr = $db->get_row();

//Заголовок
head($language['video_vkontakte_4']);

//Выводим статусы
comment_status();



//Выводим видео
begin_frame(htmlspecialchars($arr['name']));
echo '<iframe src="'.htmlspecialchars($arr['video_vkontakte']).'" width="95%" height="350" frameborder="0"></iframe>';
end_frame();


//Комментарии
begin_frame($language['details_18']);
listComment('torrents' , $id , 'video_vkontakte.php');
end_frame();


//Подвал
foot();
?>
