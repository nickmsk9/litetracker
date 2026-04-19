<?
/*
===================================================================
LiteTracker Source
===================================================================
by Nick
-------------------------------------------------------------------
Назначение: Редактирование категорий
===================================================================
*/

//Подключаем главный системный файл
require 'system/init.php';

//Проверка авторизации
is_login();

//Только Администраторам , Модераторам
if(!$PRIV['cats']) {
	err($language['default_1'] , $language['default_12']  , 1);
}



///////////////////////////////////////////////////////////////////////
//Перемещение релизов
///////////////////////////////////////////////////////////////////////
if($_GET['act'] == 'location') {

	//Обработка
	if($_POST) {

		//Из категории
		$location_1 = (int)$_POST['location_1'];
		if(!$location_1) {
			err($language['default_1'] , $language['cats_1'] , 1);
		}
		//В категорию
		$location_2 = (int)$_POST['location_2'];
		if(!$location_2) {
			err($language['default_1'] ,  $language['cats_2'] , 1);
		}

		//Выполняем перемещение, если категории не равны
		if($location_1 != $location_2) {
			$db->query("UPDATE torrents SET id_category=".$location_2." WHERE id_category=".$location_1);
		}

		header("Location:categories.php?status=4");
		die();
	}


	//Обший вид
	head($language['cats_35']);
	begin_frame($language['cats_35']);

	//Создаем массив с категориями
	$db->query("SELECT * FROM categories");
	$row = array();
	while($get_row = $db->get_row() )
		$row[] = $get_row;



	echo msg($language['cats_3']);

	echo '<form action="categories.php?&act=location" method="POST">';
	echo '<select name="location_1">';
	echo '<option value="0">('.$language['cats_4'].')</option>';
	foreach($row AS $arr) {
		echo '<option value="'.$arr['id'].'">'.htmlspecialchars($arr['name']).'</option>';
	}
	echo '</select>';

	echo '<select name="location_2">';
	echo '<option value="0">('.$language['cats_5'].')</option>';
	foreach($row AS $arr) {
		echo '<option value="'.$arr['id'].'">'.htmlspecialchars($arr['name']).'</option>';
	}
	echo '</select>';
	echo '<input type="submit" value="'.$language['cats_6'].'">&nbsp';
	echo '<input type="button" value="'.$language['default_5'].'" onClick="history.go(-1);">';
	echo '</form>';

	end_frame();
	foot();
	die();

}




///////////////////////////////////////////////////////////////////////
//Редактировать категорию
///////////////////////////////////////////////////////////////////////
if($_GET['act'] == 'edit' && $_GET['id']) {
	$id = (int)$_GET['id'];
	$db->query("SELECT * FROM categories WHERE id=".$id);
	if(!$db->num_rows()) {
		err($language['default_1'] , $language['cats_7'] , 1);
	}
	$arr = $db->get_row();


	//Обработка
	if($_POST) {

		$update = array();

		//Название
		$name = $_POST['name'];
		if(empty($name) ) {
			err($language['default_1'] , $language['cats_8'] , 1);
		}
		if($arr['name'] != $name) {
			$update[] = "name='".$db->safesql($name)."'";
		}


		//Картинка
		$allowed_types = array(
			"image/gif" => "gif",
			"image/pjpeg" => "jpg",
			"image/jpeg" => "jpg",
			"image/jpg" => "jpg",
			"image/png" => "png"
			// Add more types here if you like
		);


		if (!($_FILES["image"]['name'] == "")) {


			//Лимит размера
			$limit_size = 500 * 8 * 1024;

			// Is valid filetype?
			if (!array_key_exists($_FILES['image']['type'], $allowed_types) ) {
				err($language['default_1'], $language['cats_9'] , 1);
			}

			if (!preg_match('/^(.+)\.(jpg|jpeg|png|gif)$/si', $_FILES['image']['name']) ) {
				err($language['default_1'] , $language['cats_10'], 1);
			}

			// Is within allowed filesize?
			if ($_FILES['image']['size'] > $limit_size) {
				err($language['default_1'] , sprintf($language['cats_11'] , mksize($limit_size)) , 1);
			}

			// Where to upload?
			// Update for your own server. Make sure the folder has chmod write permissions. Remember this director
			$uploaddir = "public/images/categories/";

			// What is the temporary file name?
			$ifile = $_FILES['image']['tmp_name'];

			// Calculate what the next torrent id will be
			// $row = $db->super_query("SHOW TABLE STATUS LIKE 'categories'");
			// $next_id = $row['Auto_increment'];

			// By what filename should the tracker associate the image with?
			$ifilename = $id .  substr($_FILES['image']['name'], strlen($_FILES['image']['name'])-4, 4);


			// Upload the file
			$copy = @copy($ifile, $uploaddir.$ifilename);

			if (!$copy) {
				err($language['default_1'] , $language['cats_12'], 1);
			}

			$update[] = "image='".$db->safesql($ifilename)."'";

		}


		//Шаблон
		$template = (int)$_POST['template'];
		if($arr['template'] != $template) {
			$update[] = "template='".$template."'";
		}

		//Обновляем категорию
		if($update) {
			$db->query("UPDATE categories SET ".implode(',' , $update)." WHERE id=".$id);
			$memcache->delete('upload_categories');
		}
		header("Location:categories.php?status=3");
		die();
	}


	//Обший вид
	head($language['cats_13']);
	begin_frame($language['cats_13']);

	?>
	<form enctype="multipart/form-data" action="categories.php?act=edit&id=<?=$id;?>" method="post">

	<!--Файлы-->
	<table width="80%"  cellspacing="7" cellpadding="0" border="0"  align="center">
	<tbody>


	<tr>
		<td class="ta_r">
		 <span class="grey"><?=$language['cats_14'];?>:</span>
		</td>
		<td style="padding: 0px;">
		 <input type="text" name="name" style="margin: 0px;" size="25" class="inputText" value="<?=htmlspecialchars($arr['name']);?>">
		</td><td>
	   </td></tr>
		<tr>
		<td class="ta_r" valign="top">
		 <span class="grey" ><?=$language['cats_15'];?>:</span>
		</td>
		<td style="padding: 0px;">
			<input type="file" name="image">
			<br><small><?=$language['cats_16'];?></small>
		</td><td>
	   </td></tr>
	   <tr>
		<td class="ta_r" valign="top">
		 <span class="grey" ></span>
		</td>
		<td style="padding: 0px;">
			<select name="template">
				<option  <?=($arr['template'] == '' ? 'selected' : '');?> value="">(<?=$language['cats_17'];?>)</option>
				<option  <?=($arr['template'] == '1' ? 'selected' : '');?> value="1"><?=$language['cats_18'];?></option>
				<option  <?=($arr['template'] == '2' ? 'selected' : '');?> value="2"><?=$language['cats_19'];?></option>
				<option  <?=($arr['template'] == '3' ? 'selected' : '');?> value="3"><?=$language['cats_20'];?></option>
				<option  <?=($arr['template'] == '4' ? 'selected' : '');?> value="4"><?=$language['cats_21'];?></option>
				<option  <?=($arr['template'] == '5' ? 'selected' : '');?> value="5"><?=$language['cats_22'];?></option>
				<option  <?=($arr['template'] == '6' ? 'selected' : '');?> value="6"><?=$language['cats_23'];?></option>
			</select>
		</td><td>
	   </td></tr>

	   	<tr>
		<td class="ta_r">
		 <span class="grey"></span>
		</td>
		<td style="padding: 0px;">
		 <input type="submit" value="<?=$language['cats_24'];?>">
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



///////////////////////////////////////////////////////////////////////
//Удаление категории
///////////////////////////////////////////////////////////////////////
if($_GET['act'] == 'del' && $_GET['id']) {
	$id = (int)$_GET['id'];
	$db->query("SELECT * FROM categories WHERE id=".$id);
	if(!$db->num_rows()) {
		err($language['default_1'], $language['cats_7'] , 1);
	}


	//Удаление категории
	if($_POST) {
		//Перемещение торрентов
		$location = (int)$_POST['location'];

		//Перемещаем торренты
		if($location > 0) {
			$db->query("SELECT *  FROM categories WHERE id=".$location."");
			if(!$db->num_rows() ) {
				err($language['default_1'] , $language['cats_25'] , 1);
			}

			//Получаем весь список торрентов
			$db->query("UPDATE torrents SET id_category='".$location."' WHERE id_category=".$id);
		} else {
			//Удаление всех релизов + удаление всех комментарий
			$db->query("DELETE FROM torrents
						WHERE id_category=".$id."
						");
		}

		$db->query("DELETE FROM categories WHERE id=".$id."");
		//Удаление картинки
		//...
		header("Location:categories.php?status=2");
		die();
	}

	//Выводим предупреждение
	head($language['cats_26']);
	begin_frame($language['cats_26']);
	echo $language['cats_27'].'<br> ';

	echo '<form action="categories.php?id='.$id.'&act=del" method="POST">';
	echo '<select name="location">';
	echo '<option value="0">'.$language['cats_28'].'</option>';

	$db->query("SELECT * FROM categories");
	while($arr = $db->get_row() ) {
		echo '<option value="'.$arr['id'].'">'.sprintf($language['cats_29'] , htmlspecialchars($arr['name'])).'</option>';
	}
	echo '</select>';
	echo '<input type="submit" value="'.$language['cats_30'].'">&nbsp';
	echo '<input type="button" value="'.$language['default_5'].'" onClick="history.go(-1);">';
	echo '</form>';


	end_frame();
	foot();
	die();
}

///////////////////////////////////////////////////////////////////////
//Добавить категорию
///////////////////////////////////////////////////////////////////////
if($_GET['act'] == 'add') {

	//Обработка
	if($_POST) {

		//Название
		$name = $_POST['name'];
		if(empty($name) ) {
			err($language['default_1'] , $language['default_31'] , 1);
		}


		//Картинка
		$allowed_types = array(
			"image/gif" => "gif",
			"image/pjpeg" => "jpg",
			"image/jpeg" => "jpg",
			"image/jpg" => "jpg",
			"image/png" => "png"
			// Add more types here if you like
		);


		if (!($_FILES["image"]['name'] == "")) {


			//Лимит размера
			$limit_size = 500 * 8;

			// Is valid filetype?
			if (!array_key_exists($_FILES['image']['type'], $allowed_types) ) {
				err($language['default_1'] , $language['cats_9'], 1);
			}

			if (!preg_match('/^(.+)\.(jpg|jpeg|png|gif)$/si', $_FILES['image']['name']) ) {
				err($language['default_1'] , $language['cats_10'] , 1);
			}

			// Is within allowed filesize?
			if ($_FILES['image']['size'] > $limit_size) {
				err($language['default_1'] , sprintf($language['cats_11'] , mksize($limit_size)) , 1);
			}

			// Where to upload?
			// Update for your own server. Make sure the folder has chmod write permissions. Remember this director
			$uploaddir = "public/images/categories/";

			// What is the temporary file name?
			$ifile = $_FILES['image']['tmp_name'];

			// Calculate what the next torrent id will be
			$row = $db->super_query("SHOW TABLE STATUS LIKE 'categories'");
			$next_id = $row['Auto_increment'];

			// By what filename should the tracker associate the image with?
			$ifilename = $next_id .  substr($_FILES['image']['name'], strlen($_FILES['image']['name'])-4, 4);


			// Upload the file
			$copy = @copy($ifile, $uploaddir.$ifilename);

			if (!$copy) {
				err($language['default_1'] , $language['cats_12'] , 1);
			}

		} else {
			err($language['default_1'] , $language['cats_32'] , 1);
		}


		//Шаблон
		$template = (int)$_POST['template'];


		//Добавляем категорию
		$db->query("INSERT INTO categories(name  , image , template , date) VALUES ('".$db->safesql($name)."' , '".$db->safesql($ifilename)."' , '".$db->safesql($template)."'   , NOW() ) ");
		$memcache->delete('upload_categories');
		header("Location:categories.php?status=1");
	}
	head($language['cats_33']);
	begin_frame($language['cats_33']);

	?>
	<form enctype="multipart/form-data" action="categories.php?act=add" method="post">

	<!--Файлы-->
	<table width="80%"  cellspacing="7" cellpadding="0" border="0"  align="center">
	<tbody>


	<tr>
		<td class="ta_r">
		 <span class="grey"><?=$language['cats_14'];?>:</span>
		</td>
		<td style="padding: 0px;">
		 <input type="text" name="name" style="margin: 0px;" size="25" class="inputText" value="<?=htmlspecialchars($arr['name']);?>">
		</td><td>
	   </td></tr>
		<tr>
		<td class="ta_r" valign="top">
		 <span class="grey" ><?=$language['cats_15'];?>:</span>
		</td>
		<td style="padding: 0px;">
			<input type="file" name="image">
		</td><td>
	   </td></tr>
	   <tr>
		<td class="ta_r" valign="top">
		 <span class="grey" ></span>
		</td>
		<td style="padding: 0px;">
			<select name="template">
				<option  <?=($arr['template'] == '' ? 'selected' : '');?> value="">(<?=$language['cats_17'];?>)</option>
				<option  <?=($arr['template'] == '1' ? 'selected' : '');?> value="1"><?=$language['cats_18'];?></option>
				<option  <?=($arr['template'] == '2' ? 'selected' : '');?> value="2"><?=$language['cats_19'];?></option>
				<option  <?=($arr['template'] == '3' ? 'selected' : '');?> value="3"><?=$language['cats_20'];?></option>
				<option  <?=($arr['template'] == '4' ? 'selected' : '');?> value="4"><?=$language['cats_21'];?></option>
				<option  <?=($arr['template'] == '5' ? 'selected' : '');?> value="5"><?=$language['cats_22'];?></option>
				<option  <?=($arr['template'] == '6' ? 'selected' : '');?> value="6"><?=$language['cats_23'];?></option>
			</select>
		</td><td>
	   </td></tr>

	   	<tr>
		<td class="ta_r">
		 <span class="grey"></span>
		</td>
		<td style="padding: 0px;">
		 <input type="submit" value="<?=$language['cats_33'];?>">
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


///////////////////////////////////////////////////////////////////////
//Общий вид
///////////////////////////////////////////////////////////////////////

$sql =  $db->query("SELECT c.* , (SELECT COUNT(*) FROM torrents WHERE id_category = c.id) AS count
					FROM categories  AS c
					ORDER BY c.date ASC
					");

if(!$db->num_rows($sql) ) {
	err($language['default_1'] , $language['cats_34']);
}

head($language['cats_36']);

if($_GET['status'] == '1') {
	msg($language['default_9'] , $language['cats_37']);
}elseif($_GET['status'] == '2') {
	msg($language['default_9']  , $language['cats_38']);
}elseif($_GET['status'] == '3') {
	msg($language['default_9']  , $language['cats_39']);
}elseif($_GET['status'] == '4') {
	msg($language['default_9']  ,  $language['cats_40']);
}

begin_frame($language['cats_36']);


echo '<input type="button" value="'.$language['cats_41'].'" onClick="window.location.href=\'categories.php?act=add\'">&nbsp';
echo '<input type="button" value="'.$language['cats_35'].'" onClick="window.location.href=\'categories.php?act=location\'">';
echo '<table width="100%" cellpadding="3" class="tt">';
while($arr = $db->get_row($sql) ) {
		echo '<tr>';

		echo '<td width="1%" align="center">';
		echo '<A href="index.php?id_category='.$arr['id'].'"><img src="public/images/categories/'.$arr['image'].'" border="0" width="25px"></a>';
		echo '</td>';

		echo '<td width="50%">';
		echo '<A href="index.php?id_category='.$arr['id'].'">'.htmlspecialchars($arr['name']).'</a> <div style="float:right"><small>'.sprintf($language['cats_42'] ,convent_date($arr['date']) ).'</small></div>';
		echo '</td>';

		echo '<td width="15%">';
		echo sprintf($language['cats_43'] ,$arr['count'] );
		echo '</td>';

		echo '<td align="center">';
		echo '<input type="button" value="'.$language['cats_44'].'" onCLick="window.location.href=\'categories.php?act=edit&id='.$arr['id'].'\'">&nbsp';
		echo '<input type="button" value="'.$language['cats_45'].'" onCLick="window.location.href=\'categories.php?act=del&id='.$arr['id'].'\'">';
		echo '</td>';
		echo '</tr>';
}
echo '</table>';
end_frame();
foot();
