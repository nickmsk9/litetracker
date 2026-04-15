<?
/*
===================================================================
LiteTracker Source
===================================================================
by jenaDI
-------------------------------------------------------------------
Назначение: Авторизация
===================================================================
*/

//Подключаем главный системный файл
require 'system/init.php';

$op = (string) ($_GET['op'] ?? '');
$step = isset($_GET['step']) ? (int) $_GET['step'] : 0;
$ok = !empty($_GET['ok']);
$code = trim((string) ($_GET['code'] ?? ''));


if($USER) {
	err($language['default_1'] , $language['default_6']  , 1);
}

/////////////////////////////////////////////////////////////////////
//Забыли пароль
/////////////////////////////////////////////////////////////////////
if($op == 'forgot') {

	//Если не включена функция отправки писем , завершаем работу
	if(!$config['mail']['use'])  {
		err('Ошибка' , 'Администрация отключила данный сервис' , 1);
	}
	
	/////////////////////////////////////////////////
	//Второй шаг
	/////////////////////////////////////////////////
	if($step == 2) {
		//Если все прошло успешно
		if($ok) {
			err('Успешно' , 'Новый пароль пришел к вам на E - mail адрес <br> <a href="login.php">Войти</a>');
		}	
		
		
		$check_code = $db->query("SELECT * FROM forgot WHERE code='".$db->safesql($code)."'");
		if(!$db->num_rows($check_code) ) {
			err('Ошибка' , 'Данный код не найден , или он уже просрочен' , 1);
		}
		$row = $db->get_row($check_code);
		
		//Информация о пользователе
		$sql = $db->query("SELECT * FROM users WHERE email='".$db->safesql($row['email'])."'");
		$arr = $db->get_row($sql);
		
		//Генерируем новый пароль
		$password = mksecret(15);
		$password_code = mksecret(32); //Формируем секретный код
		$password_hash = md5($password_code . $password . $password_code); // Пасс для Базы
		
		//Перезаписываем пароль
		$db->query("UPDATE users SET password='".$password_hash."' , password_code='".$password_code."' WHERE id=".$arr['id']);
		
		//Удаляем кеш
		$memcache->delete('user_'.$arr['id'] , 0);
		
		
		//Логинимся
		// logout_cookie();
		login_cookie($arr['id']  , $password_hash);
		
		//Отправляем письмо в личные сообщения
		send_msg('Успешное восстановление пароля'  , 'Вы успешно восстановили пароль ! 
													 [b]Новый пароль:[/b]'.$password.' (вторая копия отправлена на E-mail)
													 Изменить пароль вы можете в Настройках .
													 P.S Больше не теряйте пароль ;)' , $arr['id']  , 0 );	
													 
		//Отправляем письмо на email
		//Заголовок
		$body = '';
		$body .= "Здравствуйте, вы успешно восстановили пароль на трекере ".htmlspecialchars($_SERVER['HTTP_HOST'])."\n\r";
		$body .= "Теперь вы можете войти под своим аккаунтом\n\r";
		$body .= "--------------------------------------------------\n\r";
		$body .= "Пользователь:".$arr['name']."\n\r";
		$body .= "Пароль: ".$password."\n\r";
		$body .= "--------------------------------------------------\n\r";
		$body .= "Внимание! Вы можете изменить пароль в Настройках\n\r";
		$body .= "С уважением , администрация трекера\n\r";
		$body .= "--------------------------------------------------\n\r";
		
		//Отправка письма
		$mail = new phpmailer;
		$mail->AddAddress($row['email'], $arr['name']);
		$mail->Subject = htmlspecialchars($_SERVER['HTTP_HOST']).'.Support';
		$mail->Body = $body;
		$mail->Send(); // send message
		
		//Удаляем запись 
		$db->query("DELETE FROM forgot WHERE code='".$db->safesql($code)."'");
		
		//Переадресация
		header('Location: index.php');
		die();
	}
	
	
	/////////////////////////////////////////////////
	//Обработка отправки письма (1 шаг)
	/////////////////////////////////////////////////
	if($step === 0 || $step == 1) {
	
		//Если все прошло успешно
		if($ok) {
			err('Успешно' , 'Проверьте ваш E-Mail адрес , вам должно было прийти письмо' , 0 , 'success');
		}
		
		//Обработка
		if($_POST) {
		
			//Определяем переменные
			$email = trim($_POST['email']);
			
			//Проверяем введенные данные
			if(empty($email)){
				err('Ошибка' , 'Вы ничего не ввели' , 1);
			}
			
			//Валидность email
			if (!validemail($email) ) {
				err($language['default_1']  , 'E-mail введен не верно' , 1); 
			}	

			//Проверяем email на уникальность
			$sql = $db->query("SELECT * FROM users WHERE email='".$db->safesql($email)."'");
			if(!$db->num_rows($sql)) {
				err($language['default_1']   , 'Пользователь с таким E-mail адресом не найден'  , 1);
			}
			
			//Проверяем запись forgot
			$check_forgot = $db->query("SELECT * FROM forgot WHERE email='".$db->safesql($email)."'");
			if($db->num_rows($check_forgot) ) {
				err('Ошибка' , 'Вы уже подавали заявку на восстановление , проверьте свой email' , 1);
			}
			
			//Массив с данными
			$arr =  $db->get_row($sql);
			
		
			//Отправляем письмо
			$code = md5(time().'LiteTracker'.rand()); //Код активации
			$db->query("INSERT INTO forgot (code , date , email) VALUES ('".$code."' , NOW() , '".$db->safesql($email)."')");
			
			//Заголовок
			$body = '';
			$body .= "Здравствуйте, вы запросили восстановление пароля на нашем трекере ".htmlspecialchars($_SERVER['HTTP_HOST'])."\n\r";
			$body .= "Для успешной смены пароля , вы должны подтвердить свой аккаунт\n\r";
			$body .= "--------------------------------------------------\n\r";
			$body .= "Пользователь:".$arr['name']."\n\r";
			$body .= "Cсылка на активацию: http://".htmlspecialchars($_SERVER['HTTP_HOST'])."/login.php?op=forgot&step=2&code=".$code."\n\r";
			$body .= "--------------------------------------------------\n\r";
			$body .= "Внимание! Код действует в течении 15 суток , со дня отправки\n\r";
			$body .= "С уважением , администрация трекера\n\r";
			$body .= "--------------------------------------------------\n\r";
			
			//Отправка письма
			$mail = new phpmailer;
			$mail->AddAddress($email, $arr['name']);
			$mail->Subject = htmlspecialchars($_SERVER['HTTP_HOST']).'.Support';
			$mail->Body = $body;
			$mail->Send(); // send message
			
			//Переадресация
			header('Location:login.php?op=forgot&step=1&ok=1');
			die();
			

		}

		/////////////////////////////////////////////////
		//Вывод формы
		/////////////////////////////////////////////////
		head('Восстановление пароля');
		begin_frame('Восстановление пароля');
		msg('После ввода E-mail , вам должно прийти письмо!');
		?>
		<form action="login.php?op=forgot" method="post">
		<table width="70%" align="center">
			<tr>	
				<td width="10%">E - Mail:</td>
				<td><input type="text" name="email" size="50%"></td>
			</tr>
			
			<tr>	
				<td></td>
				<td><input type="submit" value="Отправить письмо"></td>
			</tr>
			</tr>
		</table>
		</form>
		<?
		end_frame();
		foot();
		die();
	}
}

/////////////////////////////////////////////////////////////////////
//Обработка данных
/////////////////////////////////////////////////////////////////////
if($_POST) {
	$login = trim($_POST['login']);	//E-mail адрес
	$password = trim($_POST['password']); //Пароль
	$referer = htmlspecialchars(trim($_POST['referer'])); //Реферер

	//Проверяем , введены ли данные
	if(empty($login) || empty($password) ) {
		err($language['default_1'] , $language['login_7'] , 1);
	}

	//Выполняем запрос к базе данных
	$arr = $db->super_query("SELECT * FROM users WHERE email = '" . $db->safesql($login) . "' OR name = '" . $db->safesql($login) ."'" ) ;
	if(!$arr) {
		err($language['default_1'] , $language['login_8'] , 1);
	}

	//Хеш пароля
	$password_hash = md5($arr['password_code'].$password.$arr['password_code']);

	//Проверяем пароль
	if($arr['password'] != $password_hash ) {
		err($language['default_1'] ,  $language['login_8'] , 1);
	}

	//Забанен ли пользователь
	if($arr['banned']) {
		err($language['default_1'] ,  $language['login_9'] , 1);
	}
	
	
	
	//Защитный код
	if($config['reCaptcha'] && $config['reCaptcha_login']) {
		$resp = recaptcha_check_answer ($config['reCaptcha_privatekey'],
									$_SERVER["REMOTE_ADDR"],
									$_POST["recaptcha_challenge_field"],
									$_POST["recaptcha_response_field"]);

		if (!$resp->is_valid) {
			// What happens when the CAPTCHA was entered incorrectly
			err($language['default_1'] , $language['captcha_2'] , 1);
		}
	}
	
	//Подтвердил ли регистрацию
	if(!$arr['confirm']) {
		err($language['default_1']  , $language['login_10']);
	}
	
	//Удаляем кеш
	$memcache->delete('user_'.$arr['id'] , 0);
	
	//Определяем cookies
	logout_cookie();
	login_cookie($arr['id'] , $password_hash );

	//Переадресация
	if (!empty($referer) )  {
		header('Location:'.$referer);
	}else{
		header('Location:index.php');
	}	

	die();
}

//Определяем реферер
$referer = htmlspecialchars((string) ($_GET['referer'] ?? ''));
if(empty($referer))
 $referer = "index.php";

//Заголовок
head($language['login_1'] , true);
begin_frame($language['login_1']);
msg($language['default_7'], $language['login_2']);

?>


<form  action="login.php"  id="loginPage" method="post">
<table width="80%" cellspacing="7" cellpadding="0" border="0" align="center">
   <tbody><tr>
    <td class="ta_r">
     <span class="grey"><?=$language['login_3'];?>:</span>
    </td>
    <td style="padding: 0px;">
     <input type="text" style="margin: 0px;" size="25"  name="login" class="inputText" value="">
    </td><td>
   </td></tr>
   <tr>
    <td class="ta_r">
     <span class="grey"><?=$language['login_4'];?>:</span>
    </td>
    <td style="padding: 0px;">
     <input type="password" style="margin: 0px;" size="25"  name="password" class="inputText" value="">
    </td>
   </tr>

	<? if($config['reCaptcha'] && $config['reCaptcha_login']) { ?>
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
	<input type="submit" value="<?=$language['login_5'];?>">
	<input type="button" value="<?=$language['signup_3'];?>" onClick="window.location.href='signup.php'">
</div>

    </td>
   </tr>
   


   
   <tr>
    <td>
     &nbsp;
    </td>
    <td class="forgotPass">
     <a href="login.php?op=forgot"><?=$language['login_6'];?></a>
    </td>
   </tr>

  </tbody></table>
  <input type="hidden" value="<?=$referer;?>" name="referer">
  </form>


<?
end_frame();
//Подвал
foot(true);
?>
