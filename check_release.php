<?
/*
===================================================================
LiteTracker Source
===================================================================
by Nick
-------------------------------------------------------------------
Назначение: Действия с отмеченными релизами
===================================================================
*/

//Подключаем главный системный файл
require 'system/init.php';

//Проверка авторизации
is_login();

//Только Администраторам , Модераторам
if(!$PRIV['edit_release']) {
	err($language['default_1'] , $language['default_12']  , 1);
}

if ($_POST && !lt_csrf_validate('check_release')) {
	err($language['default_1'] , 'Защитный токен устарел. Обновите страницу и попробуйте снова.' , 1);
}

//Массив с данными
$array = (isset($_POST['check']) && is_array($_POST['check']) ? $_POST['check'] : array());
if(!count($array) || !is_array($array)) {
	err($language['default_1'] , 'Вы ничего не пометили' , 1);
}

//Обрабатываем данные
$ids = array();
foreach($array AS $id) {
	$ids[] = (int)$id;
}
$ids = array_values(array_unique(array_filter($ids)));



//////////////////////////////////////////////////////////////////////////////
//Блокировка/Разблокировка релизов
//////////////////////////////////////////////////////////////////////////////
if($_POST['act'] == 'banned') {

	$i = 0; //Счетчик
	$banned = array();
	$banned['banned'] = 0;
	$banned['unbanned'] = 0;

	foreach($ids AS $id) {
		//Проверяем существование релиза
		$db->query("SELECT * FROM torrents WHERE id=".$id);
		if(!$db->num_rows() ) {
			continue;
		}
		//Получаем данные
		$arr = $db->get_row();

		//Блокируем/Разблокируем
		if($arr['banned']) {
			$db->query("UPDATE torrents SET banned=0 WHERE id=".$id);
			$banned['unbanned']++;
		}else {
			$db->query("UPDATE torrents SET banned=1 WHERE id=".$id);
			$banned['banned']++;
		}

		$i++;
	}

	head('Блокировка релизов');
	begin_frame('Блокировка релизов');
	msg( $i.' операций было выполнено . Из них '.$banned['unbanned'].' разблокировано и '.$banned['banned'].' забанено ' , '<a href="javascript:window.location.href=\'index.php?search=\'">Вернуться к списку</a>');
	end_frame();
	foot();
	die();
}

//////////////////////////////////////////////////////////////////////////////
//Перемещение релизов
//////////////////////////////////////////////////////////////////////////////
if($_POST['act'] == 'location') {
	//Проверяем категорию
	$id_category = (int)$_POST['id_category'];
	$db->query("SELECT * FROM categories WHERE id=".$id_category);
	if(!$db->get_row() ) {
		err($language['default_1'] , 'Данной категории не сущесвует' , 1);
	}

	$i = 0; //Счетчик
	foreach($ids AS $id) {
		//Проверяем существование релиза
		$db->query("SELECT * FROM torrents WHERE id=".$id);
		if(!$db->num_rows() ) {
			continue;
		}
		//Получаем данные
		$arr = $db->get_row();

		//Учитываем перемещение только в другую категорию
		if($arr['id_category'] == $id_category) {
			continue;
		}

		//Перемещаем релиз
		$db->query("UPDATE torrents SET id_category=".$id_category." WHERE id=".$id);

		$i++;
	}

	head('Перемещение релизов');
	begin_frame('Перемещение релизов');
	msg( $i.' из '.count($ids).' были успешно перемещены' , '<a href="javascript:window.location.href=\'index.php?search=\'">Вернуться к списку</a>');
	end_frame();
	foot();
	die();
}


//////////////////////////////////////////////////////////////////////////////
//Удаление релизов
//////////////////////////////////////////////////////////////////////////////
if($_POST['act'] == 'delete') {
	$i = 0; //Счетчик
	foreach($ids AS $id) {
		//Проверяем существование релиза
		$db->query("SELECT * FROM torrents WHERE id=".$id);
		if(!$db->num_rows() ) {
			continue;
		}
		//Получаем данные
		$arr = $db->get_row();

		//Удаляем торрент - файл
		@unlink('public/downloads/torrents/'.$id.'.torrent');

		//Удаляем картинку
		if($arr['image']) {
				@unlink('public/downloads/images/'.$arr['image']);
		}

		//Удаляем скринщоты
		for($z = 1 ; $z <= 4 ; $z++) {
			if(!empty($arr['screen_'.$z])) {
				@unlink('public/downloads/images/'.$arr['screen_'.$z]);
			}
		}

		//Удаление из базы всех данных
		$db->query("DELETE FROM torrents WHERE id=".$id);
		$db->query("DELETE FROM trackers WHERE torrent=".$id);
		$db->query("DELETE FROM peers WHERE torrent=".$id);
		$db->query("DELETE FROM snatched WHERE torrent=".$id);

		$i++;
	}

	head('Удаление релизов');
	begin_frame('Удаление релизов');
	msg( $i.' из '.count($ids).' были успешно удалены' , '<a href="javascript:window.location.href=\'index.php?search=\'">Вернуться к списку</a>');
	end_frame();
	foot();
	die();
}


head('С отмеченными');
msg('Вами было отмечено '.count($array).' релизов' , '<a href="javascript:history.go(-1);">Вернуться назад</a>');
begin_frame('С отмеченными');
?>
<script>
function check_value() {
	var cat = $('select[name=act] option:selected').val(); //Категория
	if(cat == 'location') {
		$('#id_category').show('slow');
	} else {
		$('#id_category').hide('slow');
	}
}
</script>


<form action="check_release.php" method="post">
<?=lt_csrf_input('check_release');?>
<?php foreach ($ids as $selectedId) { ?>
<input type="hidden" name="check[]" value="<?=$selectedId;?>">
<?php } ?>
<select name="act" onChange="check_value();">
<option value="delete">Удалить отмеченные релизы</option>
<option value="location">Перенести в другую категорию</option>
<option value="banned">Забанить/Разбанить отмеченные релизы</option>
</select>
<!--Категории-->
<?
$categories_array =  categories_array();
foreach($categories_array AS $thisCat)
	$cats .= '<option value="'.$thisCat['id'].'" '.($_GET['id_category'] == $thisCat['id'] ? "selected" : "").'>'.$thisCat['name'].'</option>';
?>
<select name="id_category"   class="search" style="float:left;display:none" id="id_category">
	<option value="" <?=($_GET['id_category'] == '' ? 'selected' : '');?> >(<?=$language['search_4'];?>)</option>
	<?=$cats;?>
</select>

<input type="submit" value="Выполнить действие">
</form>
<?
end_frame();
foot();
?>
