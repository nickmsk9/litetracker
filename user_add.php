<?php
/*
===================================================================
LiteTracker Source
===================================================================
by Nick
-------------------------------------------------------------------
Назначение: Добавление пользователя
===================================================================
*/

require 'system/init.php';

$userAddBonusColumn = (lt_column_exists('users', 'bonus') ? 'bonus' : 'voice');

if(!$PRIV['user_add']) {
	err($language['default_1'], $language['user_add_1'], 1);
}

if($_POST) {
	if (!lt_csrf_validate('user_add_form')) {
		err($language['default_1'], 'Защитный токен устарел. Обновите страницу и попробуйте снова.', 1);
	}

	$name = trim((string) ($_POST['name'] ?? ''));
	$password = trim((string) ($_POST['password'] ?? ''));

	if($name === '' || $password === '') {
		err($language['default_1'], 'Введите логин и пароль.', 1);
	}

	if (!validusername($name)) {
		err($language['default_1'], $language['signup_11'], 1);
	}

	if (strlen($name) > 12) {
		err($language['default_1'], $language['signup_12'], 1);
	}

	if (strlen($password) < 6) {
		err($language['default_1'], $language['signup_13'], 1);
	}

	if (strlen($password) > 40) {
		err($language['default_1'], $language['signup_14'], 1);
	}

	$nameCheck = $db->query("SELECT * FROM users WHERE name='".$db->safesql($name)."'");
	if($db->num_rows() >= 1) {
		err($language['default_1'], $language['signup_17'], 1);
	}

	$passwordHash = lt_password_hash_value($password);

	$class = (int) ($_POST['class'] ?? 0);
	$db->query("SELECT * FROM priv WHERE id > 0 AND id = ".$class);
	if(!$db->num_rows()) {
		err($language['default_1'], $language['signup_20'], 1);
	}

	$db->query("INSERT INTO users (name, avatar, email, password, password_code, ip, class, last_access, added, passkey, uploaded, downloaded, money, ".$userAddBonusColumn.", last_chat, num_messages, num_friends) VALUES ('".$db->safesql($name)."', '', '', '".$db->safesql($passwordHash)."', '', '".ip2long_db(getip())."', '".$class."', NOW(), NOW(), '', '0', '0', '0', '300', '0', '0', '0')");
	header("Location:user_add.php?status=1");
	die();
}

head($language['user_add_2']);

if(($_GET['status'] ?? '') == '1') {
	msg($language['user_add_3']);
}

begin_frame($language['user_add_2']);
?>
<form action="user_add.php" id="loginPage" method="post">
<?=lt_csrf_input('user_add_form');?>
<table width="80%" cellspacing="7" cellpadding="0" border="0" align="center">
	<tbody>
	<tr>
		<td class="ta_r">
			<span class="grey"><?=$language['signup_8'];?>:</span>
		</td>
		<td style="padding: 0px;">
			<input type="text" style="margin: 0px;" size="25" name="name" class="inputText" value="<?=htmlspecialchars((string) ($_POST['name'] ?? ''), ENT_QUOTES, 'UTF-8');?>">
		</td>
		<td></td>
	</tr>
	<tr>
		<td class="ta_r">
			<span class="grey"><?=$language['signup_9'];?>:</span>
		</td>
		<td style="padding: 0px;">
			<input type="password" style="margin: 0px;" size="25" name="password" class="inputText" value="">
		</td>
	</tr>
	<tr>
		<td class="ta_r">
			<span class="grey"><?=$language['setting_40'];?>:</span>
		</td>
		<td style="padding: 0px;">
			<select name="class">
			<?php
			$classes = get_classes_list();
			foreach($classes AS $class) {
				echo '<option value="'.$class['id'].'">'.htmlspecialchars($class['NAME'], ENT_QUOTES, 'UTF-8').'</option>';
			}
			?>
			</select>
		</td>
		<td></td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td>
			<div style="height: 20px; margin: 5px 0px;">
				<input type="submit" value="<?=$language['user_add_4'];?>">
			</div>
		</td>
	</tr>
	</tbody>
</table>
</form>
<?php
end_frame();
foot();
?>
