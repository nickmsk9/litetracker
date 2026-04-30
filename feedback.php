<?php
/*
===================================================================
LiteTracker Source
-------------------------------------------------------------------
Назначение: Обратная связь
===================================================================
*/

require 'system/init.php';

$feedbackTopics = array(
	'auth' => 'Проблемы с авторизацией и регистрацией',
	'ideas' => 'Предложения и пожелания',
	'bugs' => 'Ошибки на сайте',
	'ads' => 'Реклама на сайте',
	'other' => 'Прочее',
);

function feedback_response($ok, $message)
{
	$isAjax = (
		(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
		|| strpos((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json') !== false
	);

	if ($isAjax) {
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(
			'ok' => (bool) $ok,
			'message' => (string) $message,
		));
		die();
	}

	if ($ok) {
		header('Location: index.php?feedback=sent');
		die();
	}

	err('Ошибка', $message, 1);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	header('Location: index.php');
	die();
}

if (!lt_csrf_validate('feedback_form')) {
	feedback_response(false, 'Защитный токен устарел. Обновите страницу и попробуйте снова.');
}

$topicKey = trim((string) ($_POST['topic'] ?? ''));
$message = trim((string) ($_POST['message'] ?? ''));

if (empty($feedbackTopics[$topicKey])) {
	feedback_response(false, 'Выберите тему обращения.');
}

if ($message === '') {
	feedback_response(false, 'Введите сообщение.');
}

if (function_exists('mb_strlen') && mb_strlen($message, 'UTF-8') > 4000) {
	feedback_response(false, 'Сообщение слишком длинное.');
}

$senderName = (!empty($USER['name']) ? (string) $USER['name'] : 'Гость');
$senderId = (!empty($USER['id']) ? (int) $USER['id'] : 0);
$subject = 'Обратная связь: '.$feedbackTopics[$topicKey];
$text = 'Тема: [b]'.$feedbackTopics[$topicKey].'[/b]'."\n";
$text .= 'Отправитель: [b]'.$senderName.'[/b]'.($senderId > 0 ? ' (ID '.$senderId.')' : '')."\n";
$text .= 'IP: '.getip()."\n\n";
$text .= $message;

$sql = $db->query(
	"SELECT DISTINCT u.id
	 FROM users AS u
	 INNER JOIN priv AS p ON p.id = u.class
	 WHERE p.EDIT_PRIV = 1
	    OR p.setting_user = 1
	    OR p.comments_edit = 1"
);

$sent = 0;
while ($row = $db->get_row($sql)) {
	if (send_msg($subject, $text, (int) $row['id'], $senderId)) {
		$sent++;
	}
}
$db->free($sql);

feedback_response($sent > 0, ($sent > 0 ? 'Сообщение отправлено администрации.' : 'Не найден получатель обращения.'));
?>
