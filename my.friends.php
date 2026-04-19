<?
/*
===================================================================
LiteTracker Source
===================================================================
by Nick
-------------------------------------------------------------------
Назначение: Мои друзья
===================================================================
*/

//Подключаем главный системный файл
require 'system/init.php';

//Проверяем пользователя
is_login();

///////////////////////////////////////////////////////////////
//Одобрение / Не одобрение заявки
///////////////////////////////////////////////////////////////
if($_GET['act'] == 'check') {

	//ID друга
	$friendid = (int)$_GET['friendid'];
	$check  = $_GET['check'];
	$array_check = array('yes' , 'no' ,  'delete');
	if(!in_array($check, $array_check) ) {
		err('Ошибка' , 'Неверное действие');
	}

	if($friendid == $USER['id']) {
		err('Ошибка' , 'Вы не можете дружить самим с собой');
	}

	//Проверяем заявку
	$sql = $db->query("SELECT * FROM friends WHERE (userid=".$USER['id']." AND friendid=".$friendid.") OR (friendid=".$USER['id']." AND userid=".$friendid.")   AND ".($check == 'delete'  ? 'status = "yes"' :  'status="pending"'));
	$arr = $db->get_row($sql);

	//Выводим ошибку, в зависимости от действия
	if($check == 'yes') {
		if(!@$db->num_rows($sql) ) {
			err('Ошибка' , 'Данной заявки не найдено, или вы уже ее одобрили' , 1);
		}
	} elseif($check == 'delete') {
		if(!@$db->num_rows($sql) ) {
			err('Ошибка' , 'Данной заявки не найдено, или вы уже ее удалили' , 1);
		}
	} elseif($check == 'no') {
		if(!$db->num_rows($sql) ) {
			err('Ошибка' , 'Данной заявки не найдено, или вы уже ее отклонили' , 1);
		}
	}


	//Выполняем действие
	if($check == 'yes') {
		$db->query("UPDATE friends SET status='yes' WHERE userid=".$arr['userid']." AND friendid=".$USER['id']);
		$db->query("INSERT INTO friends (userid  , friendid , date , status) VALUES (".$USER['id']." , ".$arr['userid']." , NOW() , 'yes')");
	} elseif($check == 'delete')  {
		$db->query("DELETE FROM friends WHERE userid=".$USER['id']." AND friendid=".$friendid);
		$db->query("DELETE FROM friends WHERE userid=".$friendid." AND friendid=".$USER['id']);
	} elseif($check == 'no')  {
		$db->query("UPDATE friends SET status='no' WHERE userid=".$arr['userid']." AND friendid=".$USER['id']);
	}


	//Обновляем счетчик
	if($USER['num_friends']) {
		$db->query("UPDATE users SET num_friends = (num_friends - 1) WHERE id=".$USER['id']);
	}
	$memcached->delete('user_'.$USER['id']  ,  0);


	//Перенаправление
	if($check == 'yes') {
		header('Location:my.friends.php?status=2');
	} elseif($check == 'delete')  {
		header('Location:my.friends.php?status=3');
	} elseif($check == 'no') {
		header('Location:my.friends.php?status=4');
	}



	die();
}


///////////////////////////////////////////////////////////////
//Добавление в друзья
///////////////////////////////////////////////////////////////
if($_GET['act'] == 'add') {
	//ID друга
	$friendid  = (int)$_GET['friendid'];

	//Проверяем друга
	$user_check = get_user_info($friendid);
	if(!$user_check) {
		err('Ошибка' , 'Данного пользователя нету . Повторите попытку' , 1);
	}

	//Проверяем , дружит ли с головой данный пользователь :D
	if($friendid == $USER['id']) {
		err('Ошибка' , 'Если ты сделал это целенаправленно , то ты с головой не дружишь !');
	}

	//Проверяем ,  не друзья ли они между собой
	$sql = $db->query("SELECT *  FROM friends
								WHERE (userid=".$USER['id']." AND friendid = ".$friendid.") OR
									  (userid=".$friendid." AND friendid = ".$USER['id'].")
							    ");

	//Если заявка существует
	if($db->num_rows($sql) ) {
		$arr = $db->get_row($sql);
	}

	//Проверяем статус заявки
	if($arr['status'] == 'yes') {
		err('Ошибка' , 'Вы уже друзья'  ,1);
	} elseif($arr['status'] == 'pending') {
		err('Ошибка' , 'Заявка уже находится в базе' , 1);
	} elseif($arr['status'] == 'no') {
		err('Ошибка' , 'Он не хочет с вами дружить , поэтому отклонил заявку :('  , 1);
	}

	//Если не друзья ,  тогда добавляем их заявку
	$db->query("INSERT INTO friends (userid  , friendid , date , status) VALUES (".$USER['id']." , ".$friendid." , NOW() , 'pending')");
	$db->query("UPDATE users SET num_friends = num_friends + 1 WHERE id=".$friendid);


	$memcached->delete('user_'.$friendid , 0);

	header('Location: my.friends.php?status=1');
	die();
}


$id = (int)$_GET['id'];
if(!$id) {
	$id = $USER['id'];
}

///////////////////////////////////////////////////////////////
//Вывод моих друзей
///////////////////////////////////////////////////////////////
$title = ($id != $USER['id'] ? 'Друзья пользователя' : 'Мои друзья');
head($title);

//Статусы
switch($_GET['status']) {
	case '1' :
		msg('Успешно' , 'Вас будующий друг будет оповещен о вашем действии');
	break;

	case '2' :
		msg('Успешно' , 'Теперь вы друзья');
	break;

	case '3' :
		msg('Успешно' , 'Теперь вы не друзья');
	break;

	case '4' :
		msg('Успешно' , 'Вы отклонили заявку');
	break;
}

//Новые заявки
if($id == $USER['id']) {
	$sql = $db->query("SELECT * FROM friends WHERE friendid = ".$USER['id']." AND status = 'pending'");
	if($db->num_rows($sql) ) {
		// begin_frame('С вами хотят дружить :)');
			while($arr = $db->get_row($sql) ) {
				//Информация о пользователе
				$userid  = $arr['userid'];
				$user = get_user_info($arr['userid']);
				$avatar  = ($user['avatar'] ? '<img src="public/avatars/'.$user['avatar'].'" width="50">' : '<center><img src="public/images/default_avatar.gif" width="50"></center> ');
				$name = get_user_color($user['class'] , $user['name']);

				//Дата
				$date  = convent_date($arr['date']);

				//Действия
				$action = "<input type='button' value='Добавить в друзья' onClick='window.location.href=\"my.friends.php?act=check&friendid=".$arr['userid']."&check=yes\"'>
				<input type='button' value='Отклонить' onClick='window.location.href=\"my.friends.php?act=check&friendid=".$arr['userid']."&check=no\" '>
				";

				//Выводим шаблон
				require 'templates/'.$config['template'].'/tpl.friends.php';
			}
		// end_frame();
	}
}

//Вывод моих друзей
$sql = $db->query("SELECT * FROM friends WHERE userid = ".$id." AND status = 'yes'");

	// begin_frame($title);

		if($db->num_rows($sql) ) {
			//Выводим друзей
			while($arr = $db->get_row($sql )  ) {

				//Информация о пользователе ( не о своем )
				$userid =  $arr['friendid'];
				$user = get_user_info($arr['friendid']);
				$avatar  = ($user['avatar'] ? '<img src="public/avatars/'.$user['avatar'].'" width="50">' : '<img src="public/images/default_avatar.gif" width="50">');
				$name = get_user_color($user['class'] , $user['name']);

				//Дата
				$date  = convent_date($arr['date']);

				//Действия
				$action = "<input type='button' value='Убрать из друзей' onClick='window.location.href=\"my.friends.php?act=check&friendid=".$arr['friendid']."&check=delete\" '>";

				//Выводим шаблон
				require 'templates/'.$config['template'].'/tpl.friends.php';
			}
		} else {
			msg("Печаль - то какая :("  , "Вы еще ни с кем не подружились . <br>
			Добавляйте друзей , обрайтесь с ними в чате , обменивайтесь сообщениями , граффити , файлами  и многим другим ! ");
		}
	// end_frame();

foot();
?>
