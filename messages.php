<?php
/*
===================================================================
LiteTracker Source
===================================================================
by Nick
-------------------------------------------------------------------
Назначение: Массовая рассылка
===================================================================
*/

require 'system/init.php';

is_login();

if (empty($PRIV['messages'])) {
	err($language['default_1'], 'Вам запрещено рассылать сообщения', 1);
}

$draft = array(
	'name' => '',
	'text' => '',
	'filter' => array(),
	'system' => 1,
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!lt_csrf_validate('messages_admin')) {
		err('Ошибка', 'Защитный токен устарел. Обновите страницу и повторите действие.', 1);
	}

	$name = trim((string) ($_POST['name'] ?? ''));
	$text = trim((string) ($_POST['text'] ?? ''));
	$filter = array_values(array_unique(array_map('intval', (array) ($_POST['filter'] ?? array()))));
	$systemMessage = !empty($_POST['system']);

	$draft = array(
		'name' => $name,
		'text' => $text,
		'filter' => $filter,
		'system' => ($systemMessage ? 1 : 0),
	);

	if ($name === '') {
		err('Ошибка', 'Введите тему сообщения', 1);
	}

	if ($text === '') {
		err('Ошибка', 'Вы не ввели текст сообщения', 1);
	}

	$filterSql = array();
	foreach ($filter as $classId) {
		if ($classId > 0) {
			$filterSql[] = 'class='.(int) $classId;
		}
	}

	$userIds = array();
	$sql = $db->query("SELECT id FROM users ".($filterSql ? 'WHERE '.implode(' OR ', $filterSql) : '')." ORDER BY id ASC");
	while ($row = $db->get_row($sql)) {
		$userIds[] = (int) $row['id'];
	}
	$db->free($sql);

	if (!$userIds) {
		err('Ошибка', 'Пользователи для выбранной группы не найдены', 1);
	}

	$sender = ($systemMessage ? 0 : (int) $USER['id']);
	foreach ($userIds as $userId) {
		send_msg($name, $text, $userId, $sender);
	}

	header('Location: messages.php?status=1&sent='.count($userIds));
	die();
}

$classes = get_classes_list();
$classCounts = array();
$countSql = $db->query("SELECT class, COUNT(*) AS c FROM users GROUP BY class");
while ($countRow = $db->get_row($countSql)) {
	$classCounts[(int) $countRow['class']] = (int) $countRow['c'];
}
$db->free($countSql);

$status = trim((string) ($_GET['status'] ?? ''));
$sent = (int) ($_GET['sent'] ?? 0);

head('Массовая рассылка');
?>
<div class="lt-admin-page">
	<section class="lt-admin-hero">
		<h1>Системные сообщения</h1>
		<p class="lt-admin-lead">Рассылка отправляет личные сообщения выбранным классам пользователей. Если классы не отмечать, сообщение уйдет всем пользователям.</p>
		<div class="lt-admin-actions">
			<a class="lt-admin-link-button lt-admin-button-secondary" href="admin.php?tab=users">Назад в админку</a>
		</div>
	</section>

	<?php if ($status === '1') { ?>
	<div class="lt-admin-notice lt-admin-notice-success">Сообщения отправлены<?=($sent > 0 ? ': '.number_format($sent) : '');?>.</div>
	<?php } ?>

	<section class="lt-admin-panel">
		<h2>Новая рассылка</h2>
		<p class="lt-admin-panel-text">Пример: тема “Технические работы”, текст с датой и временем, фильтр “Пользователи” и “Аплоадеры”.</p>

		<form class="lt-admin-form" method="post" action="messages.php" style="margin-top:16px;">
			<?=lt_csrf_input('messages_admin');?>
			<div class="lt-admin-field">
				<label class="lt-admin-label" for="message-name">Тема</label>
				<input class="lt-admin-input" id="message-name" type="text" name="name" value="<?=htmlspecialchars($draft['name'], ENT_QUOTES, 'UTF-8');?>" maxlength="120" required>
				<div class="lt-admin-help">Коротко назовите причину сообщения. Тема будет видна в списке ЛС.</div>
			</div>
			<div class="lt-admin-field">
				<label class="lt-admin-label" for="message-text">Сообщение</label>
				<textarea class="lt-admin-textarea" id="message-text" name="text" required><?=htmlspecialchars($draft['text'], ENT_QUOTES, 'UTF-8');?></textarea>
				<div class="lt-admin-help">Можно писать обычный текст и BBCode, если он поддерживается в личных сообщениях.</div>
			</div>
			<div class="lt-admin-field">
				<label class="lt-admin-label">Получатели</label>
				<div class="lt-admin-grid">
					<?php foreach ($classes as $class) { ?>
					<?php $classId = (int) $class['id']; ?>
					<label class="lt-admin-option">
						<input type="checkbox" name="filter[]" value="<?=$classId;?>"<?=(in_array($classId, $draft['filter'], true) ? ' checked' : '');?>>
						<strong><?=htmlspecialchars($class['NAME'], ENT_QUOTES, 'UTF-8');?></strong>
						<div class="lt-admin-help"><?=number_format((int) ($classCounts[$classId] ?? 0));?> пользователей. Отметьте класс, чтобы отправить только ему.</div>
					</label>
					<?php } ?>
				</div>
				<div class="lt-admin-help">Ничего не отмечено: рассылка уйдет всем аккаунтам.</div>
			</div>
			<div class="lt-admin-field">
				<label class="lt-admin-label">
					<input type="checkbox" name="system" value="1"<?=(!empty($draft['system']) ? ' checked' : '');?>>
					Отправить как системное сообщение
				</label>
				<div class="lt-admin-help">Системное сообщение приходит от сайта, а не от вашего аккаунта. Так лучше отправлять объявления и предупреждения.</div>
			</div>
			<div class="lt-admin-actions">
				<button class="lt-admin-button" type="submit">Разослать</button>
			</div>
		</form>
	</section>
</div>
<?php
foot();
