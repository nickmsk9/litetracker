<?
/*
===================================================================
LiteTracker Source
===================================================================
by jenaDI
-------------------------------------------------------------------
Назначение: Обработка настроек
===================================================================
*/

//Подключаем главный системный файл
require 'system/init.php';

//Проверяем пользователя
is_login();

//Определяем номер пользователя
$id = (int)$_GET['id'];

if(!$id || $id == $USER['id']) {
	$id = $USER['id'];
	$arr = $USER;
}else {
	$arr = $db->super_query("SELECT * FROM users WHERE id='".$id."'");
	$priv = get_priv_info($arr['class']);
	if(!$PRIV['setting_user'] || $priv['EDIT_PRIV']) {
		err($language['default_1'] , $language['setting_1'] , 1);
	}
}	


//////////////////////////////////////////////////////////////
//Бан IP
//////////////////////////////////////////////////////////////
if($_GET['act'] == 'ban_ip') {
	$ip = ip2long_db($arr['ip']);
	$db->query("SELECT * FROM bans  WHERE '".$ip."'  >= first AND '".$ip."' <= last");
	//Добавляем IP в бан
	if(!$db->num_rows()) {
		$db->query("INSERT INTO bans (first , last , id_user , date) VALUES ( '".$ip."' , '".$ip."' , ".$USER['id']." , NOW() )");
		$memcache->delete('ip_bans_'.$ip , 0);
		header('Location:my.setting.php?id='.$id.'&status=11');
		die();
	}else{
		$db->query("DELETE FROM bans   WHERE '".$ip."'  >= first AND '".$ip."' <= last");
		$memcache->delete('ip_bans_'.$ip  , 0);
		header('Location:my.setting.php?id='.$id.'&status=12');
		die();
	}
	
}

//////////////////////////////////////////////////////////////
//Бан аккаунта
//////////////////////////////////////////////////////////////
if($_GET['act'] == 'ban_account') {
	//Баним аккаунт
	if($arr['banned'] == 0) {
		$db->query("UPDATE users SET banned='1' WHERE id=".$id);
			$memcache->delete('user_'.$id , 0);
			header('Location:my.setting.php?id='.$id.'&status=9');
			die();	
	} else {
	//Убираем бан
		$db->query("UPDATE users SET banned='0' WHERE id=".$id);
		$memcache->delete('user_'.$id , 0);
		header('Location:my.setting.php?id='.$id.'&status=10');
		die();	
	}

	
}


//////////////////////////////////////////////////////////////
//Удаление фотографии
//////////////////////////////////////////////////////////////
if($_GET['act'] == 'foto_delete') {
	@unlink("public/avatars/".$arr['avatar']);
	@unlink("public/avatars/small/".$arr['avatar']);
	$db->query("UPDATE users SET avatar='' WHERE id='".$id."'");
	$memcache->delete('user_'.$id , 0);
	header('Location:my.setting.php?id='.$id.'&status=8');
	die();	
	
}


//////////////////////////////////////////////////////////////
//Загрузка фотографии
//////////////////////////////////////////////////////////////
if($_GET['act'] == 'foto') {

	
	$allowed_types = array(
	"image/gif" => "gif",
	"image/pjpeg" => "jpg",
	"image/jpeg" => "jpg",
	"image/jpg" => "jpg",
	"image/png" => "png" , 
	"image/bmp"=> "bmp" 
	// Add more types here if you like
	);


	if (!($_FILES["foto"]['name'] == "")) {

		// Is valid filetype?
		if (!array_key_exists($_FILES['foto']['type'], $allowed_types) ) {
			err($language['default_1'] , $language['setting_60'] , 1);
		}

		if (!preg_match('/^(.+)\.(jpg|jpeg|png|gif)$/si', $_FILES['foto']['name']) ) {
			err($language['default_1'] , $language['setting_61'] , 1);
		}
		
		//Директории для загрузки
		$dir_dest = 'public/avatars/';
		$dir_dest_small = 'public/avatars/small/';
		
		//Используем класс загрузки
		require 'system/classes/class.upload.php';
		
		
		//Большая фотография
		$photo = new Upload($_FILES['foto']);
		
		if ($photo->uploaded) {
			
			//Удаляем старые аватарки
			if($arr['avatar'])  {
				@unlink('public/avatars/small/'.$arr['avatar']);
				@unlink('public/avatars/'.$arr['avatar']);
			}

			$photo->file_max_size = $config['max_size_image']; // 1KB // максимальный размер загружаемого фото
			$photo->file_new_name_body = $USER['id']; // будущие имя файла
			$photo->image_resize = true;  // изменение размера
			$photo->image_convert = jpg; // конвертирование фото в формат .JPG
			$photo->image_x = 600; // Максимальный размер в ширину
			$photo->image_y = 600; // Максимальный размер в высоту
			$photo->image_ratio = true; // Сохранение пропорций
			$photo->image_text = 'LITETRACKER ENGINE'; // Подпись на фото графии
			$photo->image_text_position = 'RB'; // Расположение подписи на фотографии
			$photo->image_text_padding = 5; // Отступ подписи от краев в пикселях
			$photo->Process($dir_dest);
			$name = $photo->file_dst_name;
				
			//Загружаем , если все нормально
			if ($photo->processed) {
					$db->query("UPDATE users SET avatar='".$db->safesql($name)."' WHERE id='".$id."'");
					
			}
			// $photo->Clean();
		} else {
			err('Ошибка' , $photo->error , 1);
		}
		
	
		//Маленькая
		
		$photo_small = new Upload($_FILES['foto']);
		$photo_small->file_max_size = $config['max_size_image']; // 1KB // максимальный размер загружаемого фото
		$photo_small->file_new_name_body = $USER['id']; // будущие имя файла
		$photo_small->image_resize = true;  // изменение размера
		$photo_small->image_convert = jpg; // конвертирование фото в формат .JPG
		$photo_small->image_x = 100; // Максимальный размер в ширину
		$photo_small->image_y = 100; // Максимальный размер в высоту
		$photo_small->image_ratio = true; // Сохранение пропорций
		// $photo_small->image_text = 'http://Torrent - Tracker.Ru'; // Подпись на фото графии
		// $photo_small->image_text_position = 'RB'; // Расположение подписи на фотографии
		// $photo_small->image_text_padding = 5; // Отступ подписи от краев в пикселях
		$photo_small->Process($dir_dest_small);
		$photo_small->Clean();
		
		// err('Ошибка' , $photo_small->error , 1);
	
		//Удаляем кеш , и перенаправляем
		$memcache->delete('user_'.$id , 0);
		header('Location:my.setting.php?id='.$id.'&status=7');
	
	} else {
		header('Location:my.setting.php?id='.$id.'&status=6');
		die();	
	}

}

//////////////////////////////////////////////////////////////
//Изменение Пасскей
//////////////////////////////////////////////////////////////
if($_GET['act'] == 'passkey') {
	$db->query("UPDATE users SET passkey='' WHERE id='".$id."'");
	$memcache->delete('user_'.$id , 0);
	header('Location:my.setting.php?id='.$id.'&status=5');
	die();	
}

//////////////////////////////////////////////////////////////
//Изменение e-mail
//////////////////////////////////////////////////////////////
if($_GET['act'] == 'email') {
	
	$email = trim($_POST['email']);
	if($email == $arr['email'] || empty($email) ) {
		header('Location:my.setting.php?id='.$id.'&status=3');
		die();	
	}
	
	//Валидность email
	if (!validemail($email) ) {
		err($language['default_1'] , $language['setting_64'] , 1); 
	}	

	//Проверяем email на уникальность
	$email_check = $db->query("SELECT * FROM users WHERE email='".$db->safesql($email)."'");
	if($db->num_rows() >= 1) {
		err($language['default_1'] , $language['setting_65'] , 1);
	}
	
	/*
	...
	Подтверждение e-mail
	...
	*/
	
	
	$db->query("UPDATE users SET email='".$db->safesql($email)."' WHERE id='".$id."'");
	$memcache->delete('user_'.$id , 0);
	header('Location:my.setting.php?id='.$id.'&status=4');
	die();
}




//////////////////////////////////////////////////////////////
//Изменение пароля
//////////////////////////////////////////////////////////////
if($_GET['act'] == 'password') {
	
	//Старый пароль
	if(!$PRIV['setting_user']) {
		$old_password = trim($_POST['old_password']);
		if($arr['password'] != md5($arr['password_code'].$old_password.$arr['password_code']) ) {
			err($language['default_1'] ,  $language['setting_66'] , 1);
		}
	}
	
	//Новые пароли
	$new_password = trim($_POST['new_password']);
	$new_password_1 = trim($_POST['new_password_1']);
	
	//Валидность пароля
	if (strlen($new_password) < 6) {
		err($language['default_1'] , $language['setting_67'] , 1); 
	}

	if (strlen($new_password) > 40) {
		err($language['default_1'] , $language['setting_68'] ,  1); 
	}
	
	if($new_password != $new_password)  {
		err($language['default_1'] , $language['setting_69'],  1); 
	}
	
	$password_code = mksecret(32); //Формируем секретный код
	$password_hash = md5($password_code . $new_password . $password_code); // Пасс для Базы
	
	
	$db->query("UPDATE users SET password='".$password_hash."' , password_code='".$password_code."' WHERE id='".$id."'");
	$memcache->delete('user_'.$id , 0);
	header('Location:my.setting.php?id='.$id.'&status=2');
	die();
}


//////////////////////////////////////////////////////////////
//Общие настройки
//////////////////////////////////////////////////////////////
$update = array();

//Ник
$name = trim($_POST['name']);
if($arr['name'] != $name) {
	if(empty($name) ) {
		err($language['default_1'] , $language['setting_70'] , 1);
	}
	//Валидность ника
	if (!validusername($name) ) {
	  err($language['default_1'] , $language['setting_71'] , 1); 
	}   
	if (strlen($nick) > 12) {
		err( $language['default_1'] ,$language['setting_72'] , 1); 
	}

	//Проверяем ник на уникальность
	$email_check = $db->query("SELECT * FROM users WHERE name='".$db->safesql($name)."'");
	if($db->num_rows() >= 1) {
		err($language['default_1'] ,$language['setting_73'] , 1);
	}

	$update[] = "name='".$db->safesql($name)."'";
}


//Пол
$sex = ((int)$_POST['sex'] == 1 ? '1' : '0');
if($arr['sex'] != $sex) {
	$update[] = "sex='".$sex."'";
}

//Веб-сайт
$website = trim($_POST['website']);
if($arr['website'] != $website) {
	$pattern = "#^(http://)?[-a-z0-9_\.]+([-a-z0-9_]+\.(html|php|pl|cgi))?([-a-z0-9_:@&\?=+\.!/~*'%$]+)?$#i";
	if(preg_match($pattern  , $website) ) {
		$update[] = "website='".$db->safesql($website)."'";
	}
}

//ICQ
$icq = (int)$_POST['icq'];
if($arr['icq'] != $icq) {
	if(is_numeric($icq) && strlen($icq) < 13) {
		$update[] = "icq='".$icq."'";	
	}
}


//Skype
$skype = trim($_POST['skype']);
if($arr['skype'] != $skype ) {
	if(strlen($skype) < 30 && validusername($skype) ) {
		$update[] = 'skype="'.$db->safesql($skype).'"';
	}	
}

//ID Vkontakte
$id_vkontakte = (int)$_POST['id_vkontakte'];
if($arr['id_vkontakte'] != $id_vkontakte) {
	$update[] = "id_vkontakte='".$id_vkontakte."'";	
}

//Использовать показ профиля
if($config['vkontakte_profile'] && $config['vkontakte_use']) {
	$use_vkontakte = ($_POST['use_vkontakte'] ? 1 : 0);
	$update[] = "use_vkontakte='".$use_vkontakte."'";	
}

//Административная часть
if($PRIV['setting_user'] || $PRIV['EDIT_PRIV']) {


	//Скачал
	$downloaded = (int)$_POST['downloaded']; //Число
	$down_command = ($_POST['down_command'] == '+' ? '+' : '-');
	$down_format = ($_POST['down_format']  == 'mb' ? (1024*1024) : (1024*1024*1024) );
	if($downloaded >= 0 ) {
		//Переводим в байты
		$bytes = $downloaded  * $down_format;
		
		//Какую команду будем делать
		if($down_command == '+') {
			$update[] = "downloaded=('".$bytes."' + downloaded)";
		} else {
			if($arr['downloaded'] < $bytes) {
				err($language['default_1'] , $language['setting_74'] , 1);
			}
			$update[] = "downloaded=(downloaded - '".$bytes."')";
		}
			
	}
	
	
	
	//Раздал
	$uploaded = (int)$_POST['uploaded']; //Число
	$up_command = ($_POST['up_command'] == '+' ? '+' : '-');
	$up_format = ($_POST['up_format']  == 'mb' ? (1024*1024) : (1024*1024*1024) );
	if($downloaded >= 0 ) {
		//Переводим в байты
		$bytes = $uploaded  * $up_format;
		
		//Какую команду будем делать
		if($up_command == '+') {
			$update[] = "uploaded=('".$bytes."' + uploaded)";
		} else {
			if($arr['uploaded'] < $bytes) {
				err($language['default_1'] , $language['setting_75'] , 1);
			}
			$update[] = "uploaded=(uploaded - '".$bytes."')";
		}
			
	}
	
	
	//Класс
	if($PRIV['EDIT_PRIV']) {
		$class = (int)$_POST['class'];
		
		//Проверяем, существует ли данный класс
		$db->query("SELECT * FROM priv WHERE id > 0 AND id=".$class);
		if($arr['class'] != $class && $db->num_rows()) {
			$update[] = "class='".$class."'";
			
			$info_class = get_priv_info($class);

			//Отправляем сообщение
			send_msg($language['setting_76']  , sprintf($language['setting_77'] , $info_class['NAME']) , $id  , 0 );	
		}
		
	}
	
	

}



//Обновляем данные
if(count($update) ) {
	$sql = $db->query("UPDATE users SET ".implode(',' , $update)." WHERE id='".$id."'");
}

$memcache->delete('user_'.$id , 0);
header('Location:my.setting.php?id='.$id.'&status=1');
die();
?>
