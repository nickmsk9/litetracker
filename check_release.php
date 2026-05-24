<?php
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
require __DIR__ . '/app/system/init.php';

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
if(!is_array($array) || !count($array)) {
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
$action = (string) ($_POST['act'] ?? '');
if($action == 'banned') {

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

		//Скрываем/восстанавливаем без физического удаления
		if(lt_torrent_status_normalize($arr['status'] ?? 'approved') === 'hidden' || $arr['banned']) {
			lt_torrent_set_status($id, 'approved', (int) $USER['id'], '');
			$banned['unbanned']++;
		}else {
			lt_torrent_set_status($id, 'hidden', (int) $USER['id'], 'Скрыто массовым действием');
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
if($action == 'location') {
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
if($action == 'delete') {
	$i = 0; //Счетчик
	foreach($ids AS $id) {
		//Проверяем существование релиза
		$db->query("SELECT * FROM torrents WHERE id=".$id);
		if(!$db->num_rows() ) {
			continue;
		}
		//Получаем данные
		$arr = $db->get_row();

		//Soft delete: не удаляем torrent-файл, скриншоты, infohash и связанные данные.
		lt_torrent_set_status($id, 'deleted', (int) $USER['id'], 'Мягкое удаление массовым действием');

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
<?php
$categories_array =  categories_array();
$selectedCategoryId = (int) ($_GET['id_category'] ?? 0);
foreach($categories_array AS $thisCat)
	$cats .= '<option value="'.$thisCat['id'].'" '.($selectedCategoryId == (int) $thisCat['id'] ? "selected" : "").'>'.$thisCat['name'].'</option>';
?>
<select name="id_category"   class="search" style="float:left;display:none" id="id_category">
	<option value="" <?=($selectedCategoryId === 0 ? 'selected' : '');?> >(<?=$language['search_4'];?>)</option>
	<?=$cats;?>
</select>

<input type="submit" value="Выполнить действие">
</form>
<?php
end_frame();
foot();
?>
