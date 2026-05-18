<?php
/*
===================================================================
LiteTracker Source
===================================================================
by Nick
-------------------------------------------------------------------
Назначение: FAQ - система
===================================================================
*/

//Подключаем главный системный файл
require 'system/init.php';

$act = (string) ($_GET['act'] ?? '');
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$type = (string) ($_GET['type'] ?? '');


////////////////////////////////////////////////////////
//Вывод темы
////////////////////////////////////////////////////////
if($act == 'view') {
	$sql = $db->query("SELECT * FROM faq WHERE id=".$id);
	if(!$db->num_rows($sql)) {
		err('Ошибка' , 'Данной темы не найдено' , 1);
	}
	$arr = $db->get_row($sql);



	head(htmlspecialchars((string) ($arr['subject'] ?? ''), ENT_QUOTES, 'UTF-8'));
	//Выводим статусы
	comment_status();

	begin_frame(htmlspecialchars((string) ($arr['subject'] ?? ''), ENT_QUOTES, 'UTF-8').($PRIV['faq_moderate'] ? '<div style="float:right"><a href="faq.php?act=topic&type=edit&id='.$id.'">[Редактировать]</a> <a href="faq.php?act=topic&type=delete&id='.$id.'">[Удалить]</a></div>' : ''));
	echo format_comment($arr['text']);


	//Информация о пользователе
	$user = get_user_info($arr['id_user']);

	//Информация о том , кто редактировал
	if($arr['last_edit'] != '0000-00-00 00:00:00') {
		$user1 = get_user_info($arr['last_edit_user']);
	}

	echo '<hr><small>Добавил <a href="'.profile_href($user['id']).'">'.get_user_color($user['class'] , $user['name']).'</a> , '.convent_date($arr['added']).' </small> '.($arr['last_edit'] != '0000-00-00 00:00:00' ? '<br> <small> И редактировал <a href="'.profile_href($user1['id']).'">'.get_user_color($user1['class'] , $user1['name']).'</a> , '.convent_date($arr['last_edit']).'</small>' : '')
	.($PRIV['faq_moderate'] ? '<div style="float:right"><small><a href="faq.php?act=topic&type=edit&id='.$id.'">[Редактировать]</a> <a href="faq.php?act=del&id='.$id.'">[Удалить]</a></small></div>' : '');
	end_frame();


	//Комментарии
	begin_frame($language['details_18']);
	listComment('faq' , $id , 'faq.php?act=view&');
	end_frame();


	foot();
	die();
}

////////////////////////////////////////////////////////
//Удаление тем
////////////////////////////////////////////////////////
if($act == 'del') {
	//Проверяем права
	if(!$PRIV['faq_moderate']) {
		err('Ошибка' , 'Вам запрещено пользоваться данной функцией' , 1);
	}

	$sql = $db->query("SELECT * FROM faq WHERE id=".$id);
	if(!$db->num_rows($sql)) {
		err('Ошибка' , 'Данной темы не найдено' , 1);
	}

	//Удаляем тему
	$db->query("DELETE FROM faq WHERE id=".$id);

	//Переадресация
	header('Location:faq.php');
	die();

}
////////////////////////////////////////////////////////
//Добавление тем
////////////////////////////////////////////////////////
if($act == 'topic') {
	//Проверяем права
	if(!$PRIV['faq_moderate']) {
		err('Ошибка' , 'Вам запрещено пользоваться данной функцией' , 1);
	}

	$type_arr = array('add' , 'edit');
	if(!in_array($type , $type_arr) ) {
		err('Ошибка' , 'Данного типа не найдено');
	}

	//ID для редактирования
	if($type == 'edit') {
		$sql = $db->query("SELECT * FROM faq WHERE id=".$id);
		if(!$db->num_rows($sql)) {
			err('Ошибка' , 'Данной темы не найдено' , 1);
		}
		$arr = $db->get_row($sql);
	}

	$arr = isset($arr) && is_array($arr) ? $arr : array('subject' => '', 'text' => '');

	//Обработка данных
	if($_POST) {
		$array = array();
		//Название темы
		$subject = trim($_POST['subject']);
		if(empty($subject) ) {
			err('Ошибка' , 'Вы не ввели название темы' , 1);
		}
		$array[] = 'subject="'.$db->safesql($subject).'"';

		//Текст темы
		$text = trim($_POST['text']);
		if(empty($text) ) {
			err('Ошибка' , 'Вы не ввели название темы' , 1);
		}
		$array[] = 'text="'.$db->safesql($text).'"';

		//Пишем в базу
		if($type == 'edit') {
			$array[] = 'last_edit=NOW()';
			$array[] = 'last_edit_user='.$USER['id'];

			//Обновляем
			$db->query("UPDATE faq SET ".implode(',' , $array)." WHERE id=".$id);
		} else {
			$array[] = 'added=NOW()';
			$array[] = 'id_user='.$USER['id'];

			//Добавляем
			$db->query("INSERT INTO faq SET ".implode(',' , $array) );
			$id = $db->insert_id();
		}


		//Переадресация
		header("Location:faq.php?act=view&id=".$id);
		die();
	}

	//Вывод формы
	$title = ($type == 'add' ? 'Добавление темы' : 'Редактирование темы');
	head($title);
	begin_frame($title);
	?>

		<form action="faq.php?act=topic&type=<?=$type;?>&id=<?=$id;?>" method="post">
		<table>
			<tr><td width="10%"><b>Название:</b></td><td><input type="text" name="subject" size="80%" value="<?=htmlspecialchars((string) ($arr['subject'] ?? ''), ENT_QUOTES, 'UTF-8');?>"></td></tr>
			<tr><td colspan="2"><?=textbb('text' , $arr['text']);?></td></tr>
			<tr><td colspan="2"><input type="submit" value="Выполнить"></td></tr>
		</table>
		</form>

	<?php
	end_frame();
	foot();
	die();
}


////////////////////////////////////////////////////////
//Вывод тем
////////////////////////////////////////////////////////
$sql =  $db->query("SELECT * FROM faq ORDER BY added DESC");

//Заголовок
head('FAQ');
begin_frame('FAQ ');
echo ($PRIV['faq_moderate'] ? '<input type="button" value="Добавить топик" onCLick="window.location.href=\'faq.php?act=topic&type=add\'"><br><br>' : '');

//Проверяем , есть ли топики
if(!$db->num_rows($sql) ) {
	$staticFaq = array(
		array(
			'q' => 'Зачем нужна регистрация?',
			'a' => 'Регистрация открывает личные сообщения, закладки, комментарии, настройки профиля и участие в жизни трекера.',
		),
		array(
			'q' => 'Я зарегистрировался, что делать дальше?',
			'a' => 'Заполните профиль, проверьте правила оформления и переходите к поиску или добавлению релизов.',
		),
		array(
			'q' => 'Я не помню пароль.',
			'a' => 'Напишите администрации через форму обратной связи и укажите ник, почту регистрации и любые данные, которые помогут подтвердить аккаунт.',
		),
		array(
			'q' => 'Как изменить данные профиля?',
			'a' => 'Откройте меню пользователя в шапке сайта и перейдите в раздел «Настройки».',
		),
		array(
			'q' => 'Почему релиз может быть скрыт?',
			'a' => 'Релиз скрывается при нарушении правил оформления, жалобе правообладателя, вредоносном содержимом или ошибочном описании.',
		),
		array(
			'q' => 'Как связаться с администрацией?',
			'a' => 'Используйте ссылку «Обратная связь» внизу страницы и выберите подходящую тему обращения.',
		),
	);

	echo '<div class="static-page">';
	foreach ($staticFaq as $item) {
		echo '<h3>'.htmlspecialchars($item['q'], ENT_QUOTES, 'UTF-8').'</h3>';
		echo '<p>'.htmlspecialchars($item['a'], ENT_QUOTES, 'UTF-8').'</p>';
	}
	echo '</div>';
} else {
	echo '<link href="public/css/torrenttable.css" rel="StyleSheet" type="text/css">
	<table align="center" width="70%" class="tt">
	<tr>

	<td><b>Название</b></td>
	<td><b>Дата</b></td>
	<td><b>Кем создана</b></td>
	</tr>';

	while($arr = $db->get_row($sql) ) {
		//Информация о пользователе
		$user = get_user_info($arr['id_user']);
		echo '<tr>
		<td><a href="faq.php?act=view&id='.$arr['id'].'"><b>'.htmlspecialchars((string) ($arr['subject'] ?? ''), ENT_QUOTES, 'UTF-8').'</b></a></td>
		<td>'.convent_date($arr['added']).'</td>
		<td><a href="'.profile_href($user['id']).'">'.get_user_color($user['class'] , $user['name']).'</a></td>
		</tr>';
	}

	echo '</table>';
}
end_frame();
//Подвал
foot();


?>
