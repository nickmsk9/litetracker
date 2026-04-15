<?
/*
===================================================================
LiteTracker Source
===================================================================
by jenaDI
-------------------------------------------------------------------
Назначение: Регистрация
===================================================================
*/

//Подключаем главный системный файл
require 'system/init.php';

$act = (string) ($_GET['act'] ?? '');
$code = trim((string) ($_GET['code'] ?? ''));


if(!$config['registeronline'] || $USER) {
	err($language['signup_1'] , $language['signup_2'] );
}

/////////////////////////////////////////////////////////////////////
//Подтверждение регистрации
/////////////////////////////////////////////////////////////////////
if($act == 'confirm'  && $config['mail']['use'] && $config['mail']['signup']) {
	//Проверяем код
	$sql = $db->query("SELECT confirm.* ,users.id AS id_user , users.name , users.password , users.password_code FROM confirm 
					LEFT JOIN users ON users.id = confirm.id_user
					WHERE confirm.code='".$db->safesql($code)."'");
	if(!$db->num_rows($sql) ) {
		err($language['default_1'] , 'Данного кода не существует или он усталер');
	}
	$arr = $db->get_row($sql);
	
	//Активируем аккаунт
	$db->query("UPDATE users SET confirm='1' WHERE id=".$arr['id_user']);
	$db->query("DELETE FROM confirm WHERE id=".$arr['id']);
	
	//Авторизация
	login_cookie($arr['id_user'] , $arr['password']);
		
	//Отправляем сообщение
	// send_msg($language['signup_18']  , sprintf($language['signup_19'] , $arr['name']) , $arr['id_user']  , 0 );	
	
	err('Успешно' , 'Вы успешно активировали аккаунт . Теперь вы можете войти под своим логином и паролем <br> <a href="login.php">Войти на трекер</a>');
	//Перенаправление
	header("Location: index.php");
	die();
}

/////////////////////////////////////////////////////////////////////
//Обработка данных
/////////////////////////////////////////////////////////////////////
if($_POST) {
	$name = trim($_POST['name']); //Имя пользователя
	$password  = trim($_POST['password']); //Пароль
	$email  = trim($_POST['email']); // E-mail адрес

	if(empty($name) || empty($password) || empty($email) ) {
		err($language['default_1'] , $language['signup_10'] , 1);
	}

	//Валидность имени
	if (!validusername($name) ) {
	  err($language['default_1']  , $language['signup_11']  , 1); 
	}  
	if (strlen($name) > 12) {
		err($language['default_1']  , $language['signup_12']  , 1); 
	}

	//Валидность пароля
	if (strlen($password) < 6) {
		err($language['default_1']  , $language['signup_13']  , 1); 
	}

	if (strlen($password) > 40) {
		err($language['default_1']  , $language['signup_14'] , 1); 
	}

	//Валидность email
	if (!validemail($email) ) {
		err($language['default_1']  , $language['signup_15']  , 1); 
	}	

	//Проверяем email на уникальность
	$email_check = $db->query("SELECT * FROM users WHERE email='".$db->safesql($email)."'");
	if($db->num_rows() >= 1) {
		err($language['default_1']   , $language['signup_16']  , 1);
	}


	//Проверяем ник на уникальность
	$name_check = $db->query("SELECT * FROM users WHERE name='".$db->safesql($name)."'");
	if($db->num_rows() >= 1) {
		err($language['default_1'] ,  $language['signup_17'] , 1);
	}
	
	//Защитный код
	if($config['reCaptcha'] && $config['reCaptcha_signup']) {
		$resp = recaptcha_check_answer ($config['reCaptcha_privatekey'],
									$_SERVER["REMOTE_ADDR"],
									$_POST["recaptcha_challenge_field"],
									$_POST["recaptcha_response_field"]);

		if (!$resp->is_valid) {
			// What happens when the CAPTCHA was entered incorrectly
			err($language['default_1'] , $language['captcha_2'] , 1);
		}
	}


	$password_code = mksecret(32); //Формируем секретный код
	$password_hash = md5($password_code . $password . $password_code); // Пасс для Базы

	//Класс при регистрации
	$count_users = $db->super_query("SELECT COUNT(*) AS c FROM users");
	$class = $db->super_query("SELECT id FROM priv WHERE ".($count_users['c'] > 0 ? 'SIGNUP=1' : 'EDIT_PRIV=1' )." LIMIT 1");	
	$class = $class['id'];
	
	//Подтверждение
	if($config['mail']['signup'] && $config['mail']['use']) {
		$confirm = 0;
	} else { 
		$confirm = 1;
	}
	
	
	//Добавляем нового пользователя
	$db->query("INSERT INTO users (name , avatar , email , password , password_code , ip , class , last_access , added , passkey , uploaded , downloaded , money , website , icq , last_chat , num_messages , num_friends , confirm) VALUES ('".$db->safesql($name)."' , '' , '".$db->safesql($email)."' , '".$password_hash."' , '".$password_code."' , '".ip2long_db(getip())."' , '".$class."' , NOW() , NOW() , '' , '0' , '0' , '0' , '' , '' , '0' , '0' , '0' , '".$confirm."' )");

	//id user
	$id = $db->insert_id();
	
		
	//Подверждение регистрации
	if($config['mail']['signup'] && $config['mail']['use']) {
		//Создаем регистрационный код
		$code = md5(time().$name.rand());
		$db->query("INSERT INTO confirm (code , date , id_user) VALUES ('".$code."' , NOW() , ".$id.")");
		
		//Заголовок
		$body = '';
		$body .= "Здравствуйте, вы зарегистрировались на нашем трекере ".htmlspecialchars($_SERVER['HTTP_HOST'])."\n\r";
		$body .= "Для успешной регистрации вы должны активировать свой аккаунт\n\r";
		$body .= "--------------------------------------------------\n\r";
		$body .= "Пользователь:".$name."\n\r";
		$body .= "Cсылка на активацию: http://".htmlspecialchars($_SERVER['HTTP_HOST'])."/signup.php?act=confirm&code=".$code."\n\r";
		$body .= "--------------------------------------------------\n\r";
		$body .= "Внимание! Код действует в течении 15 суток , со дня регистрации\n\r";
		$body .= "С уважением , администрация трекера\n\r";
		$body .= "--------------------------------------------------\n\r";
		
		//Отправка письма
		$mail = new phpmailer;
		$mail->AddAddress($email, $name);
		$mail->Subject = "Регистрация на трекере ".htmlspecialchars($_SERVER['HTTP_HOST']);
		$mail->Body = $body;
		$mail->Send(); // send message
		
		//Запрещаем вход, пока не произошла активация
		$db->query("UPDATE users SET confirm='0' WHERE id=".$id);
		
		head('Процедура активации');
		begin_frame('Процедура активации');
		msg('Внимание' , 'Вы успешно зарегистрировались , и теперь вам надо подтвердить вашу регистрацию . На ваш e-mail должно прийти письмо с подтверждающим кодом<br><a href="index.php">На главную</a>');
		end_frame();
		foot();
		die();
	} else {
		//login 
		login_cookie($id  , $password_hash);
		
		//Отправляем сообщение
		// send_msg($language['signup_18']  , sprintf($language['signup_19'] , $name) , $id  , 0 );	

		header("Location: index.php");
		die();
	}
	
	
}

//Заголовок
head($language['signup_3'], true);
begin_frame($language['signup_3']);
msg($language['signup_4'] , $language['signup_5']);
?>
<script src="js/signup.js"> </script>



 <form  action="signup.php"  id="loginPage" method="post">
<table width="80%" cellspacing="7" cellpadding="0" border="0" align="center">
   <tbody><tr>
    <td class="ta_r">
     <span class="grey"><?=$language['signup_7'];?>:</span>
    </td>
    <td style="padding: 0px;">
     <input type="text" style="margin: 0px;" size="25"  name="email" class="inputText" value="<?=htmlspecialchars((string) ($_POST['email'] ?? ''));?>">
    </td><td>
   </td></tr>
   
   <tr>
    <td class="ta_r">
     <span class="grey"><?=$language['signup_8'];?>:</span>
    </td>
    <td style="padding: 0px;">
     <input type="text" style="margin: 0px;" size="25"  name="name" class="inputText" value="<?=htmlspecialchars((string) ($_POST['name'] ?? ''));?>">
    </td><td>
   </td></tr>
   
   <tr>
    <td class="ta_r">
     <span class="grey"><?=$language['signup_9'];?>:</span>
    </td>
    <td style="padding: 0px;">
     <input type="password" style="margin: 0px;" size="25"  name="password" class="inputText" value="">
    </td>
   </tr>
   
	<? if($config['reCaptcha'] && $config['reCaptcha_signup']) { ?>
   <tr>
    <td class="ta_r">
     <span class="grey"><?=$language['captcha_1'];?>:</span>
    </td>
    <td style="padding: 0px;">
		<?=recaptcha_get_html($config['reCaptcha_publickey']);?>

    </td>
   </tr>   
   <? } ?>
   


   <tr>
    <td>
     &nbsp;
    </td>
    <td>
<div style="height: 20px; margin: 5px 0px;">
	<input type="submit" value="<?=$language['signup_3'];?>" >
</div>

    </td>
   </tr>
  

  </tbody></table>

  </form>

<?
end_frame();
//Подвал
foot(true);
?>
