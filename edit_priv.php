<?
/*
===================================================================
LiteTracker Source
===================================================================
by Nick
-------------------------------------------------------------------
Назначение: Редактирование классами
===================================================================
*/
//Подключаем главный системный файл
require 'system/init.php';


//Проверка авторизации
is_login();


//Проверяем права
if(!$PRIV['EDIT_PRIV']) {
	err($language['default_1'] , $language['edit_priv_1'] , 1);
}


/////////////////////////////////////////////////////////////////
//Перемещение пользователей
/////////////////////////////////////////////////////////////////
if($_GET['act'] == 'location') {
	//Обработка
	if($_POST) {
		if (!lt_csrf_validate('edit_priv_location')) {
			err($language['default_1'], 'Защитный токен устарел. Обновите страницу и попробуйте снова.', 1);
		}

		//Из категории
		$location_1 = (int)$_POST['location_1'];
		if(!$location_1) {
			err($language['default_1'] , 'Выберете откуда будут перемещаться пользователи' , 1);
		}
		//В категорию
		$location_2 = (int)$_POST['location_2'];
		if(!$location_2) {
			err($language['default_1'] ,   'Выберете куда будут перемещаться пользователи' , 1);
		}

		//Выполняем перемещение, если категории не равны
		if($location_1 != $location_2) {
			$db->query("UPDATE users SET class=".$location_2." WHERE class=".$location_1);
		}

		header("Location:edit_priv.php?status=3");
		die();
	}


	//Обший вид
	head('Перемещение пользователей');
	begin_frame('Перемещение пользователей');

	//Создаем массив с категориями
	$db->query("SELECT * FROM priv");
	$row = array();
	while($get_row = $db->get_row() )
		$row[] = $get_row;



	echo msg('Вы можете переместить пользователей из одной категории в другую');

	echo '<form action="edit_priv.php?&act=location" method="POST">';
	echo lt_csrf_input('edit_priv_location');
	echo '<select name="location_1">';
	echo '<option value="0">(Из класса)</option>';
	foreach($row AS $arr) {
		echo '<option value="'.$arr['id'].'">'.htmlspecialchars($arr['NAME']).'</option>';
	}
	echo '</select>';

	echo '<select name="location_2">';
	echo '<option value="0">(В класс)</option>';
	foreach($row AS $arr) {
		echo '<option value="'.$arr['id'].'">'.htmlspecialchars($arr['NAME']).'</option>';
	}
	echo '</select>';
	echo '<input type="submit" value="'.$language['cats_6'].'">&nbsp';
	echo '<input type="button" value="'.$language['default_5'].'" onClick="history.go(-1);">';
	echo '</form>';

	end_frame();
	foot();
	die();
}

/////////////////////////////////////////////////////////////////
//Удаление класса
/////////////////////////////////////////////////////////////////
if($_GET['act'] == 'delete' && $_GET['id']) {
	$id = (int)$_GET['id'];
	$db->query("SELECT * FROM priv WHERE EDIT_PRIV = '0' AND id=".$id);
	if(!$db->num_rows()) {
		err($language['default_1'] , 'Данного класса не существует или данный класс нельзя удалить' , 1);
	}

	if($_POST) {
		if (!lt_csrf_validate('edit_priv_delete_'.$id)) {
			err($language['default_1'], 'Защитный токен устарел. Обновите страницу и попробуйте снова.', 1);
		}

		$location = (int)$_POST['location'];

		//Перемещаем торренты
		if($location > 0) {
			$db->query("SELECT *  FROM users WHERE class=".$location."");
			if(!$db->num_rows() ) {
				err($language['default_1'] , 'Данного класса , куда будем перемещать пользователей не существует' , 1);
			}

			//Получаем весь список торрентов
			$db->query("UPDATE users SET class='".$location."' WHERE class=".$id);

		} else {
			//Удаление всех релизов + удаление всех комментарий
			$db->query("DELETE FROM users
						WHERE class=".$id);
		}

		//Удаляем класс
		$db->query("DELETE FROM priv WHERE id=".$id);

		//Удаляем cache
		$memcached->delete('priv_'.$id , 0);

		header("Location:edit_priv.php?status=2");
		die();
	}

	//Выводим предупреждение
	head('Удаление класса');
	begin_frame('Удаление класса');
	echo 'Вы действительно хотите удалить класс?'.'<br> ';

	echo '<form action="edit_priv.php?id='.$id.'&act=delete" method="POST">';
	echo lt_csrf_input('edit_priv_delete_'.$id);
	echo '<select name="location">';
	echo '<option value="0">Удалить класс и всех пользователей</option>';

	$db->query("SELECT * FROM priv");
	while($arr = $db->get_row() ) {
		echo '<option value="'.$arr['id'].'">Перенести в класс '.htmlspecialchars($arr['NAME']).'</option>';
	}
	echo '</select>';
	echo '<input type="submit" value="'.$language['cats_30'].'">&nbsp';
	echo '<input type="button" value="'.$language['default_5'].'" onClick="history.go(-1);">';
	echo '</form>';


	end_frame();
	foot();
	die();
}


/////////////////////////////////////////////////////////////////
//Добавить / Редактировать класс
/////////////////////////////////////////////////////////////////
if($_GET['act'] == 'add' || ($_GET['act'] == 'edit') ) {
	if($_GET['act'] == 'edit') {
		$id = (int)$_GET['id'];
		$arr = $db->super_query("SELECT * FROM priv WHERE id=".$id);
		if(!$arr ) {
			err($language['default_1']  , $language['edit_priv_3'] , 1);
		}

	}
	//Обработка
	if($_POST) {
		if (!lt_csrf_validate('edit_priv_form')) {
			err($language['default_1'], 'Защитный токен устарел. Обновите страницу и попробуйте снова.', 1);
		}

		//Массив с правами
		$array = array('faq_moderate'  , 'edit_banned' ,'edit_news' , 'user_add' , 'bad_rating' , 'news_add' ,'upload', 'cats', 'chat_delete', 'chat_view', 'chat_clear',  'comments_edit', 'comments_delete', 'details_banned_view', 'details_view', 'download_torrent', 'download_magnet', 'edit_release', 'messages', 'multitracker_accounts', 'setting_user', 'ip_util', 'profile_view', 'search_query', 'sessions_view', 'sessions_clear', 'users_view' , 'block_moderators' ,  'block_administrators');
		$update = array();

		//Обрабатываем данные

		//Название
		$NAME = trim($_POST['NAME']);
		if(empty($NAME) ) {
			err($language['default_1']  , 'Введите название класса' , 1);
		}

		if($arr['NAME'] != $NAME)  {
			$update[] = 'NAME="'.$db->safesql($NAME).'"';
		}


		//Цвет класса
		$COLOR = trim($_POST['COLOR']);
		if(!empty($COLOR) && strlen($COLOR) != 6) {
			err($language['default_1']  , 'Цвет класса может состоять из 6 символов' , 1);
		}

		if($arr['COLOR'] != $COLOR)  {
			$update[] = 'COLOR="'.$db->safesql($COLOR).'"';
		}

		//Класс по умолчанию
		$SIGNUP = (int)$_POST['SIGNUP'];
		if($arr['SIGNUP'] != $SIGNUP) {
			$update[] = 'SIGNUP="'.$db->safesql($SIGNUP).'"';
		}

		//Перебираем в цикле
		foreach($array AS $row) {
			$_POST[$row] = (int)$_POST[$row];
			if($_POST[$row] != $arr[$row]) {
					$update[] = $row.'='.$_POST[$row];
			}
		}

		//Время создания
		if($_GET['act'] == 'add') {
			$update[] = 'DATE = NOW()';
		}

		//Добавляем / Обновляем данные
		if($_GET['act'] == 'add' && count($update) ) {
			$db->query("INSERT INTO priv SET ".implode(',' , $update));
		} elseif($_GET['act'] == 'edit' && count($update) )  {
			$db->query("UPDATE priv SET ".implode(',' , $update)." WHERE id=".$id);
		}

		//Удаляем cache
		$memcached->delete('priv_'.$id , 0);

		//Перенаправление
		header('Location:edit_priv.php?status=1');
		die();
	}

	$title = ($_GET['act'] == 'add' ? 'Добавление класса' : 'Редактирование класса');
	head($title);
	begin_frame($title);
	?>
	<form action="edit_priv.php?act=<?=($_GET['act'] == 'add' ? 'add' : 'edit');?>&id=<?=$id;?>" method="post">
	<?=lt_csrf_input('edit_priv_form');?>
	<table width="80%"  cellspacing="7" cellpadding="0" border="0"  align="center">
	<tbody>

	 <tr>
		<td class="ta_r" valign="top" colspan="2">
		 <span class="grey" ><b>Общее</b></span>
		</td>
		</tr>



	<tr>
		<td class="ta_r">
		 <span class="grey">Название класса:</span>
		</td>
		<td style="padding: 0px;">
		 <input type="text" name="NAME" style="margin: 0px;" size="25" class="inputText" value="<?=htmlspecialchars($arr['NAME']);?>">
		</td><td>
	   </td>
	 </tr>


		<tr>
		<td class="ta_r" valign="top">
		 <span class="grey" >Цвет класса:</span>
		</td>
		<td style="padding: 0px;">
			<input type="text" name="COLOR" style="margin: 0px;" size="25" class="inputText" value="<?=htmlspecialchars($arr['COLOR']);?>">
			<br><small>К примеру: 000000 . Коды вы можете найти <A href="http://35rus.ru/htmlcolor.php" target="_blank">здесь</a></small>
		</td><td>
	   </td></tr>

	   <tr>
		<td class="ta_r" valign="top">
		 <span class="grey" >Класс по умолчанию:</span>
		</td>
		<td style="padding: 0px;">
			<input type="checkbox" name="SIGNUP" value="1" <?=($arr['SIGNUP'] ? 'checked' : '');?> \>
			<small>При регистрации , все пользователи будут добавляться в данный класс . Такой класс может быть только один</small>
		</td><td>
	   </td></tr>


	<tr>
		<td class="ta_r" valign="top" colspan="2">
		 <span class="grey" ><b>Релизы</b></span>
		</td>
	</tr>

	   	<tr>
		<td class="ta_r" valign="top">
		 <span class="grey" >Загружать релизы:</span>
		</td>
		<td style="padding: 0px;">
			<input type="checkbox" name="upload" value="1" <?=($arr['upload'] ? 'checked' : '');?> \>
			<small>Возможность загружать релизы</small>
		</td><td>
	   </td></tr>


		<tr>
		<td class="ta_r" valign="top">
		 <span class="grey" >Просмотр релизов:</span>
		</td>
		<td style="padding: 0px;">
			<input type="checkbox" name="details_view" value="1" <?=($arr['details_view'] ? 'checked' : '');?> \>
			<small>Возможность просматривать релизы</small>
		</td><td>
	   </td></tr>

	   	<tr>
		<td class="ta_r" valign="top">
		 <span class="grey" >Видеть забанненые релизы:</span>
		</td>
		<td style="padding: 0px;">
			<input type="checkbox" name="details_banned_view" value="1" <?=($arr['details_banned_view'] ? 'checked' : '');?> \>
			<small>Возможность видеть забанненые релизы</small>
		</td><td>
	   </td></tr>


		<tr>
		<td class="ta_r" valign="top">
		 <span class="grey" >Редактировать релизы:</span>
		</td>
		<td style="padding: 0px;">
			<input type="checkbox" name="edit_release" value="1" <?=($arr['edit_release'] ? 'checked' : '');?> \>
			<small>Возможность редактировать чужые релизы</small>
		</td><td>
	   </td></tr>

		<tr>
		<td class="ta_r" valign="top">
		 <span class="grey" >Новинка месяца:</span>
		</td>
		<td style="padding: 0px;">
			<input type="checkbox" name="edit_news" value="1" <?=($arr['edit_news'] ? 'checked' : '');?> \>
			<small>Возможность делать релизы новинкой</small>
		</td><td>
	   </td></tr>


	   	<tr>
		<td class="ta_r" valign="top">
		 <span class="grey" >Банить релизы:</span>
		</td>
		<td style="padding: 0px;">
			<input type="checkbox" name="edit_banned" value="1" <?=($arr['edit_banned'] ? 'checked' : '');?> \>
			<small>Возможность банить релизы, закрывать к ним доступ</small>
		</td><td>
	   </td></tr>

	<tr>
		<td class="ta_r" valign="top">
		 <span class="grey" >Редактировать категории:</span>
		</td>
		<td style="padding: 0px;">
			<input type="checkbox" name="cats" value="1" <?=($arr['cats'] ? 'checked' : '');?> \>
			<small>Возможность добавлять, редактировать, удалять категории</small>
		</td><td>
	  </td></tr>





	 	<tr>
		<td class="ta_r" valign="top" colspan="2">
		 <span class="grey" ><b>Чат</b></span>
		</td>
	</tr>
	<tr>
		<td class="ta_r" valign="top">
		 <span class="grey" >Удалять сообщения в чате:</span>
		</td>
		<td style="padding: 0px;">
			<input type="checkbox" name="chat_delete" value="1" <?=($arr['chat_delete'] ? 'checked' : '');?> \>
			<small>Возможность удалять чужые сообщения в чате</small>
		</td><td>
	   </td></tr>

		<tr>
		<td class="ta_r" valign="top">
		 <span class="grey" >Просмотр чата:</span>
		</td>
		<td style="padding: 0px;">
			<input type="checkbox" name="chat_view" value="1" <?=($arr['chat_view'] ? 'checked' : '');?> \>
			<small>Возможность видеть и писать в чате</small>
		</td><td>
	   </td></tr>


		<tr>
		<td class="ta_r" valign="top">
		 <span class="grey" >Очистка чата:</span>
		</td>
		<td style="padding: 0px;">
			<input type="checkbox" name="chat_clear" value="1" <?=($arr['chat_clear'] ? 'checked' : '');?> \>
			<small>Возможность выполнять очистку чата</small>
		</td><td>
	   </td></tr>


		<!--
		<tr>
		<td class="ta_r" valign="top">
		 <span class="grey" >Видеть чат при большой нагрузке:</span>
		</td>
		<td style="padding: 0px;">
			<input type="checkbox" name="chat_load_in_server" value="1" <?=($arr['chat_load_in_server'] ? 'checked' : '');?> \>
			<small>Возможность видеть чат при большой нагрузке</small>
		</td><td>
	   </td></tr>	-->



		<tr>
		<td class="ta_r" valign="top" colspan="2">
		 <span class="grey" ><b>Комментарии</b></span>
		</td>
		</tr>



		<tr>
		<td class="ta_r" valign="top">
		 <span class="grey" >Редактировать комментарии:</span>
		</td>
		<td style="padding: 0px;">
			<input type="checkbox" name="comments_edit" value="1" <?=($arr['comments_edit'] ? 'checked' : '');?> \>
			<small>Возможность редактировать чужые комментарии</small>
		</td><td>
	   </td></tr>

		<tr>
		<td class="ta_r" valign="top">
		 <span class="grey" >Удалять комментарии:</span>
		</td>
		<td style="padding: 0px;">
			<input type="checkbox" name="comments_delete" value="1" <?=($arr['comments_delete'] ? 'checked' : '');?> \>
			<small>Возможность удалять чужые комментарии</small>
		</td><td>
	   </td></tr>



		<tr>
		<td class="ta_r" valign="top" colspan="2">
		 <span class="grey" ><b>Скачевание</b></span>
		</td>
		</tr>



		<tr>
		<td class="ta_r" valign="top">
		 <span class="grey" >Скачивать торренты:</span>
		</td>
		<td style="padding: 0px;">
			<input type="checkbox" name="download_torrent" value="1" <?=($arr['download_torrent'] ? 'checked' : '');?> \>
			<small>Возможность скачивать торрент файлы</small>
		</td><td>
	   </td></tr>
		<tr>
		<td class="ta_r" valign="top">
		 <span class="grey" >Скачивать через magnet:</span>
		</td>
		<td style="padding: 0px;">
			<input type="checkbox" name="download_magnet" value="1" <?=($arr['download_magnet'] ? 'checked' : '');?> \>
			<small>Возможность скачивать релизы через magnet</small>
		</td><td>
	   </td></tr>


		<tr>
		<td class="ta_r" valign="top" colspan="2">
		 <span class="grey" ><b>Утилиты</b></span>
		</td>
		</tr>



		<tr>
		<td class="ta_r" valign="top">
		 <span class="grey" >Массовая рассылка:</span>
		</td>
		<td style="padding: 0px;">
			<input type="checkbox" name="messages" value="1" <?=($arr['messages'] ? 'checked' : '');?> \>
			<small>Возможность организовывать массовую рассылку</small>
		</td><td>
	   </td></tr>

		<tr>
		<td class="ta_r" valign="top">
		 <span class="grey" >Мультитрекерные аккаунты:</span>
		</td>
		<td style="padding: 0px;">
			<input type="checkbox" name="multitracker_accounts" value="1" <?=($arr['multitracker_accounts'] ? 'checked' : '');?> \>
			<small>Возможность просматривать мультрекерные аккаунты</small>
		</td><td>
	   </td></tr>

			<tr>
		<td class="ta_r" valign="top">
		 <span class="grey" >Редактировать аккаунты:</span>
		</td>
		<td style="padding: 0px;">
			<input type="checkbox" name="setting_user" value="1" <?=($arr['setting_user'] ? 'checked' : '');?> \>
			<small>Возможность редактировать чужые аккаунты</small>
		</td><td>
	   </td></tr>

		<tr>
		<td class="ta_r" valign="top">
		 <span class="grey" >IP - утилиты:</span>
		</td>
		<td style="padding: 0px;">
			<input type="checkbox" name="ip_util" value="1" <?=($arr['ip_util'] ? 'checked' : '');?> \>
			<small>Возможность пользоваться IP-утилитами</small>
		</td><td>
	   </td></tr>


	      <tr>
		<td class="ta_r" valign="top">
		 <span class="grey" >Мониторинг поиска:</span>
		</td>
		<td style="padding: 0px;">
			<input type="checkbox" name="search_query" value="1" <?=($arr['search_query'] ? 'checked' : '');?> \>
			<small>Возможность пользоваться мониторингом поиска</small>
		</td><td>
	   </td></tr>

	    <tr>
		<td class="ta_r" valign="top">
		 <span class="grey" >Просмотр сессий:</span>
		</td>
		<td style="padding: 0px;">
			<input type="checkbox" name="sessions_view" value="1" <?=($arr['sessions_view'] ? 'checked' : '');?> \>
			<small>Возможность просматривать сессии пользователей</small>
		</td><td>
	   </td></tr>


	   <tr>
		<td class="ta_r" valign="top">
		 <span class="grey" >Очистка сессий:</span>
		</td>
		<td style="padding: 0px;">
			<input type="checkbox" name="sessions_clear" value="1" <?=($arr['sessions_clear'] ? 'checked' : '');?> \>
			<small>Возможность очищать сессии пользователей</small>
		</td><td>
	   </td></tr>


	     	   <tr>
		<td class="ta_r" valign="top">
		 <span class="grey" >Управление новостями:</span>
		</td>
		<td style="padding: 0px;">
			<input type="checkbox" name="news_add" value="1" <?=($arr['news_add'] ? 'checked' : '');?> \>
			<small>Возможность добавлять, удалять , редактировать новости</small>
		</td><td>
	   </td></tr>

	   <tr>
		<td class="ta_r" valign="top">
		 <span class="grey" >Добавление пользователя:</span>
		</td>
		<td style="padding: 0px;">
			<input type="checkbox" name="user_add" value="1" <?=($arr['user_add'] ? 'checked' : '');?> \>
			<small>Позможность добавить нового пользователя</small>
		</td><td>
	   </td></tr>

	   <tr>
		<td class="ta_r" valign="top">
		 <span class="grey" >Управление FAQ:</span>
		</td>
		<td style="padding: 0px;">
			<input type="checkbox" name="faq_moderate" value="1" <?=($arr['faq_moderate'] ? 'checked' : '');?> \>
			<small>Возможность создавать , удалять , редактировать темы FAQ</small>
		</td><td>
	   </td></tr>


		<tr>
		<td class="ta_r" valign="top" colspan="2">
		 <span class="grey" ><b>Профиль</b></span>
		</td>
		</tr>


	   <tr>
		<td class="ta_r" valign="top">
		 <span class="grey" >Просмотр профилей:</span>
		</td>
		<td style="padding: 0px;">
			<input type="checkbox" name="profile_view" value="1" <?=($arr['profile_view'] ? 'checked' : '');?> \>
			<small>Возможность просматривать профили пользователей</small>
		</td><td>
	   </td></tr>


	   <tr>
		<td class="ta_r" valign="top">
		 <span class="grey" >Просмотр участников:</span>
		</td>
		<td style="padding: 0px;">
			<input type="checkbox" name="users_view" value="1" <?=($arr['users_view'] ? 'checked' : '');?> \>
			<small>Возможность просматривать участников</small>
		</td><td>
	   </td></tr>



	      <tr>
		<td class="ta_r" valign="top">
		 <span class="grey" >Плохой рейтинг:</span>
		</td>
		<td style="padding: 0px;">
			<input type="checkbox" name="bad_rating" value="1" <?=($arr['bad_rating'] ? 'checked' : '');?> \>
			<small>Банить аккаунт, если у пользователя плохой рейтинг</small>
		</td><td>
	   </td></tr>




	   <tr>
		<td class="ta_r" valign="top" colspan="2">
		 <span class="grey" ><b>Блоки</b></span>
		</td>
		</tr>

	   <tr>
		<td class="ta_r" valign="top">
		 <span class="grey" >Модераторские блоки:</span>
		</td>
		<td style="padding: 0px;">
			<input type="checkbox" name="block_moderators" value="1" <?=($arr['block_moderators'] ? 'checked' : '');?> \>
			<small>Видит модераторские блоки</small>
		</td><td>
	   </td></tr>

	   <tr>
		<td class="ta_r" valign="top">
		 <span class="grey" >Административные блоки:</span>
		</td>
		<td style="padding: 0px;">
			<input type="checkbox" name="block_administrators" value="1" <?=($arr['block_administrators'] ? 'checked' : '');?> \>
			<small>Видит административные блоки</small>
		</td><td>
	   </td></tr>

	   	<tr>
		<td class="ta_r">
		 <span class="grey"></span>
		</td>
		<td style="padding: 0px;">
		 <input type="submit" value="<?=($_GET['act'] == 'add' ? 'Добавить' : 'Редактировать');?>">
		</td><td>
	   </td></tr>




	</tbody>
	</table>
	</form>
	<?
	end_frame();
	foot();
	die();
}


/////////////////////////////////////////////////////////////////
//Общий вывод
/////////////////////////////////////////////////////////////////


$sql = $db->query("SELECT p.*  , COUNT(u.class) AS count
				FROM priv  AS p
				LEFT JOIN users AS u ON u.class = p.id
				WHERE p.id > 0
				GROUP BY p.id DESC
				ORDER BY p.id DESC");
if(!$db->num_rows($sql)) {
	err($language['default_1'] , $language['edit_priv_2'] , 1);
}

head('Редактирование классами');

if($_GET['status'] == '1') {
	msg($language['default_9'] , 'Операция успешно выполнена');
}elseif($_GET['status'] == '2') {
	msg($language['default_9']  , 'Класс успешно удален');
}elseif($_GET['status'] == '3') {
	msg($language['default_9']  , 'Пользователи успешно перемещены');
}

begin_frame('Редактирование классами');

echo '<input type="button" value="Добавить класс" onClick="window.location.href=\'edit_priv.php?act=add\'">&nbsp';
echo '<input type="button" value="Перемещение пользователей" onClick="window.location.href=\'edit_priv.php?act=location\'">&nbsp';
echo '<input type="button" value="Права для гостей" onClick="window.location.href=\'edit_priv.php?id=0&act=edit\'">';
echo '<table width="100%" cellpadding="3" class="tt">';
while($arr = $db->get_row($sql) ) {
		echo '<tr>';

		echo '<td width="1%" align="center">';
		echo '<A href="users.php?class='.$arr['id'].'"><img src="public/images/users__arrow.png" border="0" title="Перейти к списку пользователей"></a>';
		echo '</td>';

		echo '<td width="50%">';
		echo '<A href="edit_priv.php?id='.$arr['id'].'&act=edit">'.htmlspecialchars($arr['NAME']).'</a> <div style="float:right"><small>Создана '.convent_date($arr['DATE']).'</small></div>';
		echo '</td>';

		echo '<td width="15%">';
		echo $arr['count'].' пользователей';
		echo '</td>';

		echo '<td align="center">';
		echo '<input type="button" value="Редактировать" onCLick="window.location.href=\'edit_priv.php?id='.$arr['id'].'&act=edit\'">&nbsp';
		echo '<input type="button" value="Удалить" onCLick="window.location.href=\'edit_priv.php?id='.$arr['id'].'&act=delete\'">';
		echo '</td>';
		echo '</tr>';
}
echo '</table>';

end_frame();
foot();
?>
