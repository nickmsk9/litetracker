<?php
/*
===================================================================
LiteTracker Source
===================================================================
by Nick
-------------------------------------------------------------------
Назначение: Авторизация
===================================================================
*/

//Подключаем главный системный файл
require __DIR__ . '/app/system/init.php';
require_once __DIR__ . '/app/system/functions/functions.recaptchalib.php';

function login_normalize_referer($referer)
{
	$referer = trim((string) $referer);
	if ($referer === '') {
		return 'index.php';
	}

	$referer = ltrim($referer, '/');
	if ($referer === '' || strpos($referer, "\0") !== false) {
		return 'index.php';
	}

	if (preg_match('~^(?:https?:)?//~i', $referer)) {
		return 'index.php';
	}

	if (preg_match('~^login\.php(?:[/?]|$)~i', $referer)) {
		return 'index.php';
	}

	return $referer;
}

$op = (string) ($_GET['op'] ?? '');
$step = isset($_GET['step']) ? (int) $_GET['step'] : 0;
$ok = !empty($_GET['ok']);
$code = trim((string) ($_GET['code'] ?? ''));
$isModalView = ((int) ($_GET['modal'] ?? 0) === 1);
$loginModalError = '';
$loginModalNotice = '';

function login_error_response($title, $message, $goBack = 1)
{
	global $isModalView, $loginModalError;

	if (!$isModalView) {
		err($title, $message, $goBack);
	}

	$loginModalError = (string) $message;

	return false;
}

function login_render_start($title)
{
	global $isModalView, $language, $config;

	if (!$isModalView) {
		head($title, true);
		return;
	}

	echo '<!doctype html><html lang="ru"><head><meta charset="'.htmlspecialchars($language['charset'], ENT_QUOTES, 'UTF-8').'"><meta name="viewport" content="width=device-width, initial-scale=1"><link href="public/templates/'.htmlspecialchars($config['template'], ENT_QUOTES, 'UTF-8').'/css/my.css" rel="stylesheet" type="text/css"></head><body class="auth-modal-frame">';
}

function login_render_end()
{
	global $isModalView;

	if ($isModalView) {
		echo '<script>(function(){if(window.parent===window){return;}var sendSize=function(){var card=document.querySelector(".auth-card");var page=document.querySelector(".auth-page");var target=card||page||document.body;var rect=target&&target.getBoundingClientRect?target.getBoundingClientRect():null;var h=rect?Math.ceil(rect.height)+1:Math.max(document.documentElement.scrollHeight,document.body.scrollHeight);window.parent.postMessage({type:"lt-auth-modal-size",height:h},window.location.origin);};window.addEventListener("load",sendSize);window.addEventListener("resize",sendSize);document.addEventListener("input",sendSize,true);document.addEventListener("change",sendSize,true);setTimeout(sendSize,0);})();</script>';
		echo '</body></html>';
		return;
	}

	foot(true);
}

function login_modal_success_redirect($target)
{
	$target = login_normalize_referer($target);
	$targetJson = json_encode($target, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

	echo '<!doctype html><html><head><meta charset="utf-8"></head><body><script>(function(){var target='.$targetJson.'||"index.php";if(window.parent&&window.parent!==window){window.parent.location.href=target;}else{window.location.href=target;}})();</script></body></html>';
	die();
}

function login_form_action($params = array())
{
	global $isModalView;

	$params = (is_array($params) ? $params : array());
	if ($isModalView) {
		$params['modal'] = 1;
	}

	$query = http_build_query($params);

	return 'login.php'.($query !== '' ? '?'.$query : '');
}


if($USER) {
	if ($isModalView) {
		login_modal_success_redirect($_GET['referer'] ?? 'index.php');
	}

	err($language['default_1'] , $language['default_6']  , 1);
}

/////////////////////////////////////////////////////////////////////
//Забыли пароль
/////////////////////////////////////////////////////////////////////
if($op == 'forgot') {
	$forgotCaptchaEnabled = (!empty($config['captcha']) && !empty($config['reCaptcha_login']));
	$forgotReferer = login_normalize_referer($_GET['referer'] ?? $_POST['referer'] ?? '');
	$forgotNotice = 'Если такой аккаунт существует, инструкция по восстановлению будет отправлена на e-mail.';

	//Если не включена функция отправки писем , завершаем работу
	if(!$config['mail']['use'])  {
		login_error_response('Ошибка' , 'Администрация отключила данный сервис' , 1);
	}

	if($ok) {
		if ($isModalView) {
			$loginModalNotice = $forgotNotice;
		} else {
			err('Успешно' , $forgotNotice , 0 , 'success');
		}
	}

	if($_POST) {
		if (!lt_csrf_validate('login_forgot')) {
			login_error_response($language['default_1'], 'Защитный токен устарел. Обновите страницу и попробуйте снова.', 1);
		}

		if ($loginModalError === '') {
			$forgotRateLimit = lt_rate_limit_hit('login_forgot', $_SERVER['REMOTE_ADDR'] ?? '', 5, 15 * 60);
			if (!empty($forgotRateLimit['blocked'])) {
				login_error_response($language['default_1'], 'Слишком много запросов на восстановление пароля. Повторите попытку позже.', 1);
			}
		}

		$email = trim((string) ($_POST['email'] ?? ''));
		if($email === ''){
			login_error_response('Ошибка' , 'Вы ничего не ввели' , 1);
		}

		if ($loginModalError === '' && !validemail($email) ) {
			login_error_response($language['default_1']  , 'E-mail введен не верно' , 1);
		}

		if ($loginModalError === '' && $forgotCaptchaEnabled) {
			$resp = lt_captcha_check_answer();
			if (!$resp->is_valid) {
				login_error_response($language['default_1'], $language['captcha_2'], 1);
			}
		}

		if ($loginModalError === '') {
			$arr = $db->psuper_query("SELECT id, name, email FROM users WHERE email = ? LIMIT 1", 's', [$email]);
			if ($arr && !empty($arr['id'])) {
				$plainToken = bin2hex(random_bytes(32));
				$tokenHash = hash('sha256', $plainToken);
				$expiresAt = date('Y-m-d H:i:s', time() + 60 * 60 * 2);
				$ip = substr((string) getip(), 0, 45);
				$userAgent = substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);

				$db->pquery(
					"UPDATE password_reset_tokens SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL",
					'i',
					[(int) $arr['id']]
				);
				$db->pquery(
					"INSERT INTO password_reset_tokens (user_id, token_hash, expires_at, ip, user_agent) VALUES (?, ?, ?, ?, ?)",
					'issss',
					[(int) $arr['id'], $tokenHash, $expiresAt, $ip, $userAgent]
				);

				$announceUrl = trim((string) ($config['announce_url'] ?? ''));
				$announceParts = parse_url($announceUrl);
				$scheme = (!empty($announceParts['scheme']) ? $announceParts['scheme'] : (lt_is_https_request() ? 'https' : 'http'));
				$host = (!empty($announceParts['host']) ? $announceParts['host'] : trim((string) ($_SERVER['HTTP_HOST'] ?? '')));
				$port = (!empty($announceParts['port']) ? ':'.(int) $announceParts['port'] : '');
				$origin = $scheme.'://'.$host.$port;
				$resetLink = $origin.'/login.php?op=reset&token='.rawurlencode($plainToken);

				$body = '';
				$body .= "Здравствуйте, вы запросили восстановление пароля на трекере ".$host."\n\r";
				$body .= "Для смены пароля откройте ссылку ниже:\n\r";
				$body .= $resetLink."\n\r";
				$body .= "Ссылка действует 2 часа и используется только один раз.\n\r";
				$body .= "Если вы не запрашивали восстановление, просто проигнорируйте письмо.\n\r";

				$mail = new phpmailer;
				$mail->AddAddress($arr['email'], $arr['name']);
				$mail->Subject = $host.'.Support';
				$mail->Body = $body;
				$mail->Send();
			}

			header('Location:'.login_form_action(array('op' => 'forgot', 'ok' => 1, 'referer' => $forgotReferer)));
			die();
		}
	}

	login_render_start('Восстановление доступа');
	?>
	<div class="auth-page login-page">
		<div class="auth-layout auth-layout-single login-layout">
			<section class="auth-card auth-card-compact login-card">
				<h1 class="auth-title login-title">Восстановление доступа</h1>
				<div class="auth-copy auth-copy-lead"><strong>Для восстановления доступа к аккаунту укажите e-mail, на который он был зарегистрирован.</strong> Мы отправим вам письмо с инструкциями по сбросу пароля.</div>
				<div class="auth-copy">Если такой аккаунт существует, инструкция по восстановлению будет отправлена на e-mail.</div>
				<?php if ($loginModalNotice !== '') { ?>
				<div class="auth-alert auth-alert-success"><?=htmlspecialchars($loginModalNotice, ENT_QUOTES, 'UTF-8');?></div>
				<?php } elseif ($loginModalError !== '') { ?>
				<div class="auth-alert"><?=htmlspecialchars($loginModalError, ENT_QUOTES, 'UTF-8');?></div>
				<?php } ?>
				<form action="<?=login_form_action(array('op' => 'forgot', 'referer' => $forgotReferer));?>" class="auth-form login-form" method="post">
					<div class="auth-grid auth-grid-single">
						<div class="auth-field login-field">
							<label class="auth-label login-label" for="forgot-email">E-mail</label>
							<input id="forgot-email" type="email" name="email" value="<?=htmlspecialchars((string) ($_POST['email'] ?? ''), ENT_QUOTES, 'UTF-8');?>" autocomplete="email">
						</div>
						<?php if ($forgotCaptchaEnabled) { ?>
						<div class="auth-field login-field">
							<label class="auth-label login-label">Введите код</label>
							<div class="auth-captcha-row login-captcha-row">
								<?=lt_captcha_get_html('login');?>
							</div>
						</div>
						<?php } ?>
					</div>

					<div class="auth-footer login-footer">
						<button type="submit">Отправить письмо</button>
						<a class="auth-link login-forgot-link" href="<?=htmlspecialchars(login_form_action(array('referer' => $forgotReferer)), ENT_QUOTES, 'UTF-8');?>">Вернуться ко входу</a>
					</div>
					<input type="hidden" name="referer" value="<?=htmlspecialchars($forgotReferer, ENT_QUOTES, 'UTF-8');?>">
					<?=lt_csrf_input('login_forgot');?>
				</form>
			</section>
		</div>
	</div>
	<?php
	login_render_end();
	die();
}

if($op == 'reset') {
	$token = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));
	$password = trim((string) ($_POST['password'] ?? ''));
	$passwordRepeat = trim((string) ($_POST['password_repeat'] ?? ''));

	if ($token === '') {
		login_error_response('Ошибка', 'Ссылка для восстановления недействительна или устарела.', 1);
	}

	if($_POST) {
		if (!lt_csrf_validate('login_reset')) {
			login_error_response($language['default_1'], 'Защитный токен устарел. Обновите страницу и попробуйте снова.', 1);
		}

		if ($password === '' || $passwordRepeat === '') {
			login_error_response('Ошибка', 'Введите новый пароль и подтверждение.', 1);
		}

		if (strlen($password) < 6 || strlen($password) > 40) {
			login_error_response('Ошибка', 'Пароль должен быть длиной от 6 до 40 символов.', 1);
		}

		if ($password !== $passwordRepeat) {
			login_error_response('Ошибка', 'Пароли не совпадают.', 1);
		}

		$tokenHash = hash('sha256', $token);
		$tokenRow = $db->psuper_query(
			"SELECT id, user_id FROM password_reset_tokens WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW() LIMIT 1",
			's',
			[$tokenHash]
		);

		if (!$tokenRow || empty($tokenRow['id'])) {
			login_error_response('Ошибка', 'Ссылка для восстановления недействительна или устарела.', 1);
		}

		$newPasswordHash = lt_password_hash_value($password);
		$db->pquery("UPDATE users SET password = ?, password_code = '' WHERE id = ? LIMIT 1", 'si', [$newPasswordHash, (int) $tokenRow['user_id']]);
		$db->pquery("UPDATE password_reset_tokens SET used_at = NOW() WHERE id = ? LIMIT 1", 'i', [(int) $tokenRow['id']]);
		lt_cache_invalidate_user((int) $tokenRow['user_id']);
		logout_cookie();

		err('Успешно', 'Пароль изменён. Войдите в аккаунт заново. <br><a href="login.php">Войти</a>', 0, 'success');
	}

	$tokenHash = hash('sha256', $token);
	$tokenRow = $db->psuper_query(
		"SELECT id FROM password_reset_tokens WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW() LIMIT 1",
		's',
		[$tokenHash]
	);
	if (!$tokenRow || empty($tokenRow['id'])) {
		login_error_response('Ошибка', 'Ссылка для восстановления недействительна или устарела.', 1);
	}

	login_render_start('Смена пароля');
	?>
	<div class="auth-page login-page">
		<div class="auth-layout auth-layout-single login-layout">
			<section class="auth-card auth-card-compact login-card">
				<h1 class="auth-title login-title">Смена пароля</h1>
				<div class="auth-copy">Введите новый пароль для вашего аккаунта.</div>
				<?php if ($loginModalError !== '') { ?>
				<div class="auth-alert"><?=htmlspecialchars($loginModalError, ENT_QUOTES, 'UTF-8');?></div>
				<?php } ?>
				<form action="<?=login_form_action(array('op' => 'reset', 'token' => $token));?>" class="auth-form login-form" method="post">
					<div class="auth-grid auth-grid-single">
						<div class="auth-field login-field">
							<label class="auth-label login-label" for="reset-password">Новый пароль</label>
							<input id="reset-password" type="password" name="password" value="" autocomplete="new-password">
						</div>
						<div class="auth-field login-field">
							<label class="auth-label login-label" for="reset-password-repeat">Подтверждение пароля</label>
							<input id="reset-password-repeat" type="password" name="password_repeat" value="" autocomplete="new-password">
						</div>
					</div>
					<div class="auth-footer login-footer">
						<button type="submit">Сменить пароль</button>
					</div>
					<input type="hidden" name="token" value="<?=htmlspecialchars($token, ENT_QUOTES, 'UTF-8');?>">
					<?=lt_csrf_input('login_reset');?>
				</form>
			</section>
		</div>
	</div>
	<?php
	login_render_end();
	die();
}

	/////////////////////////////////////////////////////////////////////
	//Обработка данных
/////////////////////////////////////////////////////////////////////
if($_POST) {
	if (!lt_csrf_validate('login_form')) {
		login_error_response($language['default_1'], 'Защитный токен устарел. Обновите страницу и попробуйте снова.', 1);
	}

	if ($loginModalError === '') {
		$loginRateLimit = lt_rate_limit_hit('login', $_SERVER['REMOTE_ADDR'] ?? '', 10, 5 * 60);
		if (!empty($loginRateLimit['blocked'])) {
			login_error_response($language['default_1'], 'Слишком много попыток входа. Повторите попытку позже.', 1);
		}
	}

	$login = trim((string) ($_POST['login'] ?? ''));	//E-mail адрес
	$password = trim((string) ($_POST['password'] ?? '')); //Пароль
	$referer = login_normalize_referer($_POST['referer'] ?? ''); //Реферер

	//Проверяем , введены ли данные
	if(empty($login) || empty($password) ) {
		login_error_response($language['default_1'] , $language['login_7'] , 1);
	}

	//Выполняем запрос к базе данных
	if ($loginModalError === '') {
		$arr = $db->psuper_query("SELECT * FROM users WHERE email = ? OR name = ?", 'ss', [$login, $login]);
		if(!$arr) {
			login_error_response($language['default_1'] , $language['login_8'] , 1);
		}
	}

	//Хеш пароля
	if ($loginModalError === '') {
		$passwordNeedsRehash = false;
		$password_hash = (string) ($arr['password'] ?? '');
	}

	//Проверяем пароль
	if($loginModalError === '' && !lt_password_verify_user($password, $arr, $passwordNeedsRehash)) {
		login_error_response($language['default_1'] ,  $language['login_8'] , 1);
	}

	//Забанен ли пользователь
	if($loginModalError === '' && $arr['banned']) {
		login_error_response($language['default_1'] ,  $language['login_9'] , 1);
	}



	//Защитный код
	if($loginModalError === '' && !empty($config['captcha']) && $config['reCaptcha_login']) {
		$resp = lt_captcha_check_answer();

		if (!$resp->is_valid) {
			// What happens when the CAPTCHA was entered incorrectly
			login_error_response($language['default_1'] , $language['captcha_2'] , 1);
		}
	}

	//Подтвердил ли регистрацию
	if($loginModalError === '' && !$arr['confirm']) {
		login_error_response($language['default_1']  , $language['login_10']);
	}

	if ($loginModalError === '') {
		if (!empty($passwordNeedsRehash)) {
			$password_hash = lt_password_hash_value($password);
			$db->pquery("UPDATE users SET password=?, password_code='' WHERE id=?", 'si', [$password_hash, (int) $arr['id']]);
			$arr['password'] = $password_hash;
		}

		//Удаляем кеш
		lt_cache_invalidate_user($arr['id']);

		//Определяем cookies
		logout_cookie();
		login_cookie($arr['id'] , $password_hash );

		//Переадресация
		if ($isModalView) {
			login_modal_success_redirect($referer);
		}

		if (!empty($referer) )  {
			header('Location:'.$referer);
		}else{
			header('Location:index.php');
		}

		die();
	}
}

//Определяем реферер
$referer = login_normalize_referer($_GET['referer'] ?? '');

$loginValue = htmlspecialchars((string) ($_POST['login'] ?? ''), ENT_QUOTES, 'UTF-8');
$loginFormAction = login_form_action();
$forgotHref = login_form_action(array('op' => 'forgot', 'referer' => $referer));

//Заголовок
login_render_start($language['login_1']);

?>

<div class="auth-page login-page<?=($isModalView ? ' auth-modal-page auth-modal-page-login' : '');?>">
	<div class="auth-layout auth-layout-single login-layout<?=($isModalView ? ' auth-modal-layout' : '');?>">
	<section class="auth-card auth-card-compact login-card<?=($isModalView ? ' auth-modal-card' : '');?>">
		<h1 class="auth-title login-title<?=($isModalView ? ' auth-modal-title' : '');?>">Вход на сайт</h1>
		<?php if ($loginModalError !== '') { ?>
		<div class="auth-alert"><?=htmlspecialchars($loginModalError, ENT_QUOTES, 'UTF-8');?></div>
		<?php } ?>
		<form action="<?=$loginFormAction;?>" class="auth-form login-form" id="loginPage" method="post">
			<div class="auth-grid auth-grid-single login-fields">
				<div class="auth-field login-field">
					<label class="auth-label login-label" for="login-name"><?=$language['login_3'];?></label>
					<input id="login-name" type="text" name="login" value="<?=$loginValue;?>" autocomplete="username">
				</div>
				<div class="auth-field login-field">
					<label class="auth-label login-label" for="login-password"><?=$language['login_4'];?></label>
					<input id="login-password" type="password" name="password" value="" autocomplete="current-password">
				</div>

				<?php if(!empty($config['captcha']) && $config['reCaptcha_login']) { ?>
				<div class="auth-captcha-row login-captcha-row">
					<?=lt_captcha_get_html('login');?>
				</div>
				<?php } ?>
			</div>

			<div class="auth-footer login-footer<?=($isModalView ? ' auth-modal-footer' : '');?>">
				<button type="submit"><?=$language['login_5'];?></button>
				<span class="auth-footer-separator login-footer-separator<?=($isModalView ? ' auth-modal-separator' : '');?>">или</span>
				<a class="auth-link login-forgot-link" href="<?=$forgotHref;?>"><?=$language['login_6'];?></a>
			</div>
			<input type="hidden" value="<?=$referer;?>" name="referer">
			<?=lt_csrf_input('login_form');?>
		</form>
	</section>
	</div>
</div>


<?php
//Подвал
login_render_end();
?>
