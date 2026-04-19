<?
/*
===================================================================
LiteTracker Source
===================================================================
by Nick
-------------------------------------------------------------------
Назначение: Последние комментарии
===================================================================
*/

//Подключаем главный системный файл
require 'system/init.php';
is_login();

//Постраничная навигация
$res = $db->query("SELECT * FROM comments_torrents");

$count = $db->num_rows($res);
list($pagertop, $pagerbottom, $limit) = pager('20', $count, 'comments.last.php?'); //Делим на страницы

//Query to database
$query  = "SELECT comments_torrents.* , comments_torrents.id AS comment_id  , torrents.name AS torrent_name , torrents.id AS torrent_id
		   FROM comments_torrents
		   LEFT JOIN torrents ON torrents.id =  comments_torrents.id_torrents
		   ORDER BY  comments_torrents.date DESC
		   ".$limit."
		   ";
$sql = $db->query($query);

//Если нету записей
if(!$db->num_rows($sql) ) {
	err('Ошибка' , 'Последних отзывов не было найдено' , 1);
}



//Заголовок
head('Последние отзывы');

begin_frame('Последние отзывы');

//Проверка, существуют ли комментарии

echo $pagertop;
//Выводим в цикле комментарии
while($arr  = $db->get_row($sql) )
{
	$id = $arr['comment_id']; //Номер комментария
	$text = cleanhtml($arr['text']); //Текст комментария


	$user = get_user_info($arr['id_user']);
	$user_id = $user['id']; //Номер пользователя
	$avatar = ($user['avatar'] == "" ? '<center><img src="public/images/default_avatar.gif" border="0" width="80"></center>' : '<center><img src="public/avatars/'.$user['avatar'].'" border="0" width="80"></center>'); //Фотография пользователя

	$user_name = $user['name']; //Имя пользователя
	$user_class = $user['class']; //Класс пользователя
	$date = convent_date($arr['date']);

	$append_edit = ($arr['date_edit'] != '0000-00-00 00:00:00' ?   $language['comments_3'].' '.convent_date($arr['date_edit']) : ''); //Дата правки комментария

	$torrent_name = htmlspecialchars($arr['torrent_name']);
	$torrent_id = $arr['torrent_id'];

	require 'templates/'.$config['template'].'/tpl.comments.php';
}

echo $pagerbottom;

end_frame();

//Подвал
foot();


?>
