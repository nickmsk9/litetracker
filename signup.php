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

$act = trim((string) ($_GET['act'] ?? ''));
$signupName = trim((string) ($_POST['name'] ?? ''));
$signupEmail = trim((string) ($_POST['email'] ?? ''));
$signupPassword = trim((string) ($_POST['password'] ?? ''));
$signupBirthdayDay = trim((string) ($_POST['birthday_day'] ?? ''));
$signupBirthdayMonth = trim((string) ($_POST['birthday_month'] ?? ''));
$signupBirthdayYear = trim((string) ($_POST['birthday_year'] ?? ''));
$signupAgreementAccepted = !empty($_POST['accept_rules']);

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

if(!$config['registeronline'] || $USER) {
	err($language['signup_1'] , $language['signup_2']);
}

if($act === 'confirm') {
	err('Ошибка', 'Подтверждение регистрации по e-mail отключено. Используйте обычный вход на сайт.', 1);
}

if($_POST) {
	$name = $signupName;
	$email = $signupEmail;
	$password = $signupPassword;
	$birthdayDate = null;

	if($name === '' || $email === '' || $password === '') {
		err($language['default_1'], 'Заполните логин, E-mail и пароль.', 1);
	}

	if (!validusername($name)) {
		err($language['default_1'], $language['signup_11'], 1);
	}

	if (!validemail($email)) {
		err($language['default_1'], 'Введите корректный E-mail адрес.', 1);
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

	$emailCheck = $db->query("SELECT * FROM users WHERE email='".$db->safesql($email)."'");
	if($db->num_rows($emailCheck) >= 1) {
		err($language['default_1'], $language['signup_16'], 1);
	}

	if ($signupBirthdayDay === '' || $signupBirthdayMonth === '' || $signupBirthdayYear === '') {
		err($language['default_1'], 'Укажите дату рождения.', 1);
	}

	if (!checkdate((int) $signupBirthdayMonth, (int) $signupBirthdayDay, (int) $signupBirthdayYear)) {
		err($language['default_1'], 'Дата рождения заполнена неверно.', 1);
	}

	$birthdayDate = sprintf('%04d-%02d-%02d', (int) $signupBirthdayYear, (int) $signupBirthdayMonth, (int) $signupBirthdayDay);
	$minimumAllowedBirthday = strtotime('-14 years');
	if ($minimumAllowedBirthday !== false && strtotime($birthdayDate) > $minimumAllowedBirthday) {
		err($language['default_1'], 'Регистрация доступна только пользователям старше 14 лет.', 1);
	}

	if (!$signupAgreementAccepted) {
		err($language['default_1'], 'Подтвердите, что вам исполнилось 14 лет и вы принимаете пользовательское соглашение.', 1);
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

	$db->query("INSERT INTO users (name, avatar, email, password, password_code, ip, class, last_access, added, passkey, uploaded, downloaded, money, voice, sex, birthday_date, website, icq, last_chat, num_messages, num_friends, confirm) VALUES ('".$db->safesql($name)."', '', '".$db->safesql($email)."', '".$passwordHash."', '".$passwordCode."', '".ip2long_db(getip())."', '".$classId."', NOW(), NOW(), '', '0', '0', '0', '300', '1', '".$db->safesql($birthdayDate)."', '', '', '0', '0', '0', '1')");

	$id = (int) $db->insert_id();

	login_cookie($id, $passwordHash);

	header('Location: index.php');
	die();
}

head($language['signup_3'], true);
?>

<div class="signup-page">
	<div class="signup-layout">
		<section class="signup-card signup-form-card">
			<h1 class="signup-title">Регистрация</h1>
			<form action="signup.php" class="signup-form" id="signupPage" method="post">
				<div class="signup-grid">
					<div class="signup-field signup-field-login">
						<label class="signup-label" for="signup-name">Логин</label>
						<div class="signup-input-wrap signup-input-wrap-login">
							<input id="signup-name" type="text" name="name" value="<?=htmlspecialchars($signupName, ENT_QUOTES, 'UTF-8');?>" autocomplete="username">
						</div>
					</div>

					<div class="signup-field">
						<label class="signup-label" for="signup-email">E-mail</label>
						<div class="signup-input-wrap">
							<input id="signup-email" type="email" name="email" value="<?=htmlspecialchars($signupEmail, ENT_QUOTES, 'UTF-8');?>" autocomplete="email">
						</div>
					</div>

					<div class="signup-field">
						<label class="signup-label" for="signup-password">Пароль</label>
						<div class="signup-input-wrap">
							<input id="signup-password" type="password" name="password" value="" autocomplete="new-password">
						</div>
					</div>

					<div class="signup-field">
						<label class="signup-label">Дата рождения</label>
						<div class="signup-birthday-row">
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
				<div class="signup-captcha-row">
					<?=recaptcha_get_html($config['reCaptcha_publickey']);?>
				</div>
				<?php } ?>

				<label class="signup-consent">
					<input type="checkbox" name="accept_rules" value="1"<?=($signupAgreementAccepted ? ' checked' : '');?> >
					<span>Я подтверждаю, что мне уже исполнилось 14 лет и я принимаю <a href="faq.php">Пользовательское соглашение</a></span>
				</label>

				<div class="signup-submit-row">
					<button type="submit"><?=$language['signup_3'];?></button>
				</div>
			</form>
		</section>

		<aside class="signup-card signup-info-card">
			<h2 class="signup-info-title">Зачем мне нужно регистрироваться?</h2>
			<div class="signup-info-copy">
				Регистрация постоянного пользователя позволяет использовать весь доступный функционал сайта. Вы сможете общаться с единомышленниками на форуме, оставлять комментарии к раздачам, скачивать понравившиеся релизы и вносить изменения в личные данные профиля.
			</div>
			<div class="signup-info-copy signup-info-copy-last">
				Полный список действий доступен на <a href="faq.php">странице</a> часто задаваемых вопросов.
			</div>
		</aside>
	</div>
</div>

<?php
foot(true);
?>
