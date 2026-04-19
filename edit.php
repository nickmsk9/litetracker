<?php
/*
===================================================================
LiteTracker Source
===================================================================
by Nick
-------------------------------------------------------------------
Назначение: Редактирование релиза
===================================================================
*/


//Подключаем главный системный файл
require 'system/init.php';
require 'system/functions/functions.benc.php';

//Проверка авторизации
is_login();
$act = isset($_GET['act']) ? (string)$_GET['act'] : '';
$screen = isset($_GET['screen']) ? (int)$_GET['screen'] : 0;
$cats = '';
$tags_echo = '';
$delete = array();


//Информация о торренте
$id = (int)$_GET['id'];
$sql = $db->query("SELECT * FROM torrents WHERE id=".$id);
if(!$db->num_rows($sql) ) {
	err($language['default_1'] , $language['edit_1'] , 1);
}
$arr = $db->get_row($sql);


//Проверяем права
if($arr['id_user'] != $USER['id'] && !$PRIV['edit_release']) {
	err($language['default_1'] , $language['edit_2'] , 1);
}



//////////////////////////////////////////////////////////////////////////
//Удаление обложки
//////////////////////////////////////////////////////////////////////////
if($act == 'delete_image') {
	if($arr['image']) {
		$db->query("UPDATE torrents SET image='' WHERE id=".$id);
		@unlink('public/downloads/images/'.$arr['image']);
    }

	header('Location:details.php?id='.$id.'&edit=1');
	die();
}

//////////////////////////////////////////////////////////////////////////
//Удаление скриншота
//////////////////////////////////////////////////////////////////////////
if($act == 'delete_screen') {
	if($screen > 4 || $screen < 1) {
		err($language['default_1'] , $language['default_6']);
	}
	if($arr['screen_'.$screen]) {
		$db->query("UPDATE torrents SET screen_".$screen."='' WHERE id=".$id);
		@unlink('public/downloads/screens/'.$arr['screen_'.$screen]);
    }

	header('Location:details.php?id='.$id.'&edit=1');
	die();
}


//////////////////////////////////////////////////////////////////////////
//Обработка редактирования
//////////////////////////////////////////////////////////////////////////
if($act == 'take') {
	$update = array();


	/*
	===================================
	Торрент
	===================================
	*/

	//Мульти
	$multi =  ($_POST['multi'] == 1 ? 1 : 0);
	if($arr['multi'] != $multi) {
		$update[] = 'multi="'.$multi.'"';
	}

	//Файл
	$f = $_FILES["file"];
	//Имя файла
	$fname = trim($f["name"]);
	if (!empty($fname)) {
		//Проверяем имя торрент-файла
		if (!validfilename($fname) ) {
			err($language['default_1'] , $language['upload_20'] , 1);
		}

		//Проверяем формат
		if (!preg_match('/^(.+)\.torrent$/si', $fname, $matches) ) {
			err($language['default_1'] , $language['upload_21'] , 1);
		}


		//Проверяем, загрузиться ли файл через HTTP POST
		$tmpname = $f["tmp_name"];
		if (!is_uploaded_file($tmpname)) {
			err($language['default_1'] , $language['upload_22'] , 1 );
		}

		//Проверяем размер файла
		if (!filesize($tmpname) ) {
			err($language['default_1'] , $language['upload_23'] , 1);
		}

		//Получаем содержимое файла
		$dict = bdec_file($tmpname, (1024 * 1024) );


		//Удяляем не нужное
		unset($dict['value']['nodes']); // remove cached peers (Bitcomet & Azareus)
		unset($dict['value']['azureus_properties']); // remove azureus properties
		unset($dict['value']['comment']);
		unset($dict['value']['created by']);
		unset($dict['value']['publisher']);
		unset($dict['value']['publisher.windows-1251']);
		unset($dict['value']['publisher-url']);
		unset($dict['value']['publisher-url.windows-1251']);




		if (!$multi) {
			unset($dict['value']['announce-list']);
			unset($dict['value']['announce']);

		} else $anarray = get_announce_urls($dict);

		if($multi && !$anarray) {
			err($language['default_1'] , $language['upload_24'] , 1);
		}

		//Декодируем строку
		$dict = bdec(benc($dict) );
		list($info) = dict_check($dict, "info");

		list($dname, $plen, $pieces) = dict_check($info, "name(string):piece length(integer):pieces(string)");


		if (strlen($pieces) % 20 != 0) {
			err("Invalid pieces");
		}

		$filelist = array();
		$totallen = dict_get($info, "length", "integer");
		if (isset($totallen)) {
			$filelist[] = array($dname, $totallen);
			$type = 'single';
		} else {
			$flist = dict_get($info, "files", "list");
			if (!is_array($flist)) {
				err("missing both length and files");
			}
			if (count($flist) === 0) {
				err("no files");
			}
			$totallen = 0;
			foreach ($flist as $fn) {
				list($ll, $ff) = dict_check($fn, "length(integer):path(list)");
				$totallen += $ll;
				$ffa = array();
				foreach ($ff as $ffe) {
					if ($ffe["type"] != "string")
					err("filename error");
					$ffa[] = $ffe["value"];
				}
				if (!count($ffa))
				err("filename error");
				$ffe = implode("/", $ffa);
				$filelist[] = array($ffe, $ll);

				if ($ffe == 'Thumbs.db'){
					err($language['default_1'], $language['upload_44'] , 1);
				}

			}
			$type = 'multi';
		}


		//Инфохеш
		$infohash = sha1($info["string"]);

		//Обновление
		$update[] = 'infohash="'.$db->safesql($infohash).'"';
		$update[] = 'filename = "'.$db->safesql($fname).'"';
		$update[] = 'size = "'.$totallen.'"';
		$update[] = 'multi = "'.$multi.'"';
		$update[] = 'num_files = "'.count($filelist).'"';
		$update[] = 'type = "'.$type.'"';



	}

	/*
	===================================
	Категория
	===================================
	*/
	$category = (int)$_POST['category'];
	if($arr['id_category'] != $category) {
		$db->query("SELECT * FROM categories WHERE id=".$category);
		if($db->num_rows() == 0) {
			err($language['default_1'] , $language['upload_3'] , 1 );
		}
		$update[] = 'id_category="'.$category.'"';
	}


	/*
	===================================
	Описание
	===================================
	*/
	//Имя
	$name = trim($_POST['name']);
	if($arr['name'] != $name) {
		if(empty($name) ) {
			err($language['default_1']  , $language['upload_25'] , 1);
		}
		$update[] = 'name="'.$db->safesql($name).'"';
	}

	//Описание
	$descr = $_POST['descr'];

	// die(print_r($_REQUEST));


	if($arr['descr'] != $descr) {
		if(empty($descr) ) {
			err($language['default_1']  , $language['upload_26'] , 1);
		}
		$update[] = 'descr="'.$db->safesql($descr).'"';
	}




	/*
	===================================
	Обложка
	===================================
	*/


	$allowed_types = array(
		"image/gif" => "gif",
		"image/pjpeg" => "jpg",
		"image/jpeg" => "jpg",
		"image/jpg" => "jpg",
		"image/png" => "png"
	);


	if (!empty($_FILES['image']['name'])) {

			//Проверяем тип обложки
			if (!array_key_exists($_FILES['image']['type'], $allowed_types) ) {
				err($language['default_1'] , $language['upload_27'] , 1);
			}

			if (!preg_match('/^(.+)\.(jpg|jpeg|png|gif)$/si', $_FILES['image']['name']) ) {
				err($language['default_1'] , $language['upload_28'] , 1);
			}
			// Is within allowed filesize?
			if ($_FILES['image']['size'] > $config['max_size_image']) {
				err($language['default_1']  , sprintf($language['upload_29'] , mksize($config['max_size_image'])) , 1);
			}
			// Where to upload?
			// Update for your own server. Make sure the folder has chmod write permissions. Remember this director
			$uploaddir = "public/downloads/images/";

			// What is the temporary file name?
			$ifile = $_FILES['image']['tmp_name'];



			// By what filename should the tracker associate the image with?
			$ifilename = $id .  substr($_FILES['image']['name'], strlen($_FILES['image']['name'])-4, 4);

			// Upload the file
			@unlink($uploaddir.$arr['image']);
			$copy = copy($ifile, $uploaddir.$ifilename);

			if (!$copy) {
				err($language['default_1'] , $language['upload_30'] , 1);
			}

			$image = $ifilename;

			$update[] = 'image="'.$db->safesql($image).'"';

	}


	/*
	===================================
	Скриншоты
	===================================
	*/
	$screenshot = array();
	for ($x=0; $x < 4; $x++) {

		if (!($_FILES['screenshot']['name'][$x] == "")) {
			$y = $x + 1;

			// Is valid filetype?
			if (!array_key_exists($_FILES['screenshot']['type'][$x], $allowed_types)) {
				err($language['default_1'] , sprintf($language['upload_32'] , $y) , 1);
			}
			if (!preg_match('/^(.+)\.(jpg|jpeg|png|gif)$/si', $_FILES['screenshot']['name'][$x])) {
				err($language['default_1'] , sprintf($language['upload_33'] , $y) , 1);

			}
			// Is within allowed filesize?
			if ($_FILES['screenshot']['size'][$x] > $config['max_size_image']) {
				err($language['default_1'] , sprintf($language['upload_34'] , $y) , 1);

			}
			// Where to upload?
			// Update for your own server. Make sure the folder has chmod write permissions. Remember this director
			$uploaddir_screen = "public/downloads/screens/";

			// What is the temporary file name?
			$ifile = $_FILES['screenshot']['tmp_name'][$x];


			// By what filename should the tracker associate the image with?
			$ifilename_screen = $id . $x . substr($_FILES['screenshot']['name'][$x], strlen($_FILES['screenshot']['name'][$x])-4, 4);

			// Upload the file
			@unlink($uploaddir_screen.$arr['screen_'.$y]);
			$copy_screen = copy($ifile, $uploaddir_screen.$ifilename_screen);

			if (!$copy_screen) {
					err($language['default_1'] , sprintf($language['upload_35'] , $y) , 1);
			}
			// $screenshot[] = $ifilename_screen;
			$update[] = 'screen_'.$y.'="'.$db->safesql($ifilename_screen).'"';
		}

	}

	//Теги
	$tags = trim($_POST['tags']);
	if($arr['tags'] != $tags) {
		$update[] = 'tags="'.$db->safesql($tags).'"';
	}

	// $tags = str_replace($replace, ",", $_POST["tags"], MB_CASE_TITLE, $config['mysql']['charset'])));


	//Видео Вконтакте
	$video_vkontakte = trim($_POST['video_vkontakte']);
	if($arr['video_vkontakte'] != $video_vkontakte) {
		if(!preg_match("#http\:\\/\\/vkontakte\.ru\\/video_ext\.php\?oid=(\d+)|-(\d+)\&id=(\d+)\&hash=(.*?)\&hd=1#i" , $video_vkontakte) && !empty($video_vkontakte) ) {
			err($language['default_1'] , $language['upload_36'] , 1);
		}
		$update[] = 'video_vkontakte="'.$db->safesql($video_vkontakte).'"';
	}


	//Новинка месяца
	if($PRIV['edit_news']) {
		$news = ($_POST['news'] == '1' ? '1' : '0');
		$update[] = 'news="'.$news.'"';
	} else {
		$news = '0';
		$update[] = 'news="0"';
	}

	//Забанить релиз
	if($PRIV['edit_banned']) {
		$banned = ($_POST['banned'] == '1' ? '1' : '0');
		$update[] = 'banned="'.$banned.'"';
	}


	/*
	===================================
	Обновление
	===================================
	*/


	//Обновляем торрент
	if($update) {
		$add = $db->query("UPDATE torrents SET ".implode(',' , $update)." WHERE id=".$id , 1);
		if(!$add) {
			err($language['default_1'] , $language['upload_37'] , 1);
		}
	}

	//Создаем информацию о торренте
	if(!empty($fname) ) {
		$db->query("DELETE FROM files WHERE id_torrent=".$id); //Удаляем старые данные
		foreach ($filelist as $file) {
			$db->query("INSERT INTO files (id_torrent, filename, size) VALUES (".$id.", '".$db->safesql($file[0])."', '".$file[1]."')");
		}
	}

	//Загружаем торрент	- файл
	if(!empty($fname) ) {
		move_uploaded_file($tmpname, 'public/downloads/torrents/'.$id.'.torrent');
	}




	//Удаляем старые данные шаблона
	if($arr['id_category'] != $category) {
		foreach($descr_array AS  $this_val => $this_name)  {
			$delete[] = $this_val.'=""';
		}

		$db->query("UPDATE torrents SET ".implode(',' , $delete)." WHERE id=".$id);
	}


	//Добавляем локальные трекеры
	if(!empty($fname) ) {
		//Сначала удаляем старые
		$db->query("DELETE FROM trackers WHERE torrent=".$id);

		//Добавляем новые
		$db->query("INSERT INTO trackers (torrent,tracker) VALUES ('".$id."','localhost')");

		//Добавляем мультитрекеры
		if ($anarray) {
				foreach ($anarray as $anurl) $db->query("INSERT INTO trackers (torrent,tracker) VALUES ('".$id."','".$db->safesql($anurl)."')");
		}
	}
	//Добавляем теги
	if($arr['tags'] != $tags) {
		$ret = array();
		$res = $db->query("SELECT name FROM tags WHERE category = ".$category);
		while ($row = $db->get_row() ) {
			$ret[] = $row["name"];
		}

		$tag_list = array_map('trim', explode(",", $tags));
$tag_list = array_filter($tag_list, 'strlen');

$union = array_intersect($ret, $tag_list);
$ununion = array_diff($tag_list, $ret);

foreach ($union as $tag) {
	$tag = trim($tag);
	if ($tag === '') {
		continue;
	}
	$db->query("UPDATE tags SET howmuch=howmuch+1 WHERE name LIKE '".$db->safesql($tag)."'");
}

foreach ($ununion as $tag) {
	$tag = trim($tag);
	if ($tag === '') {
		continue;
	}
	$db->query("INSERT INTO tags (category, name, howmuch) VALUES ('".$category."', '".$db->safesql($tag)."', 1)");
}
	}


	//Удаление memcached
	$memcached->delete('tags');

	//Переадресация
	header("Location:details.php?id=".$id."&edit=1");
	die();
}

//////////////////////////////////////////////////////////////////////////
//Удаление релиза
//////////////////////////////////////////////////////////////////////////
if($act == 'delete') {
	// err('Приносим свои извинения' , 'Удаление релизов пока выключено !');
	if(isset($_GET['take']) && (int)$_GET['take'] === 1) {
		$db->query("DELETE FROM torrents WHERE id=".$id);
		$db->query("DELETE FROM trackers WHERE torrent=".$id);
		$db->query("DELETE FROM peers WHERE torrent=".$id);
		$db->query("DELETE FROM snatched WHERE torrent=".$id);
		@unlink('public/downloads/images/'.$arr['image']);
		@unlink('public/downloads/torrents/'.$id.'.torrent');
		@unlink('public/downloads/screens/'.$arr['screen_1']);
		@unlink('public/downloads/screens/'.$arr['screen_2']);
		@unlink('public/downloads/screens/'.$arr['screen_3']);
		@unlink('public/downloads/screens/'.$arr['screen_4']);
		header('Location:index.php');
		die();
	}

	head('Удалить релиз');
	msg('Удалить релиз' , 'Вы действительно хотите удалить релиз?');
	echo '<input type="button" value="'.$language['upload_38'].'" onClick="window.location.href=\'edit.php?act=delete&id='.$id.'&take=1\'"> ';
	echo '<input type="button" value="'.$language['default_5'].'" onClick="history.go(-1)">';
	foot();
	die();
}


//Узнаем шаблон
$db->query("SELECT * FROM categories WHERE id=".$arr['id_category']);
if(!$db->num_rows()) {
	err($language['default_1'] , $language['upload_3'] , 1 );
}
$template = $db->get_row();
$template = $template['template'];



head($language['edit_3'] , true);
begin_frame($language['edit_3']);
?>
<!--Граббер-->
<!--<script type="text/javascript" src="public/js/grabber.js"></script>-->
<!--ТЕГИ-->
<script type="text/javascript" src="public/js/tagto.js"> </script>
<script type="text/javascript">
	$(document).ready(function(){
		$("#from").tagTo("#tags");
	});
</script>




<form enctype="multipart/form-data" action="edit.php?act=take&id=<?=$id;?>" method="post" name="upload">
<input type="hidden" value="<?=$id?>" name="id">
<!--Файлы-->
<table width="95%"  cellspacing="7" cellpadding="0" border="0"  align="center">
<tbody>


<tr>
    <td class="ta_r">
     <span class="grey"><?=$language['upload_4'];?>:</span>
    </td>
    <td style="padding: 0px;">
	 <input type="file" name="file" style="margin: 0px;" size="25" class="inputText">
    </td><td>
   </td></tr>

<tr>
    <td class="ta_r">
     <span class="grey"><?=$language['upload_5'];?>:</span>
    </td>
    <td style="padding: 0px;">
	 <input type="file" name="image" style="margin: 0px;" size="25" class="inputText"> <?=($arr['image'] ? '<a href="edit.php?id='.$id.'&act=delete_image"><small>Удалить</small></a>' : '');?>
	 <br><small><?=sprintf($language['upload_6'] , mksize($config['max_size_image']));?></small>
    </td><td>
   </td></tr>


<tr>
    <td class="ta_r" valign="top">
     <span class="grey" ><?=$language['upload_7'];?>:</span>
    </td>
    <td style="padding: 0px;">
	 <input type="file" name="screenshot[]" size="25"> <?=($arr['screen_1'] ? '<a href="edit.php?id='.$id.'&act=delete_screen&screen=1"><small>Удалить</small></a>' : '');?> <br>
		<input type="file" name="screenshot[]" size="25"> <?=($arr['screen_2'] ? '<a href="edit.php?id='.$id.'&act=delete_screen&screen=2"><small>Удалить</small></a>' : '');?> <br>
		<input type="file" name="screenshot[]" size="25"> <?=($arr['screen_3'] ? '<a href="edit.php?id='.$id.'&act=delete_screen&screen=3"><small>Удалить</small></a>' : '');?> <br>
		<input type="file" name="screenshot[]" size="25"> <?=($arr['screen_4'] ? '<a href="edit.php?id='.$id.'&act=delete_screen&screen=4"><small>Удалить</small></a>' : '');?> <br>
    </td><td>
   </td></tr>

<?
$cache_result = categories_array();
foreach ($cache_result AS $cat) {
	$cats .= '<option value="'.$cat['id'].'" '.($arr['id_category'] == $cat['id'] ? 'selected' : '').'>'.htmlspecialchars($cat['name']).'</option>';
}
?>
<tr>
    <td class="ta_r">
     <span class="grey">Категория:</span>
    </td>
    <td style="padding: 0px;">
		<select name="category" >
		<?=$cats;?>
		</select>
    </td><td>
   </td></tr>


<tr>
    <td class="ta_r">
     <span class="grey"><?=$language['upload_9'];?>:</span>
    </td>
    <td style="padding: 0px;">
<input type="text" style="margin: 0px; width: 100%; max-width: 520px;" name="name" class="inputText" value="<?=htmlspecialchars($arr['name']);?>">
    </td><td>
   </td></tr>

<tr>
	<td colspan="2" ><?=textbb('descr' , $arr['descr'],  '95%' , '300');?></td>
</tr>



<tr>
    <td class="ta_r">
     <span class="grey"><?=$language['upload_12'];?>:</span>
    </td>
    <td style="padding: 0px;">
<input type="text" name="tags" id="tags" style="width: 100%; max-width: 520px;" value="<?=htmlspecialchars($arr['tags']);?>">
	<?
	///////////////////////////////////////////////////////////
	//Теги
	///////////////////////////////////////////////////////////

$tags = taggenrelist($arr['id_category']);

$tags_echo = '<div id="from">';
	if (!$tags) {
		$tags_echo .=  '<small>'.$language['upload_13'].'</small>';
	}
	else {
		foreach ($tags as $row)
			$tags_echo .= "<a href='#'>" . htmlspecialchars($row["name"]) . "</a>\n";
	}
	$tags_echo .= "</div>\n";

	msg_ajax($tags_echo , (!$tags ? 'ajaxerror' : 'ajaxsuccess'));
	?>
    </td><td>
   </td></tr>


<tr>
    <td class="ta_r">
     <span class="grey"><?=$language['upload_14'];?>:</span>
    </td>
    <td style="padding: 0px;">
		<input type="checkbox" name="multi" value="1" <?=($arr['multi'] ? 'checked' : '');?>/>&nbsp<?=$language['upload_15'];?>
    </td><td>
   </td></tr>

  <tr>
      <td class="ta_r">
     <span class="grey"><?=$language['upload_16'];?>:</span>
    </td>
    <td style="padding: 0px;">
<input type="text" name="video_vkontakte" value="<?=htmlspecialchars($arr['video_vkontakte']);?>" style="width: 100%; max-width: 520px;"/>		<br> <small><br><?=$language['upload_17'];?></small>
    </td><td>
   </td></tr>


 <?
 if($PRIV['edit_news']) { ?>
   <tr>
      <td class="ta_r">
     <span class="grey"><?=$language['upload_39'];?>:</span>
    </td>
    <td style="padding: 0px;">
		<input type="checkbox" value="1" name="news"  <?=($arr['news'] ? 'checked' : '');?>/>&nbsp<?=$language['upload_40'];?>

    </td><td>
   </td></tr>

 <? } ?>


 <?
 if($PRIV['edit_banned'] && $arr['id_user'] != $USER['id']) { ?>
   <tr>
      <td class="ta_r">
     <span class="grey"><?=$language['upload_42'];?>:</span>
    </td>
    <td style="padding: 0px;">
		<input type="checkbox" value="1" name="banned"  <?=($arr['banned'] ? 'checked' : '');?>/>&nbsp<?=$language['upload_43'];?>

    </td><td>
   </td></tr>

 <? } ?>


<tr>
	<td></td>
	<td><input type="submit" value="<?=$language['details_23'];?>"> <input type="button" value="<?=$language['upload_38'];?>" onClick="window.location.href='edit.php?act=delete&id=<?=$id;?>'"> </td>
</tr>
</table>
</form>
<?
end_frame();
foot(true);
?>
