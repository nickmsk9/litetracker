<?php
define('LT_CAPTCHA_SESSION_KEY', 'lt_local_captcha');
define('LT_CAPTCHA_FIELD_ID', 'lt_captcha_id');
define('LT_CAPTCHA_FIELD_ANSWER', 'lt_captcha_answer');
define('LT_CAPTCHA_TTL', 10 * 60);
define('LT_CAPTCHA_LENGTH', 5);

class ReCaptchaResponse {
	var $is_valid;
	var $error;
}

if (!function_exists('lt_captcha_random_string')) {
	function lt_captcha_random_string($length = LT_CAPTCHA_LENGTH)
	{
		$alphabet = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
		$alphabetLength = strlen($alphabet);
		$result = '';

		for ($i = 0; $i < (int) $length; $i++) {
			if (function_exists('random_int')) {
				$index = random_int(0, $alphabetLength - 1);
			} else {
				$index = mt_rand(0, $alphabetLength - 1);
			}

			$result .= $alphabet[$index];
		}

		return $result;
	}
}

if (!function_exists('lt_captcha_create')) {
	function lt_captcha_create()
	{
		if (function_exists('lt_session_resume')) {
			lt_session_resume();
		}

		if (!isset($_SESSION[LT_CAPTCHA_SESSION_KEY]) || !is_array($_SESSION[LT_CAPTCHA_SESSION_KEY])) {
			$_SESSION[LT_CAPTCHA_SESSION_KEY] = array();
		}

		$now = time();
		foreach ((array) $_SESSION[LT_CAPTCHA_SESSION_KEY] as $captchaId => $captchaMeta) {
			$expiresAt = (int) ($captchaMeta['expires'] ?? 0);
			if ($expiresAt <= $now) {
				unset($_SESSION[LT_CAPTCHA_SESSION_KEY][$captchaId]);
			}
		}

		if (function_exists('random_bytes')) {
			$captchaId = bin2hex(random_bytes(8));
		} else {
			$captchaId = md5(microtime(true).mt_rand(1, 999999).uniqid('', true));
		}

		$captchaValue = lt_captcha_random_string(LT_CAPTCHA_LENGTH);
		$_SESSION[LT_CAPTCHA_SESSION_KEY][$captchaId] = array(
			'answer' => strtolower($captchaValue),
			'expires' => $now + LT_CAPTCHA_TTL,
		);

		if (function_exists('lt_session_commit')) {
			lt_session_commit();
		}

		return array(
			'id' => $captchaId,
			'value' => $captchaValue,
		);
	}
}

if (!function_exists('lt_captcha_get_html')) {
	function lt_captcha_get_html($scope = 'default')
	{
		$captcha = lt_captcha_create();
		$captchaId = htmlspecialchars((string) $captcha['id'], ENT_QUOTES, 'UTF-8');
		$captchaValue = htmlspecialchars((string) $captcha['value'], ENT_QUOTES, 'UTF-8');

		$html = '<div class="lt-captcha">';
		$html .= '<div class="lt-captcha-value" style="display:inline-block;padding:8px 12px;border:1px dashed #7f8c8d;border-radius:6px;letter-spacing:3px;font-weight:700;font-size:20px;user-select:none;">'.$captchaValue.'</div>';
		$html .= '<input type="hidden" name="'.LT_CAPTCHA_FIELD_ID.'" value="'.$captchaId.'">';
		$html .= '<div style="margin-top:8px;"><label for="lt-captcha-answer">Введите символы с картинки</label></div>';
		$html .= '<input id="lt-captcha-answer" type="text" name="'.LT_CAPTCHA_FIELD_ANSWER.'" value="" maxlength="'.(int) LT_CAPTCHA_LENGTH.'" autocomplete="off" required>';
		$html .= '</div>';

		return $html;
	}
}

if (!function_exists('lt_captcha_check_answer')) {
	function lt_captcha_check_answer($challenge = '', $response = '')
	{
		$captchaResponse = new ReCaptchaResponse();
		$captchaResponse->is_valid = false;
		$captchaResponse->error = '';

		$captchaId = trim((string) ($_POST[LT_CAPTCHA_FIELD_ID] ?? $challenge ?? ''));
		$captchaAnswer = strtolower(trim((string) ($_POST[LT_CAPTCHA_FIELD_ANSWER] ?? $response ?? '')));

		if ($captchaId === '' || $captchaAnswer === '') {
			$captchaResponse->error = 'missing-input-response';
			return $captchaResponse;
		}

		if (function_exists('lt_session_resume')) {
			lt_session_resume();
		}

		$captchaMeta = (array) ($_SESSION[LT_CAPTCHA_SESSION_KEY][$captchaId] ?? array());
		unset($_SESSION[LT_CAPTCHA_SESSION_KEY][$captchaId]);

		if (function_exists('lt_session_commit')) {
			lt_session_commit();
		}

		$expected = strtolower(trim((string) ($captchaMeta['answer'] ?? '')));
		$expiresAt = (int) ($captchaMeta['expires'] ?? 0);
		if ($expected === '' || $expiresAt <= time()) {
			$captchaResponse->error = 'challenge-expired';
			return $captchaResponse;
		}

		if (!hash_equals($expected, $captchaAnswer)) {
			$captchaResponse->error = 'incorrect-captcha-sol';
			return $captchaResponse;
		}

		$captchaResponse->is_valid = true;
		return $captchaResponse;
	}
}

function recaptcha_get_html($pubkey = null, $error = null, $use_ssl = false)
{
	return lt_captcha_get_html('legacy');
}

function recaptcha_check_answer($privkey, $remoteip, $challenge, $response, $extra_params = array())
{
	return lt_captcha_check_answer($challenge, $response);
}
