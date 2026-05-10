<?php
/*
===================================================================
LiteTracker Source
-------------------------------------------------------------------
Назначение: Центр уведомлений
===================================================================
*/

require 'system/init.php';

is_login();

$notificationScope = 'notifications_action';
$filter = trim((string) ($_GET['filter'] ?? 'all'));
$filter = ($filter === 'unread' ? 'unread' : 'all');
$cursor = max(0, (int) ($_GET['cursor'] ?? 0));

function notifications_page_href($filter = 'all', $cursor = 0)
{
	$params = array();
	if ($filter === 'unread') {
		$params['filter'] = 'unread';
	}
	if ((int) $cursor > 0) {
		$params['cursor'] = (int) $cursor;
	}

	return 'notifications.php'.($params ? '?'.http_build_query($params) : '');
}

if ($_POST) {
	if (!lt_csrf_validate($notificationScope)) {
		err($language['default_1'], 'Защитный токен устарел. Обновите страницу и попробуйте снова.', 1);
	}

	$action = preg_replace('~[^a-z_]~', '', (string) ($_POST['action'] ?? ''));
	$ids = $_POST['ids'] ?? array();
	if (!is_array($ids)) {
		$ids = array($ids);
	}

	if ($action === 'mark_read') {
		lt_notifications_mark_read((int) $USER['id'], $ids);
	} elseif ($action === 'mark_all_read') {
		lt_notifications_mark_all_read((int) $USER['id']);
	} elseif ($action === 'archive') {
		lt_notifications_archive((int) $USER['id'], $ids);
	}

	header('Location: '.notifications_page_href($filter));
	die();
}

$notifications = lt_notifications_fetch((int) $USER['id'], array(
	'limit' => 20,
	'cursor' => $cursor,
	'unread_only' => ($filter === 'unread'),
));
$items = $notifications['items'];
$nextCursor = (int) ($notifications['next_cursor'] ?? 0);
$unreadCount = lt_notifications_unread_count((int) $USER['id']);

head('Уведомления');
?>
<div class="notifications-page" data-notifications-page>
	<section class="notifications-hero">
		<div>
			<h1>Уведомления</h1>
			<p>Ответы, комментарии к релизам, личные сообщения и важные действия модерации.</p>
		</div>
		<form method="post" action="<?=htmlspecialchars(notifications_page_href($filter), ENT_QUOTES, 'UTF-8');?>">
			<?=lt_csrf_input($notificationScope);?>
			<input type="hidden" name="action" value="mark_all_read">
			<button class="notifications-button" type="submit"<?=($unreadCount <= 0 ? ' disabled' : '');?>>Прочитать всё</button>
		</form>
	</section>

	<nav class="notifications-tabs" aria-label="Фильтр уведомлений">
		<a class="<?=($filter === 'all' ? 'is-active' : '');?>" href="notifications.php">Все</a>
		<a class="<?=($filter === 'unread' ? 'is-active' : '');?>" href="notifications.php?filter=unread">Непрочитанные<?=($unreadCount > 0 ? ' '.$unreadCount : '');?></a>
	</nav>

	<section class="notifications-panel">
		<?php if ($items) { ?>
		<div class="notifications-list">
			<?php foreach ($items as $item) { ?>
			<?php
			$itemId = (int) ($item['id'] ?? 0);
			$itemUrl = lt_notification_internal_url($item['url'] ?? '');
			$isRead = !empty($item['is_read']);
			?>
			<article class="notification-row<?=($isRead ? '' : ' notification-row-unread');?>" data-notification-id="<?=$itemId;?>">
				<a class="notification-row-main" href="<?=htmlspecialchars($itemUrl !== '' ? $itemUrl : 'notifications.php', ENT_QUOTES, 'UTF-8');?>">
					<span class="notification-avatar">
						<img src="<?=htmlspecialchars((string) ($item['actor']['avatar'] ?? 'public/images/default_avatar.gif'), ENT_QUOTES, 'UTF-8');?>" alt="" width="34" height="34">
					</span>
					<span class="notification-copy">
						<strong><?=htmlspecialchars((string) ($item['title'] ?? ''), ENT_QUOTES, 'UTF-8');?></strong>
						<?php if (!empty($item['message'])) { ?>
						<span><?=htmlspecialchars((string) $item['message'], ENT_QUOTES, 'UTF-8');?></span>
						<?php } ?>
						<time><?=htmlspecialchars((string) ($item['created_label'] ?? ''), ENT_QUOTES, 'UTF-8');?></time>
					</span>
				</a>
				<div class="notification-actions">
					<?php if (!$isRead) { ?>
					<form method="post" action="<?=htmlspecialchars(notifications_page_href($filter, $cursor), ENT_QUOTES, 'UTF-8');?>">
						<?=lt_csrf_input($notificationScope);?>
						<input type="hidden" name="action" value="mark_read">
						<input type="hidden" name="ids[]" value="<?=$itemId;?>">
						<button type="submit">Прочитано</button>
					</form>
					<?php } ?>
					<form method="post" action="<?=htmlspecialchars(notifications_page_href($filter, $cursor), ENT_QUOTES, 'UTF-8');?>">
						<?=lt_csrf_input($notificationScope);?>
						<input type="hidden" name="action" value="archive">
						<input type="hidden" name="ids[]" value="<?=$itemId;?>">
						<button type="submit">Архив</button>
					</form>
				</div>
			</article>
			<?php } ?>
		</div>
		<?php } else { ?>
		<div class="notifications-empty">
			<strong><?=($filter === 'unread' ? 'Непрочитанных уведомлений нет.' : 'Уведомлений пока нет.');?></strong>
			<span>Когда появятся ответы, комментарии, ЛС или модераторские события, они будут здесь.</span>
		</div>
		<?php } ?>
	</section>

	<?php if ($nextCursor > 0 || $cursor > 0) { ?>
	<nav class="notifications-pagination" aria-label="Навигация уведомлений">
		<a href="<?=htmlspecialchars(notifications_page_href($filter), ENT_QUOTES, 'UTF-8');?>">Свежие</a>
		<?php if ($nextCursor > 0) { ?>
		<a href="<?=htmlspecialchars(notifications_page_href($filter, $nextCursor), ENT_QUOTES, 'UTF-8');?>">Раньше</a>
		<?php } ?>
	</nav>
	<?php } ?>
</div>
<?php
foot();
?>
