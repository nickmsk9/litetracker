<?
/*
===================================================================
LiteTracker Source
===================================================================
by jenaDI
-------------------------------------------------------------------
Назначение: Магазин трекера
===================================================================
*/

//Подключаем главный системный файл
require 'system/init.php';


//Проверка авторизации
is_login();


/////////////////////////////////////////////////////////////////////
//Удаление товара
/////////////////////////////////////////////////////////////////////
if($_GET['act'] == 'delete' && $_GET['id']) {
	$id = (int)$_GET['id'];
	$db->query("SELECT * FROM shop WHERE id=".$id);
	if(!$db->num_rows() ) {
		err($language['default_1'] , 'Данного товара не существует' , 1);
	}

	$db->query("DELETE FROM shop WHERE id=".$id);
	header('Location:shop.php?status=3');
	die();
}

/////////////////////////////////////////////////////////////////////
//Добавление / Редактирование товара
/////////////////////////////////////////////////////////////////////
if($_GET['act'] == 'edit') {
	if(!$PRIV['EDIT_PRIV']) {
		err($language['default_1'] , 'Вам запрещено добавлять/регактировать услуги' , 1);
	}
	
	//Данные для редактирования
	if($_GET['id']) {
		$id = (int)$_GET['id'];
		$db->query("SELECT * FROM shop WHERE id=".$id);
		if(!$db->num_rows() ) {
			err($language['default_1'] , 'Данного товара не существует' , 1);
		}
		$arr = $db->get_row();
	}
	
	//Обработка
	if($_POST)  {
		$update = array();
		
		//Название 
		$name = trim($_POST['name']);
		if(empty($name) ) {
			err($language['default_1'] , 'Введите название товара' , 1);
		}
		if($arr['name'] != $name) {
			$update[] = 'name="'.$db->safesql($name).'"';
		}
		
		
		//Цена 
		$voice = $_POST['voice'];
		if(!is_numeric($voice) || $voice <= 0) {
			err($language['default_1'] , 'Не верный формат цены' , 1);
		}
		if($arr['voice'] != $voice) {
			$update[] = 'voice="'.$voice.'"';
		}
		
		//Файл обработки
		$file = trim($_POST['file']);
		if($file == '.' || $file == '..' || empty($file) )  {
			err($language['default_1'] , 'Не выбран файл обработчика' , 1);
		}
		
		if(!file_exists('modules/shop/'.$file) ) {
			err($language['default_1'] ,  'Файла обработчика не существует' , 1);
		}
		
		if($arr['file'] != $file) {
			$update[] = 'file="'.$file.'"';
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
			$uploaddir = "public/images/shop/";

			// What is the temporary file name?
			$ifile = $_FILES['image']['tmp_name'];
			
			// Calculate what the next torrent id will be
			if(!$_GET['id']) {
				$row = $db->super_query("SHOW TABLE STATUS LIKE 'shop'");
				$id = $row['Auto_increment'];
			} 
			
			// By what filename should the tracker associate the image with?
			$ifilename = $id .  substr($_FILES['image']['name'], strlen($_FILES['image']['name'])-4, 4);


			// Upload the file
			$copy = @copy($ifile, $uploaddir.$ifilename);

			if (!$copy) {
				err($language['default_1'] , $language['cats_12'], 1);
			}
			
			$update[] = "image='".$db->safesql($ifilename)."'";
		}
		
		//Добавляем/Обновляем 
		if($_GET['id'] && count($update) ) {
			
			$db->query("UPDATE shop SET ".implode(',' , $update)." WHERE id=".$id);
		} elseif(count($update) ) {
			$db->query("INSERT INTO shop SET ".implode(',' , $update)."");
		}
		
		//Перенаправление
		header('Location:shop.php?status=2');
		die();
		
	}
	
	$name = ($_GET['id'] ? 'Редактирование товара' : 'Добавление товара');
	head($name);
	begin_frame($name);
	?>
	<form action="shop.php?id=<?=$id;?>&act=edit" method="POST" enctype="multipart/form-data" >
	
	<!--Файлы-->
	<table width="80%"  cellspacing="7" cellpadding="0" border="0"  align="center">
	<tbody>


	<tr>
		<td class="ta_r">
		 <span class="grey">Название товара:</span>
		</td>
		<td style="padding: 0px;">
		 <input type="text" name="name" style="margin: 0px;" size="25" class="inputText" value="<?=htmlspecialchars($arr['name']);?>">
		</td><td>
	   </td></tr>
	   
	<tr>
		<td class="ta_r">
		 <span class="grey">Цена:</span>
		</td>
		<td style="padding: 0px;">
		 <input type="text" name="voice" style="margin: 0px;" size="10" class="inputText" value="<?=$arr['voice'];?>"> рублей
		<br><small>Формат: 1.00</small>	
		</td><td>
	   </td></tr>  
	  
		<tr>
		<td class="ta_r" valign="top">
		 <span class="grey" >Картинка:</span>
		</td>
		<td style="padding: 0px;">
			<input type="file" name="image">
			<br><small>Вы можете загрузить картинку</small>
		</td><td>
	   </td></tr>
	   <tr>
		<td class="ta_r" valign="top">
		 <span class="grey" >Файл обработки:</span>
		</td>
		<td style="padding: 0px;">
			<select name="file">
			<?
			$dir = "modules/shop/";

			// Открыть заведомо существующий каталог и начать считывать его содержимое
			if (is_dir($dir)) {
			   if ($dh = opendir($dir)) {
				   while (($file = readdir($dh)) !== false) {
					    if($file != '.' && $file != '..') {
							echo '<option '.($arr['file'] == $file ? 'selected' : '').' value="'.$file.'">'.$file.'</option>';
						}	
				   }
				   closedir($dh);
			   }
			}
			?>
			</select>
		</td><td>
	   </td></tr>
	   
	   	<tr>
		<td class="ta_r">
		 <span class="grey"></span>
		</td>
		<td style="padding: 0px;">
		 <input type="submit" value="<?=($_GET['id'] ? 'Редактировать' : 'Добавить');?>">
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

/////////////////////////////////////////////////////////////////////
//Обработка покупки
/////////////////////////////////////////////////////////////////////
if($_GET['act'] == 'voicing' && $_GET['id']) {

	//Номер товара
	$id = (int)$_GET['id'];
	
	//Запрос в базу данных
	$db->query("SELECT * FROM shop WHERE id=".$id);
	if(!$db->num_rows() ) {
		err($language['default_1'] , 'Данного товара не существует' , 1);
	}
	$arr = $db->get_row();
	
	//Проверяем , есть ли у пользователя столько денег
	if($arr['voice'] > $USER['voice']) {
		err($language['default_1'] ,  'У вас недостаточно средств' , 1);
	}
	
	//Проверяем , существует ли файл - обработчик
	if(!file_exists('modules/shop/'.$arr['file']) ) {
		err($language['default_1'] ,  'Данный товар времмено закрыт' , 1);
	}
	
	//Выполняем действие
	require 'modules/shop/'.$arr['file'];
	
	
	//Снимаем деньги
	$db->query("UPDATE users SET voice=(voice - ".$arr['voice'].") WHERE id=".$USER['id']."");
	$memcache->delete('user_'.$USER['id']);
	header('Location:shop.php?status=1');
	die();
}

/////////////////////////////////////////////////////////////////////
//Общий вид
/////////////////////////////////////////////////////////////////////
//Запрос к таблице
$sql = $db->query("SELECT * FROM shop ORDER BY date DESC");
if(!$db->num_rows($sql) ) {
	err('Товаров на трекере не обнаружено' , 'Попробуйте зайти позже' , 1 );
}

//Заголовок
head('Магазин на трекере');


//Статусы
if($_GET['status'] == '1') {
	msg('Покупка успешно совершена');
}elseif($_GET['status'] == '2') {
	msg('Операция успешно выполнена');
}elseif($_GET['status'] == '3') {
	msg('Товар успешно удален');
}


begin_frame('Магазин на трекере');

if($PRIV['EDIT_PRIV']) {
	echo '<input type="button" value="Добавить товар" onClick="window.location.href=\'shop.php?act=edit\'"><br><br>';
}	


echo '<table width="100%" align="center">';
while($arr = $db->get_row($sql) ) {
	echo '<tr>';
	
	echo '<td width="1">';
	if($arr['image']) {
		echo '<img src="public/images/shop/'.$arr['image'].'" width="auto">';
	}
	echo '</td>';	
		
	echo '<td>';	
	echo htmlspecialchars($arr['name']);
	echo '</td>';	
	
	echo '<td>';	
	echo '<b>Цена: '.$arr['voice'].' рублей</b>';
	echo '</td>';	
	
	echo '<td>';	
	echo '<input type="button" value="Купить" onClick="window.location.href=\'shop.php?act=voicing&id='.$arr['id'].'\'">';
	if($PRIV['EDIT_PRIV']) {
		echo '&nbsp<input type="button" value="Редактировать" onClick="window.location.href=\'shop.php?act=edit&id='.$arr['id'].'\'">';
		echo '&nbsp<input type="button" value="Удалить" onClick="window.location.href=\'shop.php?act=delete&id='.$arr['id'].'\'">';
	}	
	echo '</td>';	
	
	echo '</tr>';	
}
echo '</table>';
end_frame();
//Подвал
foot();
?>