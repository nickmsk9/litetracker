<?
/*
===================================================================
LiteTracker Source
===================================================================
by jenaDI
-------------------------------------------------------------------
Назначение: Профиль пользователя
===================================================================
*/

//Подключаем главный системный файл
require 'system/init.php';


//Проверяем права
if(!$PRIV['profile_view']) {
	err($language['default_1'] , $language['profile_20'] , 1);
}

//Определяем номер пользователя
$id = (int)$_GET['id'];

if(false == ($arr = get_user_info($id) ) ) {
	err($language['default_1'] , $language['profile_1'] , 1);
}


//Ник пользователя
$name = get_user_color($arr['class'] , $arr['name']);	

//Зарегистрирован
$date = convent_date($arr['added']);

//E-mail адрес
$email = htmlspecialchars($arr['email']);

//IP - адрес
$ip = ($PRIV['ip_util'] ? '<a href="ip.util.php?ip='.long2ip($arr['ip']).'">'.long2ip($arr['ip']).'</a>' : long2ip($arr['ip']));


//Последнее посещение 
$last_access = convent_date($arr['last_access']);

//Аватар
$avatar = ($arr['avatar'] ? '<center><img src="public/avatars/'.$arr['avatar'].'" width="150"></center>' : '<center><img src="public/images/default_avatar.gif"></center>');

//Пол
$sex = ($arr['sex'] ? $language['setting_34'] : $language['setting_35']);

//ICQ
$icq = (int)$arr['icq'];

//Skype
$skype = htmlspecialchars($arr['skype']);

//ID vkontakte
$id_vkontakte = (int)$arr['id_vkontakte'];


//Веб-сайт
if(!empty($arr['website']) ) {
	$website = '<a href="/redirector.php?url='.$arr['website'].'" target="_blank">'.$arr['website'].'</a>';
}

//Скачал
$downloaded = mksize($arr['downloaded']);


//Раздал
$uploaded = mksize($arr['uploaded']);

//Забанен
$banned = ($arr['banned'] ? $language['profile_3'] : $language['profile_4']);


//Онлайн 
$dt  = get_date_time(gmtime() - 100);
if($arr['last_access'] > $dt) {
	$online = '<small><font color="#BEBEBE">'.$language['profile_2'].'</font></small>';
} 


//Друзья пользователя
$friends_arr = array();
$friends = $db->query("SELECT *
					  FROM friends 
					  WHERE friendid = ".$id." AND status = 'yes'
					  GROUP BY id
					  ORDER BY RAND()
					  LIMIT 10");
if($db->num_rows($friends)) {
	while($row = $db->get_row($friends) ) {
		$friends_arr[] = $row;
	}
}

head($language['profile_5'] , true);

comment_status();
//Подключаем шаблон
require 'templates/'.$config['template'].'/tpl.profile.php';	
foot(true);
?>