<?php
/*
===================================================================
LiteTracker Source
===================================================================
by jenaDI
-------------------------------------------------------------------
Назначение: Загрузка торрента (шаг 1)
===================================================================
*/

//Подключаем главный системный файл
require 'system/init.php';
require 'system/functions/functions.benc.php';

//Проверка авторизации
is_login();

if(!$PRIV['upload']) {
	err($language['default_1'] , $language['upload_41'] , 1);
}


//Проверка категории 
if($_GET['act'] == 'next' || $_GET['act'] == 'take') {
	//Категория
	$catid = (int)$_REQUEST['catid'];	
	$db->query("SELECT * FROM categories WHERE id=".$catid);
	if($db->num_rows() == 0) {
		err($language['default_1'] , $language['upload_3'] , 1 );
	}
	
	$arr = $db->get_row();
	$template = $arr['template'];
}

/////////////////////////////////////////////////////////////////////
//Вывод категорий
/////////////////////////////////////////////////////////////////////
if($_GET['act'] == '' || !isset($_GET['act']) ) {
	//Получаем список категорий
	if (false === ($cache_result = $memcache->get('upload_categories')))
	{
		$categories_who = array();
		$cats = $db->query("SELECT * FROM categories") or sqlerr(__FILE__, __LINE__);
		while($arr = $db->get_row() )
			$categories_who[] = $arr;
		
		$memcache->set('upload_categories', $categories_who , 0, (24*60*60));
		$cache_result = $categories_who;
	}


	//Заголовок
	head($language['upload_1'] , true);
	begin_frame($language['upload_1']);
	if($cache_result) {
		$c = 0;
		print "<table border=0 width=400 cellpadding=10 align=center ";
		echo "<tbody>";
		$kolonok = 6;


		foreach ($cache_result AS $cat) {

			//Сортируем колонки. Число колонок содержится в переменной $kolonok
				print(($c && $c % $kolonok == 0) ? "</td><tr>" : "");
				echo "<td align=center class=row1><center><a href=\"upload.php?catid=".$cat['id']."&act=next\"><img src='public/images/categories/".$cat['image']."' border=0/><br><small>".$cat['name']."</small></a></center></td>";
				$c++;
		}

		echo "</tbody></table>";
	} else {
		msg($language['default_1'] , $language['upload_2']);
	}
	end_frame();
	//Подвал
	foot(true);
	die();
} elseif($_GET['act'] == 'next') {
/////////////////////////////////////////////////////////////////////
//Вывод формы
/////////////////////////////////////////////////////////////////////
	//Заголовок
	head('Загрузка' , true);

	msg('Все поля которые выделены чертой , обязательны к заполнению' , 'Релиз будет активный если у него есть торрент-файл, и обложка!');
	begin_frame('Загрузка');
	?>
	

	<!--ТЕГИ-->
	<script type="text/javascript" src="public/js/tagto.js"> </script>
	<script type="text/javascript">
		
		$(document).ready(function(){
			$("#from").tagTo("#tags");
		});	
		
	
	</script>


	<form enctype="multipart/form-data" action="upload.php?act=take" method="post" name="upload">
	<input type="hidden" name="catid" value="<?=$catid;?>" />
	<!--Файлы-->
	<table width="95%"  cellspacing="7" cellpadding="0" border="0"  align="center">
	<tbody>


	<tr>
		<td class="ta_r">
		 <span class="grey"><b><?=$language['upload_4'];?>:</b></span>
		</td>
		<td style="padding: 0px;">
		 <input type="file" name="file" style="margin: 0px;" size="50%" class="inputText"><br>
		</td><td>
	   </td></tr>

	<tr>
		<td class="ta_r">
		 <span class="grey"><b><?=$language['upload_5'];?>:</b></span>
		</td>
		<td style="padding: 0px;">
		 <input type="file" name="image" style="margin: 0px;" size="50%" class="inputText">
		 <br><small><?=sprintf($language['upload_6'] , mksize($config['max_size_image']));?></small>
		</td><td>
	   </td></tr>


	<tr>
		<td class="ta_r" valign="top">
		 <span class="grey" ><b><?=$language['upload_7'];?>:</b></span>
		</td>
		<td style="padding: 0px;">
		 <input type="file" name="screenshot[]" size="50%"> <br>
			<input type="file" name="screenshot[]" size="50%"> <br>
			<input type="file" name="screenshot[]" size="50%"> <br>
			<input type="file" name="screenshot[]" size="50%"> <br>
		</td><td>
	   </td></tr>
	  
	

	
	<!--Название -->

	<tr>
		<td class="ta_r">
		 <span class="grey"><b><?=$language['upload_9'];?>:</b></span>
		</td>
		<td style="padding: 0px;">
		 <input type="text" style="margin: 0px;" size="50%"  name="name" class="inputText">
		
		</td><td>
	   </td></tr>
	   

	<!--Описание-->

	<tr>
		<td colspan="2"><?=textbb('descr' , ($_POST['descr'] ? $_POST['descr']  : upload_text_category($template) ) ,  '95%' , '300');?></td>
	</tr>

	<tr>
		<td class="ta_r">
		 <span class="grey"><?=$language['upload_12'];?>:</span>
		</td>
		<td style="padding: 0px;">
		<input type="text" name="tags" id="tags" size="70"> 
		<?
		///////////////////////////////////////////////////////////
		//Теги
		///////////////////////////////////////////////////////////
		
		$tags = taggenrelist($catid);
		
		$tags_echo .= '<div id="from">';
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
			<input type="checkbox" name="multi" value="1"/>&nbsp<?=$language['upload_15'];?>
		</td><td>
	   </td></tr>
	   
	  <tr> 
		  <td class="ta_r">
		 <span class="grey"><?=$language['upload_16'];?>:</span>
		</td>
		<td style="padding: 0px;">
			<input type="text" name="video_vkontakte" size="70"/>
			<br> <small><br><?=$language['upload_17'];?></small>
		</td><td>
	   </td></tr> 

	 <?
	 if($PRIV['edit_news']) { ?>
	   <tr> 
		  <td class="ta_r">
		 <span class="grey"><?=$language['upload_39'];?>:</span>
		</td>
		<td style="padding: 0px;">
			<input type="checkbox" value="1" name="news">&nbsp<?=$language['upload_40'];?>
			
		</td><td>
	   </td></tr> 
	 
	 <? } ?>

	</tbody>
	</table>

	<!--Кнопка-->
	<table width="70%" cellpadding="3" align="center">
	<tr>
		<td width="15%"></td>
		<td><input type="submit" value="<?=$language['upload_18'];?>"> </td>
	</tr>
	</table>
	</form>
	<?
	end_frame();
	//Подвал
	foot(true);
	die();

} elseif ($_GET['act'] == 'take') {
/////////////////////////////////////////////////////////////////////
//Обработка
/////////////////////////////////////////////////////////////////////
	
	/*
	===================================
	Торрент
	===================================
	*/

	//Файл
	$f = $_FILES["file"];
	
	//Если есть торрент проверяем его
	if(!($_FILES['file']['name'] == ""))  {
		//Имя файла
		$fname = trim($f["name"]);
		if (empty($fname) ) {
			err($language['default_1'] , $language['upload_19'] , 1); 
		}	

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

		$multi =  ($_POST['multi'] == 1 ? 1 : 0);


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
			if (!isset($flist))
				err("missing both length and files");
			if (!count($flist))
				err("no files");
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

	}
	
	
	/*
	===================================
	Содержимое(описание)
	===================================
	*/
	//Имя 
	$name = trim($_POST['name']);
	if(empty($name) ) {
		err($language['default_1']  , $language['upload_25'] , 1);
	}

	//Описание 
	$descr = $_POST['descr'];

	if(empty($descr) ) {
		err($language['default_1']  , $language['upload_26'] , 1);
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


	if (!($_FILES['image']['name'] == "")) {
		
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

			// Calculate what the next torrent id will be
			$row = $db->super_query("SHOW TABLE STATUS LIKE 'torrents'");
			$next_id = $row['Auto_increment'];

			// By what filename should the tracker associate the image with?
			$ifilename = $next_id .  substr($_FILES['image']['name'], strlen($_FILES['image']['name'])-4, 4);

			// Upload the file
			$copy = copy($ifile, $uploaddir.$ifilename);

			if (!$copy) {
				err($language['default_1'] , $language['upload_30'] , 1);
			}
		
			$image = $ifilename;

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
			$ifilename_screen = $next_id . $x . substr($_FILES['screenshot']['name'][$x], strlen($_FILES['screenshot']['name'][$x])-4, 4);

			// Upload the file
			$copy_screen = copy($ifile, $uploaddir_screen.$ifilename_screen);

			if (!$copy_screen) {
					err($language['default_1'] , sprintf($language['upload_35'] , $y) , 1);
			}
			$screenshot[] = $ifilename_screen;
		}

	}

	//Теги 
	$tags = trim($_POST['tags']);
	// $tags = str_replace($replace, ",", $_POST["tags"], MB_CASE_TITLE, $config['mysql']['charset'])));


	//Видео Вконтакте
	$video_vkontakte = trim($_POST['video_vkontakte']);
	if(!preg_match("#http\:\\/\\/vkontakte\.ru\\/video_ext\.php\?oid=(\d+)|-(\d+)\&id=(\d+)\&hash=(.*?)\&hd=1#i" , $video_vkontakte) && !empty($video_vkontakte) ) {
		err($language['default_1'] , $language['upload_36'] , 1);
	}

	//Новинка месяца
	if($PRIV['edit_news']) {
		$news = ($_POST['news'] == '1' ? '1' : '0');
	} else {
		$news = '0';
	}

	/*
	===================================
	Добавление в базу
	===================================
	*/
	
	
	
	$add = $db->query("INSERT INTO torrents  (name , filename , num_files , type ,  size , descr , infohash , tags , id_category , id_user , added , image , multi  , screen_1 , screen_2 , screen_3 , screen_4 , video_vkontakte , news) VALUES ('".$db->safesql($name)."' , '".$db->safesql($fname)."' ,  ".count($filelist).", '".$type."' ,  '".$totallen."' ,  '".$db->safesql($descr)."' , '".$db->safesql($infohash)."' , '".$db->safesql($tags)."' ,  '".$catid."' , '".$USER['id']."' ,  NOW() ,  '".$db->safesql($image)."' , '".$multi."' , '".$db->safesql($screenshot['0'])."' ,   '".$db->safesql($screenshot['1'])."' ,  '".$db->safesql($screenshot['2'])."' ,  '".$db->safesql($screenshot['3'])."' , '".$db->safesql($video_vkontakte)."' , '".$news."')" , 0);
	if(!$add) {
		err($language['default_1'] , $language['upload_37'] , 1);
	}

	//ID торрента
	$id = $db->insert_id();
	
	//Создаем информацию о торренте
	$db->query("DELETE FROM files WHERE id_torrent=".$id); //Удаляем старые данные
	foreach ($filelist as $file) {
		$db->query("INSERT INTO files (id_torrent, filename, size) VALUES (".$id.", '".$db->safesql($file[0])."', '".$file[1]."')");
	}
	
	
	//Добавляем локальные трекеры
	$db->query("INSERT INTO trackers (torrent,tracker) VALUES ('".$id."','localhost')");

	//Добавляем мультитрекеры
	if ($anarray) {
			foreach ($anarray as $anurl) $db->query("INSERT INTO trackers (torrent,tracker) VALUES ('".$id."','".$db->safesql($anurl)."')");
	}

	//Добавляем теги
	$ret = array();
	$res = $db->query("SELECT name FROM tags WHERE category = ".$catid);
	while ($row = $db->get_row() ) {
		$ret[] = $row["name"];
	}

	$union = array_intersect($ret, explode(",", $tags));
	$ununion = array_diff(explode(",", $tags), $ret);

	foreach ($union as $tag) {
		$db->query("UPDATE tags SET howmuch=howmuch+1 WHERE name LIKE '".$db->safesql($tag)."'");
	}

	foreach ($ununion as $tag) {
		$db->query("INSERT INTO tags (category, name, howmuch) VALUES ('".$catid."', '".$db->safesql($tag)."', 1)");
	}

	//Добавляем дополнительные поля
	/*if(sizeof($insert_fields) ) {
		$db->query("UPDATE torrents SET ".implode("," , $insert_fields)." WHERE id=".$id);
	}*/

	//Загружаем торрент	- файл
	move_uploaded_file($tmpname, 'public/downloads/torrents/'.$id.'.torrent');


	//Удаление старого кеша
	$memcache->delete('upload_categories');
	$memcache->delete('news_releases');


	header("Location:/details.php?id=".$id);
	die();
}
?>
