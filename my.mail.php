<?php
/*
===================================================================
LiteTracker Source
===================================================================
by Nick
-------------------------------------------------------------------
Назначение: Мои сообщения
===================================================================
*/

require __DIR__ . '/app/system/init.php';
require_once __DIR__ . '/app/system/functions/functions.notifications.php';

is_login();

function mail_build_href($act = 'list', $targetUserId = 0, $system = false, $extra = array())
{
	$params = array('act' => $act);

	if ($targetUserId > 0) {
		$params['id_user'] = (int) $targetUserId;
	}

	if ($system) {
		$params['system'] = 1;
	}

	foreach ($extra as $key => $value) {
		if ($value === null || $value === '') {
			continue;
		}

		$params[$key] = $value;
	}

	return 'my.mail.php?'.http_build_query($params);
}

function mail_is_ajax_request()
{
	return (strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest');
}

function mail_json_response($ok, $message = '', $extra = array())
{
	header('Content-Type: application/json; charset=UTF-8');
	$payload = array(
		'ok' => ($ok ? 1 : 0),
		'message' => (string) $message,
	);
	foreach ((array) $extra as $key => $value) {
		$payload[$key] = $value;
	}
	echo json_encode($payload, JSON_UNESCAPED_UNICODE);
	die();
}

function mail_action_error($message, $status = 400)
{
	if (mail_is_ajax_request()) {
		api_json_error($message, (int) $status);
	}

	err('Ошибка', $message, 1);
}

function mail_require_post_action()
{
	if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST') {
		return;
	}

	if (mail_is_ajax_request()) {
		api_json_error('Метод запроса не поддерживается.', 405);
	}

	header('Location: '.mail_build_href('list', 0, false, array('status' => 6)));
	die();
}

function mail_require_csrf_action()
{
	if (lt_csrf_validate('mail_action')) {
		return;
	}

	mail_action_error('Защитный токен устарел. Обновите страницу и попробуйте снова.', 403);
}

function mail_rate_limit_send()
{
	global $USER;

	$identifier = (!empty($USER['id']) ? 'user:'.(int) $USER['id'] : 'ip:'.($_SERVER['REMOTE_ADDR'] ?? 'cli'));
	$result = lt_rate_limit_hit('mail_send', $identifier, 10, 60);
	if (!empty($result['blocked'])) {
		$retryAfter = max(1, (int) ($result['reset_at'] ?? time()) - time());
		header('Retry-After: '.$retryAfter);
		mail_action_error('Слишком много сообщений. Повторите попытку позже.', 429);
	}

	return $result;
}

function mail_partner_id($message, $currentUserId)
{
	$currentUserId = (int) $currentUserId;
	return ((int) $message['id_user_in'] === $currentUserId ? (int) $message['id_user_out'] : (int) $message['id_user_in']);
}

function mail_avatar_path($user)
{
	$defaultAvatar = 'public/images/default_avatar.gif';
	if (empty($user) || empty($user['avatar'])) {
		return $defaultAvatar;
	}

	if (is_file('public/avatars/small/'.$user['avatar'])) {
		return 'public/avatars/small/'.$user['avatar'];
	}

	if (is_file('public/avatars/'.$user['avatar'])) {
		return 'public/avatars/'.$user['avatar'];
	}

	return $defaultAvatar;
}

function mail_preview_text($text, $limit = 160)
{
	$text = trim(strip_tags(format_comment((string) $text)));
	$text = preg_replace('~\s+~u', ' ', $text);

	if ($text === '') {
		return '';
	}

	if (function_exists('mb_strlen') && function_exists('mb_substr')) {
		if (mb_strlen($text, 'UTF-8') > $limit) {
			return mb_substr($text, 0, $limit, 'UTF-8').'...';
		}

		return $text;
	}

	if (strlen($text) > $limit) {
		return substr($text, 0, $limit).'...';
	}

	return $text;
}

function mail_plural($count, $one, $two, $five)
{
	$count = abs((int) $count) % 100;
	$last = $count % 10;

	if ($count > 10 && $count < 20) {
		return $five;
	}

	if ($last > 1 && $last < 5) {
		return $two;
	}

	if ($last == 1) {
		return $one;
	}

	return $five;
}

function mail_conversation_where($currentUserId, $targetUserId = 0, $system = false, $alias = 'm')
{
	$currentUserId = (int) $currentUserId;
	$targetUserId = (int) $targetUserId;
	$prefix = ($alias !== '' ? $alias.'.' : '');

	if ($system) {
		return "({$prefix}id_user_in = {$currentUserId} AND {$prefix}id_user_out = 0 AND {$prefix}delete_in = 0)";
	}

	return "((".$prefix."id_user_in = {$currentUserId} AND ".$prefix."id_user_out = {$targetUserId} AND ".$prefix."delete_in = 0) OR (".$prefix."id_user_out = {$currentUserId} AND ".$prefix."id_user_in = {$targetUserId} AND ".$prefix."delete_out = 0))";
}

function mail_invalidate_user_cache($userId)
{
	$userId = (int) $userId;
	if ($userId <= 0) {
		return;
	}

	if (function_exists('lt_cache_invalidate_user')) {
		lt_cache_invalidate_user($userId);
	}
	if (function_exists('lt_cache_invalidate_user_unread_mail_count')) {
		lt_cache_invalidate_user_unread_mail_count($userId);
	}

	global $memcached;
	if (is_object($memcached) && method_exists($memcached, 'delete')) {
		$memcached->delete('user_'.$userId, 0);
	}
}

function mail_load_message($messageId, $currentUserId)
{
	global $db;

	$messageId = (int) $messageId;
	$currentUserId = (int) $currentUserId;

	if ($messageId <= 0) {
		return false;
	}

	return $db->super_query("SELECT * FROM mail WHERE id = {$messageId} AND (id_user_in = {$currentUserId} OR id_user_out = {$currentUserId}) LIMIT 1");
}

function mail_mark_system_read($currentUserId)
{
	global $db;

	$currentUserId = (int) $currentUserId;
	if ($currentUserId <= 0) {
		return 0;
	}

	$unread = $db->super_query("SELECT COUNT(*) AS c FROM mail WHERE id_user_in = {$currentUserId} AND id_user_out = 0 AND delete_in = 0 AND reading = 0");
	$unreadCount = (int) ($unread['c'] ?? 0);

	if ($unreadCount > 0) {
		$db->query("UPDATE mail SET reading = '1' WHERE id_user_in = {$currentUserId} AND id_user_out = 0 AND delete_in = 0 AND reading = 0");
	}

	lt_sync_user_unread_messages($currentUserId);

	return $unreadCount;
}

function mail_mark_conversation_read($currentUserId, $targetUserId, $systemConversation = false)
{
	global $db, $USER;

	$currentUserId = (int) $currentUserId;
	$targetUserId = (int) $targetUserId;
	if ($currentUserId <= 0) {
		return 0;
	}

	if ($systemConversation) {
		return mail_mark_system_read($currentUserId);
	}

	if ($targetUserId <= 0 || $targetUserId === $currentUserId) {
		return 0;
	}

	$unread = $db->super_query("SELECT COUNT(*) AS c FROM mail WHERE id_user_in = {$currentUserId} AND id_user_out = {$targetUserId} AND delete_in = 0 AND reading = 0");
	$unreadCount = (int) ($unread['c'] ?? 0);

	if ($unreadCount > 0) {
		$db->query("UPDATE mail SET reading = '1' WHERE id_user_in = {$currentUserId} AND id_user_out = {$targetUserId} AND delete_in = 0 AND reading = 0");
		$db->query("UPDATE users SET num_messages = GREATEST(num_messages - {$unreadCount}, 0) WHERE id = {$currentUserId}");
		$USER['num_messages'] = max(0, (int) ($USER['num_messages'] ?? 0) - $unreadCount);
		mail_invalidate_user_cache($currentUserId);
	}

	return $unreadCount;
}

function mail_render_message_html($row, $currentUserId, $currentUserName)
{
	$row = (array) $row;
	$currentUserId = (int) $currentUserId;
	$isOutgoing = ((int) ($row['id_user_out'] ?? 0) === $currentUserId);
	$isSystem = ((int) ($row['id_user_out'] ?? 0) === 0);
	$messageAuthor = ($isSystem ? ($currentUserName !== '' ? $currentUserName : 'SYSTEM') : ($isOutgoing ? ($currentUserName !== '' ? $currentUserName : 'Вы') : (!empty($row['sender_name']) ? $row['sender_name'] : 'SYSTEM')));

	ob_start();
	?>
	<div class="mail-modal-message<?=($isOutgoing ? ' mail-modal-message-outgoing' : '');?><?=($isSystem ? ' mail-modal-message-system' : '');?>">
		<div class="mail-modal-message-meta">
			<span class="mail-modal-message-author"><?=htmlspecialchars($messageAuthor, ENT_QUOTES, 'UTF-8');?></span>
			<span class="mail-modal-message-date"><?=convent_date($row['date']);?></span>
		</div>
		<div class="mail-modal-message-text"><?=format_comment($row['text']);?></div>
	</div>
	<?php

	return (string) ob_get_clean();
}

function mail_render_conversation_modal($participant, $conversationTitle, $conversationSubtitle, $messages, $currentUserId, $targetUserId, $systemConversation, $conversationHasOlderMessages, $conversationOlderHref, $blockedByParticipant, $blockedByCurrent, $showAllConversationMessages)
{
	$currentUserName = (string) ($GLOBALS['USER']['name'] ?? '');
	$systemConversation = (bool) $systemConversation;
	$targetUserId = (int) $targetUserId;
	$participantProfileHref = (!$systemConversation && $targetUserId > 0 ? profile_href($participant ?: $targetUserId) : '');

	ob_start();
	?>
	<div class="mail-overlay" data-mail-overlay="1">
		<a class="mail-overlay-close" href="<?=mail_build_href('list');?>" aria-label="Закрыть">&times;</a>

		<div class="mail-modal" role="dialog" aria-modal="true" aria-labelledby="mail-modal-title">
			<div class="mail-modal-header">
				<<?=($participantProfileHref !== '' ? 'a' : 'div');?> class="mail-modal-avatar"<?=($participantProfileHref !== '' ? ' href="'.htmlspecialchars($participantProfileHref, ENT_QUOTES, 'UTF-8').'" data-mail-profile-link="1"' : '');?>>
					<img src="<?=mail_avatar_path($participant);?>" alt="<?=htmlspecialchars($conversationTitle, ENT_QUOTES, 'UTF-8');?>" width="40" height="40">
				</<?=($participantProfileHref !== '' ? 'a' : 'div');?>>
				<div class="mail-modal-heading">
					<?php if ($participantProfileHref !== '') { ?>
					<a class="mail-modal-title mail-modal-title-link" id="mail-modal-title" href="<?=htmlspecialchars($participantProfileHref, ENT_QUOTES, 'UTF-8');?>" data-mail-profile-link="1"><?=htmlspecialchars($conversationTitle, ENT_QUOTES, 'UTF-8');?></a>
					<?php } else { ?>
					<div class="mail-modal-title" id="mail-modal-title"><?=htmlspecialchars($conversationTitle, ENT_QUOTES, 'UTF-8');?></div>
					<?php } ?>
					<div class="mail-modal-subtitle"><?=$conversationSubtitle;?></div>
				</div>
			</div>

			<div class="mail-modal-body">
				<div class="mail-modal-stream<?=($systemConversation ? ' mail-modal-stream-system' : '');?>" data-mail-stream="1">
					<?php if ($conversationHasOlderMessages && $conversationOlderHref !== '') { ?>
					<a class="mail-modal-history-link" href="<?=$conversationOlderHref;?>" data-mail-load-older="1">Показать более старые сообщения</a>
					<?php } ?>

					<?php if (!$messages) { ?>
					<div class="mail-empty-state mail-empty-state-compact"><?=($systemConversation ? 'Системных сообщений пока нет.' : 'Сообщений пока нет. Можно начать диалог прямо сейчас.');?></div>
					<?php } ?>

					<?php foreach($messages as $row) { ?>
					<?=mail_render_message_html($row, $currentUserId, $currentUserName);?>
					<?php } ?>
				</div>

				<?php if (!$systemConversation) { ?>
				<?php if ($blockedByParticipant) { ?>
				<div class="mail-empty-state mail-empty-state-compact">Пользователь добавил вас в ЧС. Отправка новых сообщений недоступна.</div>
				<?php } elseif ($blockedByCurrent) { ?>
				<div class="mail-empty-state mail-empty-state-compact">Пользователь находится в вашем ЧС. Уберите его из списка, чтобы написать сообщение.</div>
				<?php } else { ?>
				<form class="mail-modal-form" action="<?=mail_build_href('conversation', $targetUserId, false, array('all' => ($showAllConversationMessages ? 1 : null)));?>" method="post" data-mail-reply-form="1">
					<input type="hidden" name="name" value="Сообщение">
					<?=lt_csrf_input('mail_action');?>
					<textarea class="mail-modal-textarea" id="mail_reply_text" name="text"><?=htmlspecialchars((string) ($_POST['text'] ?? ''), ENT_QUOTES, 'UTF-8');?></textarea>
					<div class="mail-modal-actions">
						<button class="mail-button" type="submit">Отправить</button>
					</div>
				</form>
				<?php } ?>
				<?php } else { ?>
				<form class="mail-modal-form mail-modal-form-system" action="#" method="post">
					<textarea class="mail-modal-textarea" aria-label="Ответ на системное сообщение"></textarea>
					<div class="mail-modal-actions">
						<button class="mail-button" type="button">Отправить</button>
					</div>
				</form>
				<?php } ?>
			</div>
		</div>
	</div>
	<?php

	return (string) ob_get_clean();
}

$currentUserId = (int) $USER['id'];
lt_sync_user_unread_messages($currentUserId);
$requestMethod = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
$postAct = trim((string) ($_POST['act'] ?? ''));
$act = trim((string) ($_GET['act'] ?? 'list'));
$messageId = (int) ($_GET['id'] ?? 0);
$targetUserId = (int) ($_GET['id_user'] ?? 0);
$systemConversation = !empty($_GET['system']);
$status = trim((string) ($_GET['status'] ?? ''));
$statusMessageId = (int) ($_GET['id_message'] ?? 0);
$loadOlderMessages = !empty($_GET['load_older']);

if ($act === '') {
	$act = 'list';
}

if ($act === 'in_message' || $act === 'out_message') {
	$act = 'list';
}

if ($act === 'send') {
	$act = 'conversation';
}

if (in_array($act, array('read_system', 'restore', 'del'), true) && $requestMethod !== 'POST') {
	if (mail_is_ajax_request()) {
		api_json_error('Метод запроса не поддерживается.', 405);
	}

	header('Location: '.mail_build_href('list', 0, false, array('status' => 6)));
	die();
}

if ($act === 'view' && $messageId > 0) {
	$message = mail_load_message($messageId, $currentUserId);
	if(!$message) {
		err('Ошибка', 'Данного сообщения не существует', 1);
	}

	$targetUserId = mail_partner_id($message, $currentUserId);
	$systemConversation = ($targetUserId === 0);

	header('Location: '.mail_build_href('conversation', $targetUserId, $systemConversation));
	die();
}

if ($postAct === 'read_system') {
	mail_require_post_action();
	mail_require_csrf_action();
	mail_mark_system_read($currentUserId);

	if (mail_is_ajax_request()) {
		mail_json_response(true, 'Системные сообщения отмечены прочитанными.', array(
			'unread_messages' => (int) ($USER['num_messages'] ?? 0),
		));
	}

	header('Location: '.mail_build_href('conversation', 0, true, array('status' => 5)));
	die();
}

if ($postAct === 'mark_read') {
	mail_require_post_action();
	mail_require_csrf_action();

	$markTargetUserId = (int) ($_POST['id_user'] ?? 0);
	$markSystemConversation = !empty($_POST['system']);
	$markedCount = mail_mark_conversation_read($currentUserId, $markTargetUserId, $markSystemConversation);

	if (mail_is_ajax_request()) {
		mail_json_response(true, 'Диалог отмечен прочитанным.', array(
			'marked_count' => $markedCount,
			'unread_messages' => (int) ($USER['num_messages'] ?? 0),
		));
	}

	header('Location: '.mail_build_href('conversation', $markTargetUserId, $markSystemConversation));
	die();
}

if ($postAct === 'restore') {
	mail_require_post_action();
	mail_require_csrf_action();

	$messageId = (int) ($_POST['id'] ?? 0);
	$message = mail_load_message($messageId, $currentUserId);
	if(!$message) {
		mail_action_error('Данного сообщения не существует.', 404);
	}

	$targetUserId = mail_partner_id($message, $currentUserId);
	$systemConversation = ($targetUserId === 0);

	if ((int) $message['id_user_in'] === $currentUserId && (int) $message['delete_in'] === 1) {
		$db->query("UPDATE mail SET delete_in = '0' WHERE id = ".$message['id']);
		if (!(int) $message['reading']) {
			$db->query("UPDATE users SET num_messages = (num_messages + 1) WHERE id = ".$currentUserId);
			$USER['num_messages'] = (int) $USER['num_messages'] + 1;
			mail_invalidate_user_cache($currentUserId);
		}
	}

	if ((int) $message['id_user_out'] === $currentUserId && (int) $message['delete_out'] === 1) {
		$db->query("UPDATE mail SET delete_out = '0' WHERE id = ".$message['id']);
	}

	if (mail_is_ajax_request()) {
		mail_json_response(true, 'Сообщение восстановлено.', array(
			'href' => mail_build_href('conversation', $targetUserId, $systemConversation, array('status' => 4)),
		));
	}

	header('Location: '.mail_build_href('conversation', $targetUserId, $systemConversation, array('status' => 4)));
	die();
}

if ($postAct === 'del') {
	mail_require_post_action();
	mail_require_csrf_action();

	$messageId = (int) ($_POST['id'] ?? 0);
	$message = mail_load_message($messageId, $currentUserId);
	if(!$message) {
		mail_action_error('Данного сообщения не существует.', 404);
	}

	$targetUserId = mail_partner_id($message, $currentUserId);
	$systemConversation = ($targetUserId === 0);

	$deleteIn = (int) $message['delete_in'];
	$deleteOut = (int) $message['delete_out'];

	if ((int) $message['id_user_in'] === $currentUserId && $deleteIn === 0) {
		$db->query("UPDATE mail SET delete_in = '1' WHERE id = ".$message['id']);
		$deleteIn = 1;

		if (!(int) $message['reading'] && (int) $USER['num_messages'] > 0) {
			$db->query("UPDATE users SET num_messages = GREATEST(num_messages - 1, 0) WHERE id = ".$currentUserId);
			$USER['num_messages'] = max(0, (int) $USER['num_messages'] - 1);
			mail_invalidate_user_cache($currentUserId);
		}
	}

	if ((int) $message['id_user_out'] === $currentUserId && $deleteOut === 0) {
		$db->query("UPDATE mail SET delete_out = '1' WHERE id = ".$message['id']);
		$deleteOut = 1;
	}

	if ($deleteIn === 1 && $deleteOut === 1) {
		$db->query("DELETE FROM mail WHERE id = ".$message['id']);
		if (mail_is_ajax_request()) {
			mail_json_response(true, 'Сообщение окончательно удалено.', array(
				'href' => mail_build_href('list', 0, false, array('status' => 2)),
			));
		}
		header('Location: '.mail_build_href('list', 0, false, array('status' => 2)));
		die();
	}

	if (mail_is_ajax_request()) {
		mail_json_response(true, 'Сообщение скрыто из списка.', array(
			'href' => mail_build_href('conversation', $targetUserId, $systemConversation, array('status' => 3, 'id_message' => $message['id'])),
		));
	}

	header('Location: '.mail_build_href('conversation', $targetUserId, $systemConversation, array('status' => 3, 'id_message' => $message['id'])));
	die();
}

$participant = null;
$conversationTitle = 'Диалоги';
$conversationSubtitle = 'Все личные сообщения сгруппированы по собеседникам.';
$messages = array();
$blockedByParticipant = false;
$blockedByCurrent = false;
$conversationLimit = 10;
$showAllConversationMessages = !empty($_GET['all']);
$conversationHasOlderMessages = false;
$conversationOlderHref = '';

if ($act === 'conversation') {
	if ($systemConversation) {
		$participant = array(
			'id' => (int) $USER['id'],
			'name' => (string) $USER['name'],
			'avatar' => (string) ($USER['avatar'] ?? ''),
			'class' => (int) ($USER['class'] ?? 0),
			'last_access' => (string) ($USER['last_access'] ?? ''),
		);
		$conversationTitle = (string) $USER['name'];
		$conversationSubtitle = 'Был на сайте '.convent_date($USER['last_access'] ?? '');
	} else {
		if ($targetUserId <= 0) {
			err('Ошибка', 'Получатель не выбран.', 1);
		}

		if ($targetUserId === $currentUserId) {
			err('Ошибка', 'Вы не можете отправлять сообщения самому себе', 1);
		}

		$participant = $db->super_query("SELECT id, name, class, avatar, last_access FROM users WHERE id = ".$targetUserId." LIMIT 1");
		if(!$participant) {
			err('Ошибка', 'Данного пользователя не существует', 1);
		}

		$blockedByParticipant = user_is_blacklisted($targetUserId, $currentUserId);
		$blockedByCurrent = user_is_blacklisted($currentUserId, $targetUserId);
		$conversationTitle = (string) $participant['name'];
		$conversationSubtitle = 'Был на сайте '.convent_date($participant['last_access']);
	}

	if($_POST && !$systemConversation) {
		mail_require_post_action();
		mail_require_csrf_action();
		mail_rate_limit_send();

		if ($blockedByParticipant) {
			if (mail_is_ajax_request()) {
				mail_json_response(false, 'Пользователь добавил вас в ЧС.');
			}
			err('Ошибка', 'Пользователь добавил вас в ЧС.', 1);
		}

		if ($blockedByCurrent) {
			if (mail_is_ajax_request()) {
				mail_json_response(false, 'Сначала уберите пользователя из ЧС.');
			}
			err('Ошибка', 'Сначала уберите пользователя из ЧС.', 1);
		}

		$replyToId = (int) ($_GET['id_message'] ?? 0);
		$subject = trim((string) ($_POST['name'] ?? ''));

		if ($replyToId > 0) {
			$sourceMessage = mail_load_message($replyToId, $currentUserId);
			if(!$sourceMessage) {
				if (mail_is_ajax_request()) {
					mail_json_response(false, 'Данного сообщения не существует.');
				}
				err('Ошибка', 'Данного сообщения не существует', 1);
			}

			$subject = trim((string) $sourceMessage['name']);
			if ($subject === '') {
				$subject = 'Сообщение';
			} elseif (stripos($subject, 'Re:') !== 0) {
				$subject = 'Re: '.$subject;
			}
		}

		if ($subject === '') {
			$subject = 'Сообщение';
		}

		$text = trim((string) ($_POST['text'] ?? ''));
		if($text === '') {
			if (mail_is_ajax_request()) {
				mail_json_response(false, 'Вы не ввели текст сообщения.');
			}
			err('Ошибка', 'Вы не ввели текст сообщения', 1);
		}

		$db->query("INSERT INTO mail (name, text, date, id_user_in, id_user_out, delete_in, delete_out) VALUES ('".$db->safesql($subject)."', '".$db->safesql($text)."', NOW(), ".$targetUserId.", ".$currentUserId.", 0, 0)");
		$newMessageId = (int) $db->insert_id();
		$db->query("UPDATE users SET num_messages = (num_messages + 1) WHERE id = ".$targetUserId);
		mail_invalidate_user_cache($targetUserId);
		lt_notifications_handle_private_message($targetUserId, $currentUserId, $newMessageId, $subject);

		if (mail_is_ajax_request()) {
			$newMessage = $db->super_query("SELECT m.*, u.name AS sender_name, u.class AS sender_class, u.avatar AS sender_avatar FROM mail AS m LEFT JOIN users AS u ON u.id = m.id_user_out WHERE m.id = ".$newMessageId." LIMIT 1");
			mail_json_response(true, 'Сообщение отправлено.', array(
				'message_html' => mail_render_message_html($newMessage, $currentUserId, (string) ($USER['name'] ?? '')),
			));
		}

		header('Location: '.mail_build_href('conversation', $targetUserId, false, array('status' => 1)));
		die();
	}

	$conversationWhere = mail_conversation_where($currentUserId, $targetUserId, $systemConversation, 'm');
	$totalMessagesRow = $db->super_query("SELECT COUNT(*) AS c FROM mail AS m WHERE ".$conversationWhere);
	$totalMessagesCount = (int) ($totalMessagesRow['c'] ?? 0);
	$olderMessagesCount = max(0, $totalMessagesCount - $conversationLimit);

	if ($loadOlderMessages && $totalMessagesCount > $conversationLimit) {
		$conversationHasOlderMessages = true;
		$conversationOlderHref = mail_build_href('conversation', $targetUserId, $systemConversation, array('all' => 1));
		$sql = $db->query("SELECT m.*, u.name AS sender_name, u.class AS sender_class, u.avatar AS sender_avatar
			FROM mail AS m
			LEFT JOIN users AS u ON u.id = m.id_user_out
			WHERE ".$conversationWhere."
			ORDER BY m.date ASC, m.id ASC
			LIMIT ".$olderMessagesCount);
	} elseif (!$showAllConversationMessages && $totalMessagesCount > $conversationLimit) {
		$conversationHasOlderMessages = true;
		$conversationOlderHref = mail_build_href('conversation', $targetUserId, $systemConversation, array('all' => 1));
		$sql = $db->query("SELECT * FROM (
			SELECT m.*, u.name AS sender_name, u.class AS sender_class, u.avatar AS sender_avatar
			FROM mail AS m
			LEFT JOIN users AS u ON u.id = m.id_user_out
			WHERE ".$conversationWhere."
			ORDER BY m.date DESC, m.id DESC
			LIMIT ".$conversationLimit."
		) AS conversation_slice
		ORDER BY date ASC, id ASC");
	} else {
		$sql = $db->query("SELECT m.*, u.name AS sender_name, u.class AS sender_class, u.avatar AS sender_avatar FROM mail AS m LEFT JOIN users AS u ON u.id = m.id_user_out WHERE ".$conversationWhere." ORDER BY m.date ASC, m.id ASC");
	}

	while($row = $db->get_row($sql)) {
		$messages[] = $row;
	}

	if ($loadOlderMessages) {
		foreach ($messages as $row) {
			echo mail_render_message_html($row, $currentUserId, (string) ($USER['name'] ?? ''));
		}
		die();
	}

	if (mail_is_ajax_request() && !$_POST) {
		mail_json_response(true, '', array(
			'modal_html' => mail_render_conversation_modal($participant, $conversationTitle, $conversationSubtitle, $messages, $currentUserId, $targetUserId, $systemConversation, $conversationHasOlderMessages, $conversationOlderHref, $blockedByParticipant, $blockedByCurrent, $showAllConversationMessages),
			'partner_id' => (int) $targetUserId,
			'system' => ($systemConversation ? 1 : 0),
		));
	}
}

head('Мои сообщения');
echo '<link type="text/css" href="public/css/mail.css" rel="stylesheet">';

if($status === '1') {
	msg('Успешно', 'Сообщение успешно отправлено.');
} elseif($status === '2') {
	msg('Успешно', 'Сообщение окончательно удалено.');
} elseif($status === '3' && $statusMessageId > 0) {
	$restoreForm = '<form method="post" action="my.mail.php" style="display:inline;">'
		.lt_csrf_input('mail_action')
		.'<input type="hidden" name="act" value="restore">'
		.'<input type="hidden" name="id" value="'.$statusMessageId.'">'
		.'<button type="submit" style="background:none;border:0;padding:0;color:inherit;text-decoration:underline;cursor:pointer;">Восстановить</button>'
		.'</form>';
	msg('Успешно', 'Сообщение скрыто из списка. '.$restoreForm);
} elseif($status === '4') {
	msg('Успешно', 'Сообщение восстановлено.');
} elseif($status === '5') {
	msg('Успешно', 'Системные сообщения отмечены прочитанными.');
} elseif($status === '6') {
	msg('Ошибка', 'Действие требует отправки формы.');
}

$conversations = array();
$conversationRows = array();
$partnerIds = array();
$lastMessageIds = array();
$conversationsSql = $db->query(
	"SELECT partner_id,
	        MAX(date) AS last_date,
	        SUBSTRING_INDEX(GROUP_CONCAT(id ORDER BY date DESC, id DESC), ',', 1) AS last_message_id,
	        COUNT(*) AS total_messages,
	        SUM(unread) AS unread_messages
	 FROM (
		SELECT id, date, id_user_out AS partner_id, IF(reading = 0, 1, 0) AS unread
		FROM mail
		WHERE id_user_in = {$currentUserId} AND delete_in = 0
		UNION ALL
		SELECT id, date, id_user_in AS partner_id, 0 AS unread
		FROM mail
		WHERE id_user_out = {$currentUserId} AND delete_out = 0
	 ) AS visible_mail
	 GROUP BY partner_id
	 ORDER BY (unread_messages > 0) DESC, last_date DESC, last_message_id DESC"
);

while ($conversation = $db->get_row($conversationsSql)) {
	$partnerId = (int) ($conversation['partner_id'] ?? 0);
	$lastMessageId = (int) ($conversation['last_message_id'] ?? 0);

	$conversationRows[] = $conversation;
	if ($partnerId > 0) {
		$partnerIds[$partnerId] = $partnerId;
	}
	if ($lastMessageId > 0) {
		$lastMessageIds[$lastMessageId] = $lastMessageId;
	}
}

$partnersById = array();
if ($partnerIds) {
	$partnersSql = $db->query("SELECT id, name, class, avatar, last_access FROM users WHERE id IN (".implode(',', $partnerIds).")");
	while ($partnerRow = $db->get_row($partnersSql)) {
		$partnersById[(int) $partnerRow['id']] = $partnerRow;
	}
}

$lastMessagesById = array();
if ($lastMessageIds) {
	$lastMessagesSql = $db->query("SELECT * FROM mail WHERE id IN (".implode(',', $lastMessageIds).")");
	while ($messageRow = $db->get_row($lastMessagesSql)) {
		$lastMessagesById[(int) $messageRow['id']] = $messageRow;
	}
}

foreach ($conversationRows as $conversation) {
	$partnerId = (int) $conversation['partner_id'];
	$isSystem = ($partnerId === 0);
	$lastMessage = (array) ($lastMessagesById[(int) ($conversation['last_message_id'] ?? 0)] ?? array());

	if ($isSystem) {
		$partner = array(
			'id' => 0,
			'name' => 'SYSTEM',
			'avatar' => '',
			'class' => 0,
			'last_access' => '',
		);
		$subtitle = 'Системные уведомления';
	} else {
		$partner = (array) ($partnersById[$partnerId] ?? array());
		if (!$partner) {
			continue;
		}

		$subtitle = 'Был на сайте '.convent_date($partner['last_access']);
	}

	$conversations[] = array(
		'partner_id' => $partnerId,
		'system' => $isSystem,
		'partner' => $partner,
		'last_message' => $lastMessage,
		'subtitle' => $subtitle,
		'total_messages' => (int) $conversation['total_messages'],
		'unread_messages' => (int) $conversation['unread_messages'],
	);
}
?>

<div class="mail-page<?=($act === 'conversation' ? ' mail-page-dialog-open' : '');?>">
	<div class="mail-thread-list">
		<?php if (!$conversations) { ?>
		<div class="mail-empty-state">У вас пока нет сообщений.</div>
		<?php } ?>

		<?php foreach($conversations as $conversation) { ?>
		<?php
		$partner = $conversation['partner'];
		$partnerName = (!empty($partner['name']) ? $partner['name'] : 'System');
		$openHref = mail_build_href('conversation', $conversation['partner_id'], $conversation['system']);
		$profileHref = (!$conversation['system'] && (int) $conversation['partner_id'] > 0 ? profile_href($partner ?: (int) $conversation['partner_id']) : $openHref);
		$countLabel = $conversation['total_messages'].' '.mail_plural($conversation['total_messages'], 'сообщение', 'сообщения', 'сообщений');
		$isActiveConversation = ($act === 'conversation' && (int) $conversation['partner_id'] === (int) $targetUserId && (bool) $conversation['system'] === (bool) $systemConversation);
		$hasUnreadMessages = ((int) ($conversation['unread_messages'] ?? 0) > 0);
		?>
		<article class="mail-thread-row<?=($isActiveConversation ? ' mail-thread-row-active' : '');?><?=($hasUnreadMessages ? ' mail-thread-row-unread' : '');?>" data-mail-thread="1" data-mail-open-href="<?=$openHref;?>" data-mail-partner-id="<?=(int) $conversation['partner_id'];?>" data-mail-system="<?=($conversation['system'] ? 1 : 0);?>" role="button" tabindex="0">
			<a class="mail-thread-avatar" href="<?=htmlspecialchars($profileHref, ENT_QUOTES, 'UTF-8');?>"<?=(!$conversation['system'] ? ' data-mail-profile-link="1"' : '');?>>
				<img src="<?=mail_avatar_path($partner);?>" alt="<?=htmlspecialchars($partnerName, ENT_QUOTES, 'UTF-8');?>" width="40" height="40">
			</a>

			<div class="mail-thread-main">
				<a class="mail-thread-name" href="<?=htmlspecialchars($profileHref, ENT_QUOTES, 'UTF-8');?>"<?=(!$conversation['system'] ? ' data-mail-profile-link="1"' : '');?>><?=htmlspecialchars($partnerName, ENT_QUOTES, 'UTF-8');?></a>
				<div class="mail-thread-status"><?=$conversation['subtitle'];?></div>
			</div>

			<div class="mail-thread-side">
				<?php if ($hasUnreadMessages) { ?>
				<div class="mail-thread-unread"><?=(int) $conversation['unread_messages'];?></div>
				<?php } ?>
				<div class="mail-thread-count"><?=$countLabel;?></div>
				<a class="mail-button" href="<?=$openHref;?>"><?=($conversation['system'] ? 'Открыть' : 'Написать');?></a>
			</div>
		</article>
		<?php } ?>
	</div>

	<?php if ($act === 'conversation') { ?>
	<?=mail_render_conversation_modal($participant, $conversationTitle, $conversationSubtitle, $messages, $currentUserId, $targetUserId, $systemConversation, $conversationHasOlderMessages, $conversationOlderHref, $blockedByParticipant, $blockedByCurrent, $showAllConversationMessages);?>
	<?php } ?>
</div>

<script>
var mailCsrfToken = '<?=htmlspecialchars(lt_csrf_token('mail_action'), ENT_QUOTES, 'UTF-8');?>';

function mailSetActiveThread(row) {
	var rows = document.querySelectorAll('[data-mail-thread="1"]');
	for (var i = 0; i < rows.length; i++) {
		rows[i].classList.remove('mail-thread-row-active');
	}

	if (!row) {
		return;
	}

	row.classList.add('mail-thread-row-active');
}

function mailMarkConversationRead(partnerId, system, row) {
	var formData = new FormData();
	formData.append('act', 'mark_read');
	formData.append('id_user', partnerId || '0');
	formData.append('system', system ? '1' : '0');
	formData.append('csrf_token', mailCsrfToken);

	fetch('my.mail.php', {
		method: 'POST',
		body: formData,
		credentials: 'same-origin',
		headers: {
			'X-Requested-With': 'XMLHttpRequest',
			'Accept': 'application/json',
			'X-CSRF-Token': mailCsrfToken
		}
	})
		.then(function (response) {
			return response.json();
		})
		.then(function (payload) {
			if (!payload || !payload.ok) {
				return;
			}

			if (row) {
				row.classList.remove('mail-thread-row-unread');
				var unread = row.querySelector('.mail-thread-unread');
				if (unread) {
					unread.remove();
				}
			}
		})
		.catch(function () {});
}

function mailCloseOverlay(pushState) {
	var overlay = document.querySelector('[data-mail-overlay="1"]');
	if (overlay) {
		overlay.remove();
	}

	var rows = document.querySelectorAll('[data-mail-thread="1"]');
	for (var i = 0; i < rows.length; i++) {
		rows[i].classList.remove('mail-thread-row-active');
	}

	if (pushState && window.history && window.history.pushState) {
		window.history.pushState({ mailList: true }, '', '<?=mail_build_href('list');?>');
	}
}

function mailInsertOverlay(html) {
	var page = document.querySelector('.mail-page');
	if (!page) {
		return false;
	}

	var existing = page.querySelector('[data-mail-overlay="1"]');
	if (existing) {
		existing.remove();
	}

	page.insertAdjacentHTML('beforeend', html);
	var stream = page.querySelector('[data-mail-stream="1"]');
	if (stream) {
		stream.scrollTop = stream.scrollHeight;
	}

	return true;
}

function mailOpenConversation(href, row, pushState) {
	if (!href) {
		return;
	}

	if (row && row.getAttribute('data-mail-loading') === '1') {
		return;
	}

	if (row) {
		row.setAttribute('data-mail-loading', '1');
	}

	fetch(href, {
		credentials: 'same-origin',
		headers: {
			'X-Requested-With': 'XMLHttpRequest',
			'Accept': 'application/json',
			'X-CSRF-Token': mailCsrfToken
		}
	})
		.then(function (response) {
			if (!response.ok) {
				throw new Error('Request failed');
			}
			return response.json();
		})
		.then(function (payload) {
			if (!payload || !payload.ok || !payload.modal_html) {
				throw new Error((payload && payload.message) ? payload.message : 'Не удалось открыть диалог.');
			}

			if (!mailInsertOverlay(payload.modal_html)) {
				throw new Error('Не удалось открыть диалог.');
			}

			mailSetActiveThread(row);
			mailMarkConversationRead(payload.partner_id || 0, !!payload.system, row);
			if (pushState && window.history && window.history.pushState) {
				window.history.pushState({ mailConversation: true }, '', href);
			}
		})
		.catch(function () {
			window.location.href = href;
		})
		.finally(function () {
			if (row) {
				row.removeAttribute('data-mail-loading');
			}
		});
}

document.addEventListener('click', function (event) {
	var overlay = event.target.closest('.mail-overlay');
	if (overlay && !event.target.closest('.mail-modal')) {
		event.preventDefault();
		mailCloseOverlay(true);
		return;
	}

	var closeLink = event.target.closest('.mail-overlay-close');
	if (closeLink) {
		event.preventDefault();
		mailCloseOverlay(true);
		return;
	}

	var threadRow = event.target.closest('[data-mail-thread="1"]');
	if (threadRow) {
		if (event.target.closest('[data-mail-profile-link="1"]')) {
			return;
		}

		var href = threadRow.getAttribute('data-mail-open-href') || '';
		if (href) {
			event.preventDefault();
			mailOpenConversation(href, threadRow, true);
			return;
		}
	}

	var loadOlderLink = event.target.closest('[data-mail-load-older="1"]');
	if (!loadOlderLink) {
		return;
	}

	event.preventDefault();

	if (loadOlderLink.getAttribute('data-mail-loading') === '1') {
		return;
	}

	var stream = loadOlderLink.closest('.mail-modal-stream');
	if (!stream) {
		window.location.href = loadOlderLink.href;
		return;
	}

	loadOlderLink.setAttribute('data-mail-loading', '1');
	loadOlderLink.classList.add('mail-modal-history-link-loading');

	var beforeHeight = stream.scrollHeight;
	var requestUrl = loadOlderLink.href + (loadOlderLink.href.indexOf('?') === -1 ? '?' : '&') + 'load_older=1';

	fetch(requestUrl, {
		credentials: 'same-origin',
		headers: {
			'X-Requested-With': 'XMLHttpRequest'
		}
	})
		.then(function (response) {
			if (!response.ok) {
				throw new Error('Request failed');
			}

			return response.text();
		})
		.then(function (html) {
			if (html.replace(/\s+/g, '') !== '') {
				loadOlderLink.insertAdjacentHTML('afterend', html);
			}

			loadOlderLink.remove();
			stream.scrollTop += stream.scrollHeight - beforeHeight;
		})
		.catch(function () {
			loadOlderLink.removeAttribute('data-mail-loading');
			loadOlderLink.classList.remove('mail-modal-history-link-loading');
			window.location.href = loadOlderLink.href;
		});
});

document.addEventListener('submit', function (event) {
	var form = event.target.closest('[data-mail-reply-form="1"]');
	if (!form) {
		return;
	}

	event.preventDefault();

	var textarea = form.querySelector('textarea[name="text"]');
	var button = form.querySelector('button[type="submit"]');
	var stream = document.querySelector('[data-mail-stream="1"]');
	var formData = new FormData(form);

	if (!textarea || textarea.value.replace(/\s+/g, '') === '') {
		return;
	}

	if (button) {
		button.disabled = true;
	}

	fetch(form.action, {
		method: 'POST',
		body: formData,
		credentials: 'same-origin',
		headers: {
			'X-Requested-With': 'XMLHttpRequest',
			'Accept': 'application/json',
			'X-CSRF-Token': mailCsrfToken
		}
	})
		.then(function (response) {
			return response.json();
		})
		.then(function (payload) {
			if (!payload || !payload.ok) {
				throw new Error((payload && payload.message) ? payload.message : 'Не удалось отправить сообщение.');
			}

			if (stream && payload.message_html) {
				var empty = stream.querySelector('.mail-empty-state');
				if (empty) {
					empty.remove();
				}
				stream.insertAdjacentHTML('beforeend', payload.message_html);
				stream.scrollTop = stream.scrollHeight;
			}
			form.reset();
			if (textarea) {
				textarea.focus();
			}
		})
		.catch(function (error) {
			alert(error.message || 'Не удалось отправить сообщение.');
		})
		.finally(function () {
			if (button) {
				button.disabled = false;
			}
		});
});

document.addEventListener('keydown', function (event) {
	if (event.key !== 'Enter' && event.key !== ' ') {
		return;
	}

	var threadRow = event.target.closest('[data-mail-thread="1"]');
	if (!threadRow || event.target.closest('a, button, input, textarea, select')) {
		return;
	}

	var href = threadRow.getAttribute('data-mail-open-href') || '';
	if (!href) {
		return;
	}

	event.preventDefault();
	mailOpenConversation(href, threadRow, true);
});

window.addEventListener('popstate', function () {
	var params = new URLSearchParams(window.location.search || '');
	if (params.get('act') === 'conversation') {
		var href = window.location.pathname.replace(/^\//, '') + window.location.search;
		var id = params.get('id_user') || '0';
		var system = params.get('system') ? '1' : '0';
		var row = document.querySelector('[data-mail-thread="1"][data-mail-partner-id="' + id + '"][data-mail-system="' + system + '"]');
		mailOpenConversation(href, row, false);
		return;
	}

	mailCloseOverlay(false);
});

document.addEventListener('DOMContentLoaded', function () {
	var row = document.querySelector('[data-mail-thread="1"].mail-thread-row-active');
	if (!row) {
		return;
	}

	mailMarkConversationRead(row.getAttribute('data-mail-partner-id') || '0', row.getAttribute('data-mail-system') === '1', row);
});
</script>

<?php
foot();
?>
