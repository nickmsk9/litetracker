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

function edit_priv_permission_labels()
{
	return array(
		'upload' => 'загрузка релизов',
		'details_view' => 'просмотр релизов',
		'details_banned_view' => 'видит забаненные релизы',
		'edit_release' => 'редактирование релизов',
		'edit_news' => 'новинка месяца',
		'edit_banned' => 'бан релизов',
		'cats' => 'категории',
		'comments_edit' => 'редактирование комментариев',
		'comments_delete' => 'удаление комментариев',
		'download_torrent' => 'скачивание torrent',
		'download_magnet' => 'скачивание magnet',
		'messages' => 'рассылка',
		'multitracker_accounts' => 'мультитрекер',
		'setting_user' => 'редактирование аккаунтов',
		'ip_util' => 'IP-утилиты',
		'search_query' => 'мониторинг поиска',
		'sessions_view' => 'просмотр сессий',
		'sessions_clear' => 'очистка сессий',
		'news_add' => 'новости',
		'user_add' => 'добавление пользователей',
		'faq_moderate' => 'FAQ',
		'profile_view' => 'просмотр профилей',
		'users_view' => 'список пользователей',
		'EDIT_PRIV' => 'классы и права',
	);
}

function edit_priv_enabled_permissions($row)
{
	$labels = edit_priv_permission_labels();
	$result = array();

	foreach ($labels as $key => $label) {
		if (!empty($row[$key])) {
			$result[] = $label;
		}
	}

	return $result;
}

function edit_priv_default_row()
{
	$row = array(
		'id' => 0,
		'NAME' => '',
		'COLOR' => '',
		'SIGNUP' => 0,
		'DATE' => '',
	);

	foreach (edit_priv_permission_labels() as $key => $label) {
		$row[$key] = 0;
	}

	return $row;
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
			$db->query("SELECT id FROM priv WHERE id=".$location." LIMIT 1");
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
		lt_cache_invalidate_priv();

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
	$id = 0;
	$arr = edit_priv_default_row();

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
			$array = array('faq_moderate'  , 'edit_banned' ,'edit_news' , 'user_add' , 'news_add' ,'upload', 'cats',  'comments_edit', 'comments_delete', 'details_banned_view', 'details_view', 'download_torrent', 'download_magnet', 'edit_release', 'messages', 'multitracker_accounts', 'setting_user', 'ip_util', 'profile_view', 'search_query', 'sessions_view', 'sessions_clear', 'users_view');
		$update = array();

		//Обрабатываем данные

		//Название
		$NAME = trim((string) ($_POST['NAME'] ?? ''));
		if(empty($NAME) ) {
			err($language['default_1']  , 'Введите название класса' , 1);
		}

		if($arr['NAME'] != $NAME)  {
			$update[] = 'NAME="'.$db->safesql($NAME).'"';
		}


		//Цвет класса
		$COLOR = trim((string) ($_POST['COLOR'] ?? ''));
		if(!empty($COLOR) && strlen($COLOR) != 6) {
			err($language['default_1']  , 'Цвет класса может состоять из 6 символов' , 1);
		}

		if($arr['COLOR'] != $COLOR)  {
			$update[] = 'COLOR="'.$db->safesql($COLOR).'"';
		}

		//Класс по умолчанию
		$SIGNUP = (int)($_POST['SIGNUP'] ?? 0);
		if($arr['SIGNUP'] != $SIGNUP) {
			$update[] = 'SIGNUP="'.$db->safesql($SIGNUP).'"';
		}

		//Перебираем в цикле
		foreach($array AS $row) {
			$_POST[$row] = (int)($_POST[$row] ?? 0);
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
		lt_cache_invalidate_priv();

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


$sql = $db->query("SELECT p.*,
					(SELECT COUNT(*) FROM users AS u WHERE u.class = p.id) AS count
				FROM priv AS p
				WHERE p.id > 0
				ORDER BY p.id DESC");
if(!$db->num_rows($sql)) {
	err($language['default_1'] , $language['edit_priv_2'] , 1);
}

head('Редактирование классами');

$notice = '';
if (($_GET['status'] ?? '') == '1') {
	$notice = 'Операция успешно выполнена.';
} elseif (($_GET['status'] ?? '') == '2') {
	$notice = 'Класс успешно удален.';
} elseif (($_GET['status'] ?? '') == '3') {
	$notice = 'Пользователи успешно перемещены.';
}
?>
<div class="lt-admin-page">
	<section class="lt-admin-hero">
		<h1>Классы и права</h1>
		<p class="lt-admin-lead">Здесь задается, что может каждый класс пользователей: загружать релизы, скачивать, модерировать, видеть админские разделы и работать с утилитами.</p>
		<div class="lt-admin-actions">
			<a class="lt-admin-link-button" href="edit_priv.php?act=add">Добавить класс</a>
			<a class="lt-admin-link-button lt-admin-button-secondary" href="edit_priv.php?act=location">Переместить пользователей</a>
			<a class="lt-admin-link-button lt-admin-button-secondary" href="edit_priv.php?id=0&amp;act=edit">Права гостей</a>
			<a class="lt-admin-link-button lt-admin-button-secondary" href="admin.php?tab=users">Назад в админку</a>
		</div>
	</section>

	<?php if ($notice !== '') { ?>
	<div class="lt-admin-notice lt-admin-notice-success"><?=$notice;?></div>
	<?php } ?>

	<section class="lt-admin-panel">
		<h2>Список классов</h2>
		<p class="lt-admin-panel-text">В колонке “Может” показана короткая выжимка прав. Полный набор переключателей открывается по кнопке редактирования.</p>
		<div class="lt-admin-table-wrap">
			<table class="lt-admin-table">
				<thead>
					<tr>
						<th>ID</th>
						<th>Класс</th>
						<th>Пользователи</th>
						<th>Может</th>
						<th>Действия</th>
					</tr>
				</thead>
				<tbody>
					<?php while ($arr = $db->get_row($sql)) { ?>
					<?php
					$enabledPermissions = edit_priv_enabled_permissions($arr);
					$permissionsPreview = ($enabledPermissions ? implode(', ', array_slice($enabledPermissions, 0, 10)) : 'нет включенных прав');
					if (count($enabledPermissions) > 10) {
						$permissionsPreview .= ' и еще '.(count($enabledPermissions) - 10);
					}
					?>
					<tr>
						<td><span class="lt-admin-code">#<?=(int) $arr['id'];?></span></td>
						<td>
							<a href="edit_priv.php?id=<?=(int) $arr['id'];?>&amp;act=edit"><?=htmlspecialchars($arr['NAME'], ENT_QUOTES, 'UTF-8');?></a><br>
							<span class="lt-admin-muted">Создан: <?=(!empty($arr['DATE']) ? convent_date($arr['DATE']) : 'не указано');?></span>
						</td>
						<td><a href="users.php?class=<?=(int) $arr['id'];?>"><?=number_format((int) $arr['count']);?> пользователей</a></td>
						<td><?=htmlspecialchars($permissionsPreview, ENT_QUOTES, 'UTF-8');?></td>
						<td>
							<div class="lt-admin-inline-actions">
								<a href="edit_priv.php?id=<?=(int) $arr['id'];?>&amp;act=edit">Редактировать</a>
								<a href="edit_priv.php?id=<?=(int) $arr['id'];?>&amp;act=delete">Удалить</a>
							</div>
						</td>
					</tr>
					<?php } ?>
				</tbody>
			</table>
		</div>
	</section>
</div>
<?php
foot();
?>
