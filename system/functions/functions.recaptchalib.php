<?php
define("RECAPTCHA_API_SECURE_SERVER", "https://www.google.com/recaptcha/api.js");
define("RECAPTCHA_VERIFY_URL", "https://www.google.com/recaptcha/api/siteverify");

function recaptcha_get_html ($pubkey, $error = null, $use_ssl = false)
{
	if ($pubkey == null || $pubkey == '') {
		return '<div class="auth-alert">reCAPTCHA не настроена: отсутствует публичный ключ.</div>';
	}

	$siteKey = htmlspecialchars((string) $pubkey, ENT_QUOTES, 'UTF-8');
	return '<script src="'.RECAPTCHA_API_SECURE_SERVER.'" async defer></script><div class="g-recaptcha" data-sitekey="'.$siteKey.'"></div>';
}

class ReCaptchaResponse {
        var $is_valid;
        var $error;
}

function recaptcha_check_answer ($privkey, $remoteip, $challenge, $response, $extra_params = array())
{
	$recaptcha_response = new ReCaptchaResponse();
	$recaptcha_response->is_valid = false;
	$recaptcha_response->error = '';

	if ($privkey == null || $privkey == '') {
		$recaptcha_response->error = 'missing-private-key';
		return $recaptcha_response;
	}

	if ($remoteip == null || $remoteip == '') {
		$recaptcha_response->error = 'missing-remote-ip';
		return $recaptcha_response;
	}

	$captchaToken = trim((string) ($_POST['g-recaptcha-response'] ?? $response ?? ''));
	if ($captchaToken === '') {
		$recaptcha_response->error = 'missing-input-response';
		return $recaptcha_response;
	}

	$postData = array(
		'secret' => $privkey,
		'remoteip' => $remoteip,
		'response' => $captchaToken,
	) + $extra_params;

	$context = stream_context_create(array(
		'http' => array(
			'method' => 'POST',
			'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
			'content' => http_build_query($postData),
			'timeout' => 10,
		),
	));

	$verifyResponse = @file_get_contents(RECAPTCHA_VERIFY_URL, false, $context);
	if ($verifyResponse === false) {
		$recaptcha_response->error = 'verify-request-failed';
		return $recaptcha_response;
	}

	$decodedResponse = json_decode($verifyResponse, true);
	if (is_array($decodedResponse) && !empty($decodedResponse['success'])) {
		$recaptcha_response->is_valid = true;
		return $recaptcha_response;
	}

	if (is_array($decodedResponse) && !empty($decodedResponse['error-codes'])) {
		$recaptcha_response->error = implode(',', (array) $decodedResponse['error-codes']);
	} else {
		$recaptcha_response->error = 'incorrect-captcha-sol';
	}

	return $recaptcha_response;
}
