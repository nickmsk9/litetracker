<?php
/*
===================================================================
LiteTracker Source
===================================================================
by Nick
-------------------------------------------------------------------
Назначение: Регистрация
===================================================================
*/

require 'system/init.php';

$signupBonusColumn = (lt_column_exists('users', 'bonus') ? 'bonus' : 'voice');

$act = trim((string) ($_GET['act'] ?? ''));
$signupName = trim((string) ($_POST['name'] ?? ''));
$signupEmail = trim((string) ($_POST['email'] ?? ''));
$signupPassword = trim((string) ($_POST['password'] ?? ''));
$signupBirthdayDay = trim((string) ($_POST['birthday_day'] ?? ''));
$signupBirthdayMonth = trim((string) ($_POST['birthday_month'] ?? ''));
$signupBirthdayYear = trim((string) ($_POST['birthday_year'] ?? ''));
$signupAgreementAccepted = !empty($_POST['accept_rules']);
$isModalView = ((int) ($_GET['modal'] ?? 0) === 1);

$signupBirthdayMonths = array(
	'' => 'месяц',
	'01' => 'январь',
	'02' => 'февраль',
	'03' => 'март',
	'04' => 'апрель',
	'05' => 'май',
	'06' => 'июнь',
	'07' => 'июль',
	'08' => 'август',
	'09' => 'сентябрь',
	'10' => 'октябрь',
	'11' => 'ноябрь',
	'12' => 'декабрь',
);

function signup_error_response($title, $message, $goBack = 1)
{
	global $isModalView, $signupModalError;

	if (!$isModalView) {
		err($title, $message, $goBack);
	}

	$signupModalError = (string) $message;

	return false;
}

function signup_admin_class_id()
{
	global $db;

	$class = $db->super_query("SELECT id FROM priv WHERE EDIT_PRIV = 1 ORDER BY id DESC LIMIT 1");
	if (!empty($class['id'])) {
		return (int) $class['id'];
	}

	$class = $db->super_query("SELECT id FROM priv WHERE id > 0 ORDER BY id DESC LIMIT 1");
	return (int) ($class['id'] ?? 0);
}

function signup_default_class_id()
{
	global $db;

	$class = $db->super_query("SELECT id FROM priv WHERE SIGNUP = 1 ORDER BY id ASC LIMIT 1");
	if (!empty($class['id'])) {
		return (int) $class['id'];
	}

	$class = $db->super_query("SELECT id FROM priv WHERE id > 0 ORDER BY id ASC LIMIT 1");
	return (int) ($class['id'] ?? 0);
}

$signupModalError = '';
$signupBlockedMessage = '';

if(!$config['registeronline'] || $USER) {
	signup_error_response($language['signup_1'] , $language['signup_2']);
	$signupBlockedMessage = $language['signup_2'];
}

if ($isModalView) {
	$GLOBALS['LITETRACKER_HIDE_TOP_BLOCKS'] = true;
	$GLOBALS['LITETRACKER_HIDE_BOTTOM_BLOCKS'] = true;
	$GLOBALS['LITETRACKER_HIDE_STANDARD_SIDEBAR'] = true;
}

if($act === 'confirm') {
	signup_error_response('Ошибка', 'Подтверждение регистрации по e-mail отключено. Используйте обычный вход на сайт.', 1);
	if ($signupBlockedMessage === '') {
		$signupBlockedMessage = 'Подтверждение регистрации по e-mail отключено. Используйте обычный вход на сайт.';
	}
}

if($_POST && $signupBlockedMessage === '') {
	if (!lt_csrf_validate('signup_form')) {
		signup_error_response($language['default_1'], 'Защитный токен устарел. Обновите страницу и попробуйте снова.', 1);
	}

	if ($signupModalError === '') {
		$signupRateLimit = lt_rate_limit_hit('signup', $_SERVER['REMOTE_ADDR'] ?? '', 5, 15 * 60);
		if (!empty($signupRateLimit['blocked'])) {
			signup_error_response($language['default_1'], 'Слишком много попыток регистрации. Повторите попытку позже.', 1);
		}
	}

	$name = $signupName;
	$email = $signupEmail;
	$password = $signupPassword;
	$birthdayDate = null;
	$errorMessage = '';

	if($name === '' || $email === '' || $password === '') {
		$errorMessage = 'Заполните логин, E-mail и пароль.';
	} elseif (!validusername($name)) {
		$errorMessage = $language['signup_11'];
	} elseif (!validemail($email)) {
		$errorMessage = 'Введите корректный E-mail адрес.';
	} elseif (strlen($name) > 12) {
		$errorMessage = $language['signup_12'];
	} elseif (strlen($password) < 6) {
		$errorMessage = $language['signup_13'];
	} elseif (strlen($password) > 40) {
		$errorMessage = $language['signup_14'];
	} elseif ($signupBirthdayDay === '' || $signupBirthdayMonth === '' || $signupBirthdayYear === '') {
		$errorMessage = 'Укажите дату рождения.';
	} elseif (!checkdate((int) $signupBirthdayMonth, (int) $signupBirthdayDay, (int) $signupBirthdayYear)) {
		$errorMessage = 'Дата рождения заполнена неверно.';
	}

	if ($errorMessage !== '') {
		signup_error_response($language['default_1'], $errorMessage, 1);
	}

	if ($signupModalError === '') {
		$nameCheck = $db->query("SELECT * FROM users WHERE name='".$db->safesql($name)."'");
		if($db->num_rows() >= 1) {
			signup_error_response($language['default_1'], $language['signup_17'], 1);
		}
	}

	if ($signupModalError === '') {
		$emailCheck = $db->query("SELECT * FROM users WHERE email='".$db->safesql($email)."'");
		if($db->num_rows($emailCheck) >= 1) {
			signup_error_response($language['default_1'], $language['signup_16'], 1);
		}
	}

	if ($signupModalError === '') {
		$birthdayDate = sprintf('%04d-%02d-%02d', (int) $signupBirthdayYear, (int) $signupBirthdayMonth, (int) $signupBirthdayDay);
		$minimumAllowedBirthday = strtotime('-14 years');
		if ($minimumAllowedBirthday !== false && strtotime($birthdayDate) > $minimumAllowedBirthday) {
			signup_error_response($language['default_1'], 'Регистрация доступна только пользователям старше 14 лет.', 1);
		}
	}

	if ($signupModalError === '' && !$signupAgreementAccepted) {
		signup_error_response($language['default_1'], 'Подтвердите, что вам исполнилось 14 лет и вы принимаете пользовательское соглашение.', 1);
	}

	if($signupModalError === '' && $config['reCaptcha'] && $config['reCaptcha_signup']) {
		$resp = recaptcha_check_answer(
			$config['reCaptcha_privatekey'],
			$_SERVER['REMOTE_ADDR'] ?? '',
			$_POST['recaptcha_challenge_field'] ?? '',
			$_POST['recaptcha_response_field'] ?? ''
		);

		if (!$resp->is_valid) {
			signup_error_response($language['default_1'], $language['captcha_2'], 1);
		}
	}

	if ($signupModalError === '') {
		$passwordHash = lt_password_hash_value($password);

		$countUsers = $db->super_query("SELECT COUNT(*) AS c FROM users");
		$classId = ((int) ($countUsers['c'] ?? 0) > 0 ? signup_default_class_id() : signup_admin_class_id());

		$db->query("INSERT INTO users (name, avatar, email, password, password_code, ip, class, last_access, added, passkey, uploaded, downloaded, money, ".$signupBonusColumn.", sex, birthday_date, profile_text, website, icq, last_chat, num_messages, num_friends, confirm) VALUES ('".$db->safesql($name)."', '', '".$db->safesql($email)."', '".$db->safesql($passwordHash)."', '', '".ip2long_db(getip())."', '".$classId."', NOW(), NOW(), '', '0', '0', '0', '300', '1', '".$db->safesql($birthdayDate)."', '', '', '', '0', '0', '0', '1')");

		$id = (int) $db->insert_id();
		if ($id === 1) {
			$classId = signup_admin_class_id();
			$db->query("UPDATE users SET class = ".$classId." WHERE id = 1");
		}

		login_cookie($id, $passwordHash);

		if ($isModalView) {
			echo '<!doctype html><html><head><meta charset="'.$language['charset'].'"></head><body><script>if(window.parent&&window.parent!==window){window.parent.location.reload();}else{window.location.href="index.php";}</script></body></html>';
			die();
		}

		header('Location: index.php');
		die();
	}
}

if (!$isModalView) {
	head($language['signup_3'], true);
} else {
	?>
	<!doctype html>
	<html lang="ru">
	<head>
	<meta charset="<?=$language['charset'];?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link href="templates/<?=$config['template'];?>/css/my.css" rel="stylesheet" type="text/css">
	</head>
	<body class="auth-modal-frame">
	<?php
}

$signupFormAction = 'signup.php'.($isModalView ? '?modal=1' : '');
?>

<div class="auth-page signup-page<?=($isModalView ? ' auth-modal-page auth-modal-page-signup' : '');?>">
	<div class="auth-layout signup-layout<?=($isModalView ? ' auth-modal-layout' : '');?>">
		<section class="auth-card signup-card signup-form-card<?=($isModalView ? ' auth-modal-card' : '');?>">
			<h1 class="auth-title signup-title<?=($isModalView ? ' auth-modal-title' : '');?>">Регистрация</h1>
			<?php if ($signupBlockedMessage !== '') { ?>
			<div class="auth-alert signup-modal-error"><?=htmlspecialchars($signupBlockedMessage, ENT_QUOTES, 'UTF-8');?></div>
			<?php } elseif ($signupModalError !== '') { ?>
			<div class="auth-alert signup-modal-error"><?=htmlspecialchars($signupModalError, ENT_QUOTES, 'UTF-8');?></div>
			<?php } ?>
			<form action="<?=$signupFormAction;?>" class="auth-form signup-form" id="signupPage" method="post">
				<div class="auth-grid signup-grid">
					<div class="auth-field signup-field signup-field-login">
						<label class="auth-label signup-label" for="signup-name">Логин</label>
						<div class="signup-input-wrap signup-input-wrap-login">
							<input id="signup-name" type="text" name="name" value="<?=htmlspecialchars($signupName, ENT_QUOTES, 'UTF-8');?>" autocomplete="username">
						</div>
					</div>

					<div class="auth-field signup-field">
						<label class="auth-label signup-label" for="signup-email">E-mail</label>
						<div class="signup-input-wrap">
							<input id="signup-email" type="email" name="email" value="<?=htmlspecialchars($signupEmail, ENT_QUOTES, 'UTF-8');?>" autocomplete="email">
						</div>
					</div>

					<div class="auth-field signup-field">
						<label class="auth-label signup-label" for="signup-password">Пароль</label>
						<div class="signup-input-wrap">
							<input id="signup-password" type="password" name="password" value="" autocomplete="new-password">
						</div>
					</div>

					<div class="auth-field signup-field">
						<label class="auth-label signup-label">Дата рождения</label>
						<div class="auth-birthday-row signup-birthday-row">
							<select name="birthday_day">
								<option value="">день</option>
								<?php for ($day = 1; $day <= 31; $day++) { ?>
								<?php $dayValue = sprintf('%02d', $day); ?>
								<option value="<?=$dayValue;?>"<?=($signupBirthdayDay === $dayValue ? ' selected' : '');?>><?=$day;?></option>
								<?php } ?>
							</select>
							<select name="birthday_month">
								<?php foreach ($signupBirthdayMonths as $monthValue => $monthLabel) { ?>
								<option value="<?=$monthValue;?>"<?=($signupBirthdayMonth === $monthValue ? ' selected' : '');?>><?=$monthLabel;?></option>
								<?php } ?>
							</select>
							<select name="birthday_year">
								<option value="">год</option>
								<?php for ($year = (int) date('Y') - 14; $year >= 1950; $year--) { ?>
								<option value="<?=$year;?>"<?=((string) $signupBirthdayYear === (string) $year ? ' selected' : '');?>><?=$year;?></option>
								<?php } ?>
							</select>
						</div>
					</div>
				</div>

				<?php if($config['reCaptcha'] && $config['reCaptcha_signup']) { ?>
				<div class="auth-captcha-row signup-captcha-row">
					<?=recaptcha_get_html($config['reCaptcha_publickey']);?>
				</div>
				<?php } ?>

				<label class="auth-consent signup-consent">
					<input type="checkbox" name="accept_rules" value="1"<?=($signupAgreementAccepted ? ' checked' : '');?> >
					<span>Я подтверждаю, что мне уже исполнилось 14 лет и я принимаю <a href="faq.php">Пользовательское соглашение</a></span>
				</label>

				<div class="auth-submit-row signup-submit-row<?=($isModalView ? ' auth-modal-footer' : '');?>">
					<button type="submit"><?=$language['signup_3'];?></button>
				</div>
				<?=lt_csrf_input('signup_form');?>
			</form>
		</section>

		<aside class="auth-card auth-info-card signup-card signup-info-card">
			<h2 class="auth-info-title signup-info-title">Зачем мне нужно регистрироваться?</h2>
			<div class="auth-copy signup-info-copy">
				Регистрация постоянного пользователя позволяет использовать весь доступный функционал сайта. Вы сможете общаться с единомышленниками на форуме, оставлять комментарии к раздачам, скачивать понравившиеся релизы и вносить изменения в личные данные профиля.
			</div>
			<div class="auth-copy signup-info-copy signup-info-copy-last">
				Полный список действий доступен на <a href="faq.php">странице</a> часто задаваемых вопросов.
			</div>
		</aside>
	</div>
</div>

<?php
if ($isModalView) {
	echo '<script>(function(){if(window.parent===window){return;}var sendSize=function(){var d=document.documentElement;var b=document.body;var h=Math.max(d?d.scrollHeight:0,b?b.scrollHeight:0,d?d.offsetHeight:0,b?b.offsetHeight:0);window.parent.postMessage({type:"lt-auth-modal-size",height:h},window.location.origin);};window.addEventListener("load",sendSize);window.addEventListener("resize",sendSize);document.addEventListener("input",sendSize,true);document.addEventListener("change",sendSize,true);setTimeout(sendSize,0);})();</script>';
	echo '</body></html>';
} else {
	foot(true);
}
?>
