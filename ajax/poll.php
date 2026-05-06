<?php
require '../system/init.php';

header('Content-Type: application/json; charset=UTF-8');
echo json_encode(array(
	'ok' => 0,
	'enabled' => 0,
	'html' => '',
	'message' => 'Опросы временно отключены.',
), JSON_UNESCAPED_UNICODE);
die();
?>
