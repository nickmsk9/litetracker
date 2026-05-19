<?php
header('Content-Type: application/json; charset=' . $language['charset']);

if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'XMLHttpRequest') {
	http_response_code(403);
	echo json_encode(array('ok' => false), JSON_UNESCAPED_UNICODE);
	die();
}

$captcha = lt_captcha_create();
echo json_encode(array(
	'ok' => true,
	'id' => (string) $captcha['id'],
	'value' => (string) $captcha['value'],
), JSON_UNESCAPED_UNICODE);
