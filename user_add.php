<?
/*
===================================================================
LiteTracker Source
===================================================================
by jenaDI
-------------------------------------------------------------------
Назначение: Добавление пользователя
===================================================================
*/


//Подключаем главный системный файл
require 'system/init.php';

//Проверяем права
if(!$PRIV['user_add']) {
	err($language['default_1']	, $language['user_add_1'] , 1);
}

//Обработка
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
	if (strlen($nick) > 12) {
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



	$password_code = mksecret(32); //Формируем секретный код
	$password_hash = md5($password_code . $password . $password_code); // Пасс для Базы

	//Класс при регистрации
	$class = (int)$_POST['class'];
	$db->query("SELECT * FROM priv WHERE id > 0 AND id = ".$class);
	if(!$db->num_rows() ) {
		err($language['default_1']   , $language['signup_20'] , 1);
	}
	//Добавляем новго пользователя
	$db->query("INSERT INTO users (name , avatar , email , password , password_code , ip , class , last_access , added , passkey , uploaded , downloaded , money , website , icq , last_chat , num_messages , num_friends) VALUES ('".$db->safesql($name)."' , '' , '".$db->safesql($email)."' , '".$password_hash."' , '".$password_code."' , '".ip2long_db(getip())."' , '".$class."' , NOW() , NOW() , '' , '0' , '0' , '0' , '' , '' , '0' , '0' , '0')");
	header("Location:user_add.php?status=1");
	die();
}

//Заголовок
head($language['user_add_2']);

if($_GET['status'] == '1') {
	msg($language['user_add_3']);
}

begin_frame($language['user_add_2']);

?>
<form  action="user_add.php"  id="loginPage" method="post">
<table width="80%" cellspacing="7" cellpadding="0" border="0" align="center">
   <tbody><tr>
    <td class="ta_r">
     <span class="grey"><?=$language['signup_7'];?>:</span>
    </td>
    <td style="padding: 0px;">
     <input type="text" style="margin: 0px;" size="25"  name="email" class="inputText" value="<?=htmlspecialchars($_POST['email']);?>">
    </td><td>
   </td></tr>
   
   <tr>
    <td class="ta_r">
     <span class="grey"><?=$language['signup_8'];?>:</span>
    </td>
    <td style="padding: 0px;">
     <input type="text" style="margin: 0px;" size="25"  name="name" class="inputText" value="<?=htmlspecialchars($_POST['email']);?>">
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
   
   
			<tr>
			<td class="ta_r">
			 <span class="grey"><?=$language['setting_40'];?>:</span>
			</td>
			<td style="padding: 0px;">
			<select name="class">
			<? 
			//Выводим список классов
			$classes = get_classes_list();
			foreach($classes AS $class) {
				echo '<option '.($class['id'] == $arr['class'] ? 'selected' : '').' value="'.$class['id'].'">'.htmlspecialchars($class['NAME']).'</option>';	
			}
			?>
			
			 </select>
			
			</td><td>
		   </td>
		   </tr>

   


   <tr>
    <td>
     &nbsp;
    </td>
    <td>
<div style="height: 20px; margin: 5px 0px;">
	<input type="submit" value="<?=$language['user_add_4'];?>" >
</div>

    </td>
   </tr>
  

  </tbody></table>

  </form>
<?
end_frame();

//Подвал
foot();


?>
