<?php
/*
===================================================================
LiteTracker Source
===================================================================
by jenaDI
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

$currentUserId = (int) $USER['id'];
$act = trim((string) ($_GET['act'] ?? 'list'));
$messageId = (int) ($_GET['id'] ?? 0);
$targetUserId = (int) ($_GET['id_user'] ?? 0);
$systemConversation = !empty($_GET['system']);
$status = trim((string) ($_GET['status'] ?? ''));
$statusMessageId = (int) ($_GET['id_message'] ?? 0);

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
			$memcache->delete('user_'.$currentUserId, 0);
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
			$memcache->delete('user_'.$currentUserId, 0);
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

		$conversationTitle = (string) $participant['name'];
		$conversationSubtitle = 'Был на сайте '.convent_date($participant['last_access']);
	}

	if($_POST && !$systemConversation) {
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

		$db->query("INSERT INTO mail (name, text, date, id_user_in, id_user_out) VALUES ('".$db->safesql($subject)."', '".$db->safesql($text)."', NOW(), ".$targetUserId.", ".$currentUserId.")");
		$db->query("UPDATE users SET num_messages = (num_messages + 1) WHERE id = ".$targetUserId);
		$memcache->delete('user_'.$targetUserId, 0);

		header('Location: '.mail_build_href('conversation', $targetUserId, false, array('status' => 1)));
		die();
	}

	if (!$systemConversation) {
		$unread = $db->super_query("SELECT COUNT(*) AS c FROM mail WHERE id_user_in = {$currentUserId} AND id_user_out = {$targetUserId} AND delete_in = 0 AND reading = 0");
		$unreadCount = (int) ($unread['c'] ?? 0);

		if ($unreadCount > 0) {
			$db->query("UPDATE mail SET reading = '1' WHERE id_user_in = {$currentUserId} AND id_user_out = {$targetUserId} AND delete_in = 0 AND reading = 0");
			$db->query("UPDATE users SET num_messages = GREATEST(num_messages - {$unreadCount}, 0) WHERE id = {$currentUserId}");
			$USER['num_messages'] = max(0, (int) $USER['num_messages'] - $unreadCount);
			$memcache->delete('user_'.$currentUserId, 0);
		}
	}

	$sql = $db->query("SELECT m.*, u.name AS sender_name, u.class AS sender_class, u.avatar AS sender_avatar FROM mail AS m LEFT JOIN users AS u ON u.id = m.id_user_out WHERE ".mail_conversation_where($currentUserId, $targetUserId, $systemConversation, 'm')." ORDER BY m.date ASC, m.id ASC");
	while($row = $db->get_row($sql)) {
		$messages[] = $row;
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

<div class="mail-page">
	<div class="mail-shell">
		<aside class="mail-sidebar">
			<a class="mail-sidebar-link<?=($act === 'list' ? ' mail-sidebar-link-active' : '');?>" href="<?=mail_build_href('list');?>">Диалоги</a>
			<?php if ($act === 'conversation') { ?>
			<a class="mail-sidebar-link mail-sidebar-link-active" href="<?=mail_build_href('conversation', $targetUserId, $systemConversation);?>">Текущий диалог</a>
			<?php } ?>
		</aside>

		<section class="mail-panel">
			<?php if ($act === 'conversation') { ?>
			<div class="mail-panel-header">
				<div class="mail-panel-heading">
					<div class="mail-panel-title"><?=$conversationTitle;?></div>
					<div class="mail-panel-subtitle"><?=$conversationSubtitle;?></div>
				</div>
				<div class="mail-panel-actions">
					<a class="mail-button mail-button-secondary" href="<?=mail_build_href('list');?>">Назад к диалогам</a>
					<?php if (!$systemConversation) { ?>
					<a class="mail-button" href="profile.php?id=<?=(int) $participant['id'];?>">Профиль</a>
					<?php } ?>
				</div>
			</div>

			<div class="mail-conversation">
				<div class="mail-conversation-stream">
					<?php if (!$messages) { ?>
					<div class="mail-empty-state">Сообщений пока нет. Можно начать диалог прямо сейчас.</div>
					<?php } ?>

					<?php foreach($messages as $row) { ?>
					<?php
					$isOutgoing = ((int) $row['id_user_out'] === $currentUserId);
					$messageAvatarUser = ($isOutgoing ? $USER : array(
						'avatar' => $row['sender_avatar'],
						'name' => ($row['sender_name'] ?? 'System'),
					));
					$messageAuthor = ($isOutgoing ? 'Вы' : (!empty($row['sender_name']) ? $row['sender_name'] : 'System'));
					$messageDeleteHref = mail_build_href('del', $targetUserId, $systemConversation, array('id' => $row['id']));
					?>
					<article class="mail-message<?=($isOutgoing ? ' mail-message-outgoing' : '');?><?=(!$isOutgoing && !(int) $row['reading'] ? ' mail-message-unread' : '');?>">
						<div class="mail-message-avatar">
							<img src="<?=mail_avatar_path($messageAvatarUser);?>" alt="<?=htmlspecialchars($messageAuthor, ENT_QUOTES, 'UTF-8');?>" width="44" height="44">
						</div>
						<div class="mail-message-body">
							<div class="mail-message-meta">
								<span class="mail-message-author"><?=htmlspecialchars($messageAuthor, ENT_QUOTES, 'UTF-8');?></span>
								<span class="mail-message-date"><?=convent_date($row['date']);?></span>
							</div>
							<?php if (trim((string) $row['name']) !== '') { ?>
							<div class="mail-message-subject"><?=htmlspecialchars((string) $row['name'], ENT_QUOTES, 'UTF-8');?></div>
							<?php } ?>
							<div class="mail-message-text"><?=format_comment($row['text']);?></div>
							<div class="mail-message-actions">
								<a href="<?=$messageDeleteHref;?>">Удалить</a>
							</div>
						</div>
					</article>
					<?php } ?>
				</div>

				<?php if (!$systemConversation) { ?>
				<form class="mail-reply-form" action="<?=mail_build_href('conversation', $targetUserId);?>" method="post">
					<input type="hidden" name="name" value="Сообщение">
					<label class="mail-reply-label" for="mail_reply_text">Новое сообщение</label>
					<textarea class="mail-reply-textarea" id="mail_reply_text" name="text"><?=htmlspecialchars((string) ($_POST['text'] ?? ''), ENT_QUOTES, 'UTF-8');?></textarea>
					<div class="mail-reply-actions">
						<button class="mail-button" type="submit">Отправить</button>
					</div>
				</form>
				<?php } ?>
			</div>
			<?php } else { ?>
			<div class="mail-panel-header">
				<div class="mail-panel-heading">
					<div class="mail-panel-title">Сообщения</div>
					<div class="mail-panel-subtitle">Здесь собраны все диалоги по пользователям, а не отдельные письма вперемешку.</div>
				</div>
			</div>

			<div class="mail-thread-list">
				<?php if (!$conversations) { ?>
				<div class="mail-empty-state">У вас пока нет сообщений.</div>
				<?php } ?>

				<?php foreach($conversations as $conversation) { ?>
				<?php
				$partner = $conversation['partner'];
				$lastMessage = $conversation['last_message'];
				$partnerName = (!empty($partner['name']) ? $partner['name'] : 'System');
				$openHref = mail_build_href('conversation', $conversation['partner_id'], $conversation['system']);
				$preview = ($lastMessage ? mail_preview_text($lastMessage['text']) : '');
				$countLabel = $conversation['total_messages'].' '.mail_plural($conversation['total_messages'], 'сообщение', 'сообщения', 'сообщений');
				?>
				<article class="mail-thread-card<?=($conversation['unread_messages'] > 0 ? ' mail-thread-card-unread' : '');?>">
					<a class="mail-thread-avatar" href="<?=$openHref;?>">
						<img src="<?=mail_avatar_path($partner);?>" alt="<?=htmlspecialchars($partnerName, ENT_QUOTES, 'UTF-8');?>" width="56" height="56">
					</a>

					<div class="mail-thread-body">
						<div class="mail-thread-topline">
							<div class="mail-thread-title"><?=htmlspecialchars($partnerName, ENT_QUOTES, 'UTF-8');?></div>
							<?php if ($lastMessage) { ?>
							<div class="mail-thread-date"><?=convent_date($lastMessage['date']);?></div>
							<?php } ?>
						</div>

						<div class="mail-thread-subtitle"><?=$conversation['subtitle'];?></div>

						<?php if ($lastMessage && trim((string) $lastMessage['name']) !== '') { ?>
						<div class="mail-thread-subject"><?=htmlspecialchars((string) $lastMessage['name'], ENT_QUOTES, 'UTF-8');?></div>
						<?php } ?>

						<?php if ($preview !== '') { ?>
						<div class="mail-thread-preview"><?=htmlspecialchars($preview, ENT_QUOTES, 'UTF-8');?></div>
						<?php } ?>
					</div>

					<div class="mail-thread-side">
						<div class="mail-thread-count"><?=$countLabel;?></div>
						<?php if ($conversation['unread_messages'] > 0) { ?>
						<div class="mail-thread-badge"><?=$conversation['unread_messages'];?> новых</div>
						<?php } ?>
						<a class="mail-button" href="<?=$openHref;?>"><?=($conversation['system'] ? 'Открыть' : 'Написать');?></a>
					</div>
				</article>
				<?php } ?>
			</div>
			<?php } ?>
		</section>
	</div>
</div>

<?php
foot();
?>
