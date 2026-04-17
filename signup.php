<?php
/*
===================================================================
LiteTracker Source
===================================================================
by jenaDI
-------------------------------------------------------------------
Назначение: Регистрация
===================================================================
*/

require 'system/init.php';

$act = trim((string) ($_GET['act'] ?? ''));

if(!$config['registeronline'] || $USER) {
	err($language['signup_1'] , $language['signup_2']);
}

if($act === 'confirm') {
	err('Ошибка', 'Подтверждение регистрации по e-mail отключено. Используйте обычный вход на сайт.', 1);
}

if($_POST) {
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

	if($config['reCaptcha'] && $config['reCaptcha_signup']) {
		$resp = recaptcha_check_answer(
			$config['reCaptcha_privatekey'],
			$_SERVER['REMOTE_ADDR'] ?? '',
			$_POST['recaptcha_challenge_field'] ?? '',
			$_POST['recaptcha_response_field'] ?? ''
		);

		if (!$resp->is_valid) {
			err($language['default_1'], $language['captcha_2'], 1);
		}
	}

	$passwordCode = mksecret(32);
	$passwordHash = md5($passwordCode.$password.$passwordCode);

	$countUsers = $db->super_query("SELECT COUNT(*) AS c FROM users");
	$class = $db->super_query("SELECT id FROM priv WHERE ".($countUsers['c'] > 0 ? 'SIGNUP=1' : 'EDIT_PRIV=1')." LIMIT 1");
	$classId = (int) ($class['id'] ?? 0);

	$db->query("INSERT INTO users (name, avatar, email, password, password_code, ip, class, last_access, added, passkey, uploaded, downloaded, money, website, icq, last_chat, num_messages, num_friends, confirm) VALUES ('".$db->safesql($name)."', '', '', '".$passwordHash."', '".$passwordCode."', '".ip2long_db(getip())."', '".$classId."', NOW(), NOW(), '', '0', '0', '0', '', '', '0', '0', '0', '1')");

	$id = (int) $db->insert_id();

	login_cookie($id, $passwordHash);

	header('Location: index.php');
	die();
}

head($language['signup_3'], true);
begin_frame($language['signup_3']);
msg($language['signup_4'], 'Регистрация выполняется по логину и паролю. E-mail больше не требуется.');
?>

<form action="signup.php" id="loginPage" method="post">
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

	<?php if($config['reCaptcha'] && $config['reCaptcha_signup']) { ?>
	<tr>
		<td class="ta_r">
			<span class="grey"><?=$language['captcha_1'];?>:</span>
		</td>
		<td style="padding: 0px;">
			<?=recaptcha_get_html($config['reCaptcha_publickey']);?>
		</td>
	</tr>
	<?php } ?>

	<tr>
		<td>&nbsp;</td>
		<td>
			<div style="height: 20px; margin: 5px 0px;">
				<input type="submit" value="<?=$language['signup_3'];?>">
			</div>
		</td>
	</tr>
	</tbody>
</table>
</form>

<?php
end_frame();
foot(true);
?>
