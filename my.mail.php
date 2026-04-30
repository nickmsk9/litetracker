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

require 'system/init.php';

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

function mail_render_message_html($row, $currentUserId, $currentUserName)
{
	$row = (array) $row;
	$currentUserId = (int) $currentUserId;
	$isOutgoing = ((int) ($row['id_user_out'] ?? 0) === $currentUserId);
	$messageAuthor = ($isOutgoing ? ($currentUserName !== '' ? $currentUserName : 'Вы') : (!empty($row['sender_name']) ? $row['sender_name'] : 'System'));

	ob_start();
	?>
	<div class="mail-modal-message<?=($isOutgoing ? ' mail-modal-message-outgoing' : '');?>">
		<div class="mail-modal-message-meta">
			<span class="mail-modal-message-author"><?=htmlspecialchars($messageAuthor, ENT_QUOTES, 'UTF-8');?></span>
			<span class="mail-modal-message-date"><?=convent_date($row['date']);?></span>
		</div>
		<div class="mail-modal-message-text"><?=format_comment($row['text']);?></div>
	</div>
	<?php

	return (string) ob_get_clean();
}

$currentUserId = (int) $USER['id'];
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

if ($act === 'restore' && $messageId > 0) {
	$message = mail_load_message($messageId, $currentUserId);
	if(!$message) {
		err('Ошибка', 'Данного сообщения не существует', 1);
	}

	$targetUserId = mail_partner_id($message, $currentUserId);
	$systemConversation = ($targetUserId === 0);

	if ((int) $message['id_user_in'] === $currentUserId && (int) $message['delete_in'] === 1) {
		$db->query("UPDATE mail SET delete_in = '0' WHERE id = ".$message['id']);
		if (!(int) $message['reading']) {
			$db->query("UPDATE users SET num_messages = (num_messages + 1) WHERE id = ".$currentUserId);
			$USER['num_messages'] = (int) $USER['num_messages'] + 1;
			$memcached->delete('user_'.$currentUserId, 0);
		}
	}

	if ((int) $message['id_user_out'] === $currentUserId && (int) $message['delete_out'] === 1) {
		$db->query("UPDATE mail SET delete_out = '0' WHERE id = ".$message['id']);
	}

	header('Location: '.mail_build_href('conversation', $targetUserId, $systemConversation, array('status' => 4)));
	die();
}

if ($act === 'del' && $messageId > 0) {
	$message = mail_load_message($messageId, $currentUserId);
	if(!$message) {
		err('Ошибка', 'Данного сообщения не существует', 1);
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
			$memcached->delete('user_'.$currentUserId, 0);
		}
	}

	if ((int) $message['id_user_out'] === $currentUserId && $deleteOut === 0) {
		$db->query("UPDATE mail SET delete_out = '1' WHERE id = ".$message['id']);
		$deleteOut = 1;
	}

	if ($deleteIn === 1 && $deleteOut === 1) {
		$db->query("DELETE FROM mail WHERE id = ".$message['id']);
		header('Location: '.mail_build_href('list', 0, false, array('status' => 2)));
		die();
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
			'id' => 0,
			'name' => 'System',
			'avatar' => '',
			'class' => 0,
			'last_access' => '',
		);
		$conversationTitle = 'Системные сообщения';
		$conversationSubtitle = 'Уведомления от движка и администрации.';
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
		if ($blockedByParticipant) {
			err('Ошибка', 'Пользователь добавил вас в ЧС.', 1);
		}

		if ($blockedByCurrent) {
			err('Ошибка', 'Сначала уберите пользователя из ЧС.', 1);
		}

		$replyToId = (int) ($_GET['id_message'] ?? 0);
		$subject = trim((string) ($_POST['name'] ?? ''));

		if ($replyToId > 0) {
			$sourceMessage = mail_load_message($replyToId, $currentUserId);
			if(!$sourceMessage) {
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
			err('Ошибка', 'Вы не ввели текст сообщения', 1);
		}

		$db->query("INSERT INTO mail (name, text, date, id_user_in, id_user_out, delete_in, delete_out) VALUES ('".$db->safesql($subject)."', '".$db->safesql($text)."', NOW(), ".$targetUserId.", ".$currentUserId.", 0, 0)");
		$db->query("UPDATE users SET num_messages = (num_messages + 1) WHERE id = ".$targetUserId);
		$memcached->delete('user_'.$targetUserId, 0);

		header('Location: '.mail_build_href('conversation', $targetUserId, false, array('status' => 1)));
		die();
	}

	if ($systemConversation) {
		$unread = $db->super_query("SELECT COUNT(*) AS c FROM mail WHERE id_user_in = {$currentUserId} AND id_user_out = 0 AND delete_in = 0 AND reading = 0");
		$unreadCount = (int) ($unread['c'] ?? 0);

		if ($unreadCount > 0) {
			$db->query("UPDATE mail SET reading = '1' WHERE id_user_in = {$currentUserId} AND id_user_out = 0 AND delete_in = 0 AND reading = 0");
			$db->query("UPDATE users SET num_messages = GREATEST(num_messages - {$unreadCount}, 0) WHERE id = {$currentUserId}");
			$USER['num_messages'] = max(0, (int) $USER['num_messages'] - $unreadCount);
			$memcached->delete('user_'.$currentUserId, 0);
		}
	} else {
		$unread = $db->super_query("SELECT COUNT(*) AS c FROM mail WHERE id_user_in = {$currentUserId} AND id_user_out = {$targetUserId} AND delete_in = 0 AND reading = 0");
		$unreadCount = (int) ($unread['c'] ?? 0);

		if ($unreadCount > 0) {
			$db->query("UPDATE mail SET reading = '1' WHERE id_user_in = {$currentUserId} AND id_user_out = {$targetUserId} AND delete_in = 0 AND reading = 0");
			$db->query("UPDATE users SET num_messages = GREATEST(num_messages - {$unreadCount}, 0) WHERE id = {$currentUserId}");
			$USER['num_messages'] = max(0, (int) $USER['num_messages'] - $unreadCount);
			$memcached->delete('user_'.$currentUserId, 0);
		}
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
}

head('Мои сообщения');
echo '<link type="text/css" href="public/css/mail.css" rel="stylesheet">';

if($status === '1') {
	msg('Успешно', 'Сообщение успешно отправлено.');
} elseif($status === '2') {
	msg('Успешно', 'Сообщение окончательно удалено.');
} elseif($status === '3' && $statusMessageId > 0) {
	$restoreLink = mail_build_href('restore', $targetUserId, $systemConversation, array('id' => $statusMessageId));
	msg('Успешно', 'Сообщение скрыто из списка. <a href="'.$restoreLink.'">Восстановить</a>');
} elseif($status === '4') {
	msg('Успешно', 'Сообщение восстановлено.');
}

$conversations = array();
$conversationsSql = $db->query("SELECT IF(id_user_in = {$currentUserId}, id_user_out, id_user_in) AS partner_id, MAX(date) AS last_date, COUNT(*) AS total_messages, SUM(IF(id_user_in = {$currentUserId} AND reading = 0 AND delete_in = 0, 1, 0)) AS unread_messages FROM mail WHERE ((id_user_in = {$currentUserId} AND delete_in = 0) OR (id_user_out = {$currentUserId} AND delete_out = 0)) GROUP BY partner_id ORDER BY last_date DESC");

while($conversation = $db->get_row($conversationsSql)) {
	$partnerId = (int) $conversation['partner_id'];
	$isSystem = ($partnerId === 0);

	if ($isSystem) {
		$partner = array(
			'id' => 0,
			'name' => 'System',
			'avatar' => '',
			'class' => 0,
			'last_access' => '',
		);
		$lastMessage = $db->super_query("SELECT * FROM mail WHERE id_user_in = {$currentUserId} AND id_user_out = 0 AND delete_in = 0 ORDER BY date DESC, id DESC LIMIT 1");
		$subtitle = 'Системные уведомления';
	} else {
		$partner = get_user_info($partnerId);
		if (!$partner) {
			continue;
		}

		$lastMessage = $db->super_query("SELECT * FROM mail WHERE ".mail_conversation_where($currentUserId, $partnerId, false, '')." ORDER BY date DESC, id DESC LIMIT 1");
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
		$partnerProfileHref = ($conversation['system'] ? $openHref : 'profile.php?id='.(int) $conversation['partner_id']);
		$countLabel = $conversation['total_messages'].' '.mail_plural($conversation['total_messages'], 'сообщение', 'сообщения', 'сообщений');
		$isActiveConversation = ($act === 'conversation' && (int) $conversation['partner_id'] === (int) $targetUserId && (bool) $conversation['system'] === (bool) $systemConversation);
		?>
		<article class="mail-thread-row<?=($isActiveConversation ? ' mail-thread-row-active' : '');?>">
			<a class="mail-thread-avatar" href="<?=$openHref;?>">
				<img src="<?=mail_avatar_path($partner);?>" alt="<?=htmlspecialchars($partnerName, ENT_QUOTES, 'UTF-8');?>" width="40" height="40">
			</a>

			<div class="mail-thread-main">
				<a class="mail-thread-name" href="<?=$partnerProfileHref;?>"><?=htmlspecialchars($partnerName, ENT_QUOTES, 'UTF-8');?></a>
				<div class="mail-thread-status"><?=$conversation['subtitle'];?></div>
			</div>

			<div class="mail-thread-side">
				<div class="mail-thread-count"><?=$countLabel;?></div>
				<a class="mail-button" href="<?=$openHref;?>"><?=($conversation['system'] ? 'Открыть' : 'Написать');?></a>
			</div>
		</article>
		<?php } ?>
	</div>

	<?php if ($act === 'conversation') { ?>
	<div class="mail-overlay">
		<a class="mail-overlay-close" href="<?=mail_build_href('list');?>">&times;</a>

		<div class="mail-modal" role="dialog" aria-modal="true" aria-labelledby="mail-modal-title">
			<div class="mail-modal-header">
				<div class="mail-modal-avatar">
					<img src="<?=mail_avatar_path($participant);?>" alt="<?=htmlspecialchars($conversationTitle, ENT_QUOTES, 'UTF-8');?>" width="40" height="40">
				</div>
				<div class="mail-modal-heading">
					<div class="mail-modal-title" id="mail-modal-title"><?=htmlspecialchars($conversationTitle, ENT_QUOTES, 'UTF-8');?></div>
					<div class="mail-modal-subtitle"><?=$conversationSubtitle;?></div>
				</div>
			</div>

			<div class="mail-modal-body">
				<div class="mail-modal-stream">
					<?php if ($conversationHasOlderMessages && $conversationOlderHref !== '') { ?>
					<a class="mail-modal-history-link" href="<?=$conversationOlderHref;?>" data-mail-load-older="1">Показать более старые сообщения</a>
					<?php } ?>

					<?php if (!$messages) { ?>
					<div class="mail-empty-state mail-empty-state-compact">Сообщений пока нет. Можно начать диалог прямо сейчас.</div>
					<?php } ?>

					<?php foreach($messages as $row) { ?>
					<?=mail_render_message_html($row, $currentUserId, (string) ($USER['name'] ?? ''));?>
					<?php } ?>
				</div>

				<?php if (!$systemConversation) { ?>
				<?php if ($blockedByParticipant) { ?>
				<div class="mail-empty-state mail-empty-state-compact">Пользователь добавил вас в ЧС. Отправка новых сообщений недоступна.</div>
				<?php } elseif ($blockedByCurrent) { ?>
				<div class="mail-empty-state mail-empty-state-compact">Пользователь находится в вашем ЧС. Уберите его из списка, чтобы написать сообщение.</div>
				<?php } else { ?>
				<form class="mail-modal-form" action="<?=mail_build_href('conversation', $targetUserId, false, array('all' => ($showAllConversationMessages ? 1 : null)));?>" method="post">
					<input type="hidden" name="name" value="Сообщение">
					<textarea class="mail-modal-textarea" id="mail_reply_text" name="text"><?=htmlspecialchars((string) ($_POST['text'] ?? ''), ENT_QUOTES, 'UTF-8');?></textarea>
					<div class="mail-modal-actions">
						<button class="mail-button" type="submit">Отправить</button>
					</div>
				</form>
				<?php } ?>
				<?php } ?>
			</div>
		</div>
	</div>
	<?php } ?>
</div>

<script>
document.addEventListener('click', function (event) {
	var overlay = event.target.closest('.mail-overlay');
	if (overlay && !event.target.closest('.mail-modal')) {
		event.preventDefault();
		var closeLink = overlay.querySelector('.mail-overlay-close');
		if (closeLink && closeLink.href) {
			window.location.href = closeLink.href;
		}
		return;
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
</script>

<?php
foot();
?>
