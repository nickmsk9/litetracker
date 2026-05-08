<?php
/*
===================================================================
LiteTracker Source
===================================================================
by Nick
-------------------------------------------------------------------
Назначение: Управление блоками
===================================================================
*/

require 'system/init.php';

is_login();

if (empty($PRIV['EDIT_PRIV'])) {
	err('Ошибка', 'У вас нет прав просматривать данную страницу');
}

$act = trim((string) ($_GET['act'] ?? ''));
$status = trim((string) ($_GET['status'] ?? ''));

function lt_blocks_positions()
{
	return array(
		'l' => 'Слева',
		'c' => 'По центру сверху',
		'd' => 'По центру снизу',
		'r' => 'Справа',
	);
}

function lt_blocks_types()
{
	return array(
		'all' => 'Всем',
		'guests' => 'Гостям',
		'users' => 'Пользователям',
		'moderators' => 'Модераторам',
		'administrators' => 'Администраторам',
	);
}

function lt_blocks_notice($status)
{
	$messages = array(
		'1' => 'Блок сохранен.',
		'2' => 'Блок удален.',
		'3' => 'Порядок блоков обновлен.',
	);

	return ($messages[$status] ?? '');
}

function lt_blocks_available_files()
{
	$files = array();
	foreach ((array) glob(__DIR__.'/blocks/*.php') as $path) {
		$file = basename($path);
		if ($file === 'block-vkontakte.php') {
			continue;
		}
		$files[] = $file;
	}
	sort($files);

	return $files;
}

function lt_blocks_json_response($ok, $message = '', $extra = array())
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

function lt_blocks_reindex_position($position)
{
	global $db;

	$positions = lt_blocks_positions();
	if (empty($positions[$position])) {
		return;
	}

	$sql = $db->query("SELECT bid, weight
					  FROM orbital_blocks
					  WHERE position='".$db->safesql($position)."'
					  ORDER BY weight ASC, bid ASC");
	$weight = 1;
	while ($row = $db->get_row($sql)) {
		if ((int) $row['weight'] !== $weight) {
			$db->query("UPDATE orbital_blocks SET weight=".$weight." WHERE bid=".(int) $row['bid']);
		}
		$weight++;
	}
	$db->free($sql);
}

function lt_blocks_select_options($items, $selected)
{
	$html = '';
	foreach ((array) $items as $value => $label) {
		$html .= '<option value="'.htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8').'"'.((string) $selected === (string) $value ? ' selected' : '').'>'.htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8').'</option>';
	}

	return $html;
}

function lt_blocks_normalize_which($which)
{
	$which = trim((string) $which);
	if ($which === '' || strtolower($which) === 'all') {
		return 'all';
	}

	$pages = array();
	foreach (explode(',', $which) as $page) {
		$page = preg_replace('~[^a-z0-9_\-]~i', '', trim($page));
		if ($page !== '') {
			$pages[$page] = $page;
		}
	}

	return ($pages ? implode(',', array_values($pages)) : 'all');
}

function lt_blocks_render_which($which)
{
	$which = trim((string) $which);
	if ($which === '' || $which === 'all') {
		return 'Везде';
	}

	$links = array();
	foreach (explode(',', $which) as $page) {
		$page = preg_replace('~[^a-z0-9_\-]~i', '', trim($page));
		if ($page !== '') {
			$links[] = '<a href="'.$page.'.php" target="_blank">'.htmlspecialchars($page, ENT_QUOTES, 'UTF-8').'.php</a>';
		}
	}

	return ($links ? implode(', ', $links) : 'Везде');
}

function lt_blocks_render_form($title, $bid, $block)
{
	$files = lt_blocks_available_files();
	$currentFile = trim((string) ($block['blockfile'] ?? ''));
	if ($currentFile !== '' && !in_array($currentFile, $files, true)) {
		array_unshift($files, $currentFile);
	}

	head($title);
	?>
	<div class="lt-admin-page">
		<section class="lt-admin-hero">
			<h1><?=$title;?></h1>
			<p class="lt-admin-lead">Блок подключает PHP-файл из папки <span class="lt-admin-code">blocks/</span> и показывает его в выбранной зоне сайта. Пример: блок поиска можно поставить в центр сверху только для главной страницы.</p>
			<div class="lt-admin-actions">
				<a class="lt-admin-link-button lt-admin-button-secondary" href="blocks.php">К списку блоков</a>
			</div>
		</section>

		<section class="lt-admin-panel">
			<form class="lt-admin-form" method="post" action="blocks.php?act=add<?=($bid ? '&bid='.(int) $bid : '');?>">
				<?=lt_csrf_input('blocks_admin');?>
				<div class="lt-admin-form-grid">
					<div class="lt-admin-field">
						<label class="lt-admin-label" for="block-title">Название</label>
						<input class="lt-admin-input" id="block-title" type="text" name="title" value="<?=htmlspecialchars((string) ($block['title'] ?? ''), ENT_QUOTES, 'UTF-8');?>" maxlength="60" required>
						<div class="lt-admin-help">Видно только в админке и заголовках блока, если сам файл его выводит.</div>
					</div>
					<div class="lt-admin-field">
						<label class="lt-admin-label" for="block-file">Файл блока</label>
						<select class="lt-admin-select" id="block-file" name="blockfile" required>
							<option value="">Выберите файл</option>
							<?php foreach ($files as $file) { ?>
							<option value="<?=htmlspecialchars($file, ENT_QUOTES, 'UTF-8');?>"<?=($currentFile === $file ? ' selected' : '');?>><?=htmlspecialchars($file, ENT_QUOTES, 'UTF-8');?><?=(!is_file(__DIR__.'/blocks/'.$file) ? ' - файл отсутствует' : '');?></option>
							<?php } ?>
						</select>
						<div class="lt-admin-help">Если файл отсутствует, блок не сможет отрисоваться. Такие блоки отмечаются в списке.</div>
					</div>
					<div class="lt-admin-field">
						<label class="lt-admin-label" for="block-position">Позиция</label>
						<select class="lt-admin-select" id="block-position" name="position" required>
							<option value="">Выберите позицию</option>
							<?=lt_blocks_select_options(lt_blocks_positions(), (string) ($block['position'] ?? ''));?>
						</select>
						<div class="lt-admin-help">Позиция задает область страницы: слева, справа или центральные зоны.</div>
					</div>
					<div class="lt-admin-field">
						<label class="lt-admin-label" for="block-active">Статус</label>
						<select class="lt-admin-select" id="block-active" name="active">
							<option value="1"<?=(!empty($block['active']) ? ' selected' : '');?>>Активен</option>
							<option value="0"<?=(empty($block['active']) ? ' selected' : '');?>>Выключен</option>
						</select>
						<div class="lt-admin-help">Выключенный блок остается в списке, но не показывается пользователям.</div>
					</div>
					<div class="lt-admin-field">
						<label class="lt-admin-label" for="block-type">Кому показывать</label>
						<select class="lt-admin-select" id="block-type" name="type">
							<?=lt_blocks_select_options(lt_blocks_types(), (string) ($block['type'] ?? 'all'));?>
						</select>
						<div class="lt-admin-help">Например, рекламный блок можно оставить гостям, а служебный блок только администраторам.</div>
					</div>
					<div class="lt-admin-field">
						<label class="lt-admin-label" for="block-which">Где показывать</label>
						<input class="lt-admin-input" id="block-which" type="text" name="which" value="<?=htmlspecialchars((string) ($block['which'] ?? 'all'), ENT_QUOTES, 'UTF-8');?>">
						<div class="lt-admin-help">Введите <span class="lt-admin-code">all</span> для всех страниц или список без .php: <span class="lt-admin-code">index,browse,details</span>.</div>
					</div>
				</div>
				<div class="lt-admin-actions">
					<button class="lt-admin-button" type="submit">Сохранить блок</button>
					<a class="lt-admin-link-button lt-admin-button-secondary" href="blocks.php">Отмена</a>
				</div>
			</form>
		</section>
	</div>
	<?php
	foot();
	die();
}

if ($act === 'move') {
	$bid = (int) ($_POST['bid'] ?? ($_GET['bid'] ?? 0));
	$direction = trim((string) ($_POST['direction'] ?? ($_GET['direction'] ?? '')));

	if (!$bid) {
		lt_blocks_json_response(false, 'Не указан блок для перемещения.');
	}

	if ($direction !== 'up' && $direction !== 'down') {
		lt_blocks_json_response(false, 'Неизвестное направление перемещения.');
	}

	$current = $db->super_query("SELECT bid, position, weight FROM orbital_blocks WHERE bid=".$bid." LIMIT 1");
	if (empty($current['bid'])) {
		lt_blocks_json_response(false, 'Блок не найден.');
	}

	$position = $current['position'];
	lt_blocks_reindex_position($position);
	$current = $db->super_query("SELECT bid, position, weight FROM orbital_blocks WHERE bid=".$bid." LIMIT 1");

	$compare = ($direction === 'up' ? '<' : '>');
	$order = ($direction === 'up' ? 'DESC' : 'ASC');
	$swap = $db->super_query("SELECT bid, weight
						  FROM orbital_blocks
						  WHERE position='".$db->safesql($position)."'
							AND weight ".$compare." ".(int) $current['weight']."
						  ORDER BY weight ".$order.", bid ".$order."
						  LIMIT 1");

	if (empty($swap['bid'])) {
		lt_blocks_json_response(false, 'Блок уже находится на краю списка.');
	}

	$currentBid = (int) $current['bid'];
	$currentWeight = (int) $current['weight'];
	$swapBid = (int) $swap['bid'];
	$swapWeight = (int) $swap['weight'];

	$tempWeightRow = $db->super_query("SELECT MAX(weight) AS c FROM orbital_blocks WHERE position='".$db->safesql($position)."'");
	$tempWeight = ((int) $tempWeightRow['c']) + 1000;

	$db->query("UPDATE orbital_blocks SET weight=".$tempWeight." WHERE bid=".$currentBid);
	$db->query("UPDATE orbital_blocks SET weight=".$currentWeight." WHERE bid=".$swapBid);
	$db->query("UPDATE orbital_blocks SET weight=".$swapWeight." WHERE bid=".$currentBid);

	lt_blocks_reindex_position($position);
	$memcached->delete('block_'.$position, 0);

	lt_blocks_json_response(true, 'Порядок блоков обновлен.');
}

if ($act === 'del') {
	$bid = (int) ($_GET['bid'] ?? 0);
	$block = $db->super_query("SELECT * FROM orbital_blocks WHERE bid=".$bid." LIMIT 1");
	if (empty($block['bid'])) {
		err('Ошибка', 'Данный блок не найден', 1);
	}

	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		if (!lt_csrf_validate('blocks_admin')) {
			err('Ошибка', 'Защитный токен устарел. Обновите страницу и повторите действие.', 1);
		}

		$db->query("DELETE FROM orbital_blocks WHERE bid=".$bid);
		lt_blocks_reindex_position($block['position']);
		$memcached->delete('block_'.$block['position'], 0);

		header('Location: blocks.php?position='.urlencode($block['position']).'&status=2');
		die();
	}

	head('Удаление блока');
	?>
	<div class="lt-admin-page">
		<section class="lt-admin-hero">
			<h1>Удаление блока</h1>
			<p class="lt-admin-lead">Вы удаляете “<?=htmlspecialchars($block['title'], ENT_QUOTES, 'UTF-8');?>”. После удаления блок пропадет из админки и перестанет отображаться на сайте.</p>
			<div class="lt-admin-actions">
				<a class="lt-admin-link-button lt-admin-button-secondary" href="blocks.php">К списку блоков</a>
			</div>
		</section>
		<section class="lt-admin-panel">
			<form class="lt-admin-form" method="post" action="blocks.php?act=del&bid=<?=$bid;?>">
				<?=lt_csrf_input('blocks_admin');?>
				<div class="lt-admin-notice lt-admin-notice-warning">Проверьте, что этот блок не нужен в текущей теме. Файл блока не удаляется, удаляется только запись из базы.</div>
				<div class="lt-admin-actions">
					<button class="lt-admin-button lt-admin-danger" type="submit">Удалить блок</button>
					<a class="lt-admin-link-button lt-admin-button-secondary" href="blocks.php">Отмена</a>
				</div>
			</form>
		</section>
	</div>
	<?php
	foot();
	die();
}

if ($act === 'add') {
	$bid = (int) ($_GET['bid'] ?? 0);
	$block = array(
		'title' => '',
		'blockfile' => '',
		'position' => '',
		'active' => 1,
		'type' => 'all',
		'which' => 'all',
	);

	if ($bid) {
		$block = $db->super_query("SELECT * FROM orbital_blocks WHERE bid=".$bid." LIMIT 1");
		if (empty($block['bid'])) {
			err('Ошибка', 'Данный блок не найден', 1);
		}
	}

	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		if (!lt_csrf_validate('blocks_admin')) {
			err('Ошибка', 'Защитный токен устарел. Обновите страницу и повторите действие.', 1);
		}

		$positions = lt_blocks_positions();
		$types = lt_blocks_types();

		$title = trim((string) ($_POST['title'] ?? ''));
		if ($title === '') {
			err('Ошибка', 'Вы не ввели название блока', 1);
		}
		if (strlen($title) > 60) {
			err('Ошибка', 'Название превышает 60 символов', 1);
		}

		$blockfile = basename(trim((string) ($_POST['blockfile'] ?? '')));
		if ($blockfile === '' || !is_file(__DIR__.'/blocks/'.$blockfile)) {
			err('Ошибка', 'Выберите существующий файл из папки blocks/', 1);
		}

		$position = trim((string) ($_POST['position'] ?? ''));
		if (empty($positions[$position])) {
			err('Ошибка', 'Выберите корректную позицию блока', 1);
		}

		$type = trim((string) ($_POST['type'] ?? 'all'));
		if (empty($types[$type])) {
			err('Ошибка', 'Выберите корректную аудиторию блока', 1);
		}

		$active = ((int) ($_POST['active'] ?? 0) === 1 ? 1 : 0);
		$which = lt_blocks_normalize_which($_POST['which'] ?? 'all');

		$fields = array(
			"title='".$db->safesql($title)."'",
			"blockfile='".$db->safesql($blockfile)."'",
			"position='".$db->safesql($position)."'",
			"active=".$active,
			"type='".$db->safesql($type)."'",
			"which='".$db->safesql($which)."'",
		);

		if (!$bid) {
			$weight = $db->super_query("SELECT weight AS c FROM orbital_blocks WHERE position='".$db->safesql($position)."' ORDER BY weight DESC LIMIT 1");
			$fields[] = 'weight='.(((int) ($weight['c'] ?? 0)) + 1);
			$db->query("INSERT INTO orbital_blocks SET ".implode(', ', $fields));
		} else {
			if ((string) $block['position'] !== $position) {
				$weight = $db->super_query("SELECT weight AS c FROM orbital_blocks WHERE position='".$db->safesql($position)."' ORDER BY weight DESC LIMIT 1");
				$fields[] = 'weight='.(((int) ($weight['c'] ?? 0)) + 1);
			}

			$db->query("UPDATE orbital_blocks SET ".implode(', ', $fields)." WHERE bid=".$bid);
		}

		lt_blocks_reindex_position($position);
		$memcached->delete('block_'.$position, 0);

		if ($bid && (string) $block['position'] !== $position) {
			lt_blocks_reindex_position($block['position']);
			$memcached->delete('block_'.$block['position'], 0);
		}

		header('Location: blocks.php?status=1&position='.urlencode($position));
		die();
	}

	lt_blocks_render_form(($bid ? 'Редактирование блока' : 'Добавление блока'), $bid, $block);
}

$position = trim((string) ($_GET['position'] ?? 'all'));
$positions = lt_blocks_positions();
if ($position !== 'all' && empty($positions[$position])) {
	$position = 'all';
}

if ($position === 'all') {
	foreach (array_keys($positions) as $positionKey) {
		lt_blocks_reindex_position($positionKey);
	}
	$sql = $db->query("SELECT * FROM orbital_blocks WHERE blockfile <> 'block-vkontakte.php' ORDER BY FIELD(position, 'l', 'c', 'd', 'r'), weight ASC, bid ASC");
} else {
	lt_blocks_reindex_position($position);
	$sql = $db->query("SELECT * FROM orbital_blocks WHERE position='".$db->safesql($position)."' AND blockfile <> 'block-vkontakte.php' ORDER BY weight ASC, bid ASC");
}

$blocks = array();
while ($row = $db->get_row($sql)) {
	$blocks[] = $row;
}
$db->free($sql);

$blocksByPosition = array();
foreach ($positions as $positionKey => $positionLabel) {
	$blocksByPosition[$positionKey] = array();
}
foreach ($blocks as $block) {
	$blockPosition = (string) ($block['position'] ?? '');
	if (!isset($blocksByPosition[$blockPosition])) {
		$blocksByPosition[$blockPosition] = array();
	}
	$blocksByPosition[$blockPosition][] = $block;
}

$notice = lt_blocks_notice($status);

head('Управление блоками');
?>
<div class="lt-admin-page">
	<section class="lt-admin-hero">
		<h1>Блоки сайта</h1>
		<p class="lt-admin-lead">Управление блоками теперь показывает все зоны сразу, отмечает выключенные и отсутствующие файлы, а сортировку делает понятной по выбранной позиции.</p>
		<div class="lt-admin-actions">
			<a class="lt-admin-link-button" href="blocks.php?act=add">Добавить блок</a>
			<a class="lt-admin-link-button lt-admin-button-secondary" href="admin.php?tab=content">Назад в админку</a>
		</div>
	</section>

	<?php if ($notice !== '') { ?>
	<div class="lt-admin-notice lt-admin-notice-success"><?=$notice;?></div>
	<?php } ?>
	<?php if (empty($config['blocks_use'])) { ?>
	<div class="lt-admin-notice lt-admin-notice-warning">Блочная система выключена в настройках. Редактировать блоки можно, но пользователи их не увидят до включения параметра “Использовать блоки”.</div>
	<?php } ?>

	<nav class="lt-admin-tabs">
		<a class="lt-admin-tab<?=($position === 'all' ? ' lt-admin-tab-active' : '');?>" href="blocks.php">Все позиции</a>
		<?php foreach ($positions as $positionKey => $positionLabel) { ?>
		<a class="lt-admin-tab<?=($position === $positionKey ? ' lt-admin-tab-active' : '');?>" href="blocks.php?position=<?=$positionKey;?>"><?=$positionLabel;?></a>
		<?php } ?>
	</nav>

	<?php if (!$blocks) { ?>
	<section class="lt-admin-panel">
		<div class="lt-admin-empty">В базе пока нет блоков. Нажмите “Добавить блок” и выберите файл из папки <span class="lt-admin-code">blocks/</span>.</div>
	</section>
	<?php } else { ?>
	<?php foreach ($blocksByPosition as $positionKey => $positionBlocks) { ?>
	<?php if ($position !== 'all' && $position !== $positionKey) { continue; } ?>
	<?php if (!$positionBlocks) { continue; } ?>
	<section class="lt-admin-panel">
		<h2><?=htmlspecialchars($positions[$positionKey] ?? 'Неизвестная позиция', ENT_QUOTES, 'UTF-8');?></h2>
		<p class="lt-admin-panel-text">Порядок влияет только внутри этой зоны. Для перемещения вверх или вниз откройте конкретную позицию.</p>
		<div class="lt-admin-table-wrap lt-table-scroll">
			<table class="lt-table lt-table-compact lt-table-actions lt-admin-table">
				<thead>
					<tr>
						<th>ID</th>
						<th>Блок</th>
						<th>Статус</th>
						<th>Кому</th>
						<th>Где</th>
						<th>Порядок</th>
						<th>Действия</th>
					</tr>
				</thead>
				<tbody>
					<?php $total = count($positionBlocks); ?>
					<?php foreach ($positionBlocks as $index => $block) { ?>
					<?php
					$fileExists = is_file(__DIR__.'/blocks/'.(string) $block['blockfile']);
					$isFirst = ($index === 0);
					$isLast = ($index === $total - 1);
					?>
					<tr class="js-block-row" data-bid="<?=(int) $block['bid'];?>">
						<td><span class="lt-admin-code">#<?=(int) $block['bid'];?></span></td>
						<td>
							<strong><?=htmlspecialchars($block['title'], ENT_QUOTES, 'UTF-8');?></strong><br>
							<span class="lt-admin-muted"><?=htmlspecialchars($block['blockfile'], ENT_QUOTES, 'UTF-8');?></span>
							<?php if (!$fileExists) { ?>
							<br><span class="lt-admin-status lt-admin-status-warning">файл отсутствует</span>
							<?php } ?>
						</td>
						<td><span class="lt-admin-status <?=(!empty($block['active']) ? 'lt-admin-status-on' : 'lt-admin-status-off');?>"><?=(!empty($block['active']) ? 'Активен' : 'Выключен');?></span></td>
						<td><?=htmlspecialchars(lt_blocks_types()[$block['type']] ?? 'Неизвестно', ENT_QUOTES, 'UTF-8');?></td>
						<td><?=lt_blocks_render_which($block['which']);?></td>
						<td>
							<?php if ($position === 'all') { ?>
							<span class="lt-admin-muted">Выберите позицию</span>
							<?php } else { ?>
							<div class="lt-admin-inline-actions">
								<a href="#" class="js-block-move js-move-up" data-direction="up" style="<?=($isFirst ? 'display:none;' : '');?>">Вверх</a>
								<a href="#" class="js-block-move js-move-down" data-direction="down" style="<?=($isLast ? 'display:none;' : '');?>">Вниз</a>
							</div>
							<?php } ?>
						</td>
						<td>
							<div class="lt-admin-inline-actions">
								<a href="blocks.php?act=add&bid=<?=(int) $block['bid'];?>">Редактировать</a>
								<a href="blocks.php?act=del&bid=<?=(int) $block['bid'];?>">Удалить</a>
							</div>
						</td>
					</tr>
					<?php } ?>
				</tbody>
			</table>
		</div>
	</section>
	<?php } ?>
	<?php } ?>
</div>

<script>
(function ($) {
	if (!$) {
		return;
	}

	function refreshMoveControls() {
		var rows = $('.js-block-row');
		rows.find('.js-move-up, .js-move-down').show();
		rows.first().find('.js-move-up').hide();
		rows.last().find('.js-move-down').hide();
	}

	$('.js-block-move').on('click', function () {
		var link = $(this);
		var row = link.closest('.js-block-row');
		var direction = link.attr('data-direction');

		if (link.data('busy') || !row.length) {
			return false;
		}

		link.data('busy', 1);

		$.ajax({
			type: 'POST',
			url: 'blocks.php?act=move',
			dataType: 'json',
			data: {
				bid: row.attr('data-bid'),
				direction: direction
			},
			success: function (response) {
				if (!response || parseInt(response.ok, 10) !== 1) {
					alert(response && response.message ? response.message : 'Ошибка перемещения блока.');
					return;
				}

				if (direction === 'up') {
					var prev = row.prev('.js-block-row');
					if (prev.length) {
						prev.before(row);
					}
				} else {
					var next = row.next('.js-block-row');
					if (next.length) {
						next.after(row);
					}
				}

				refreshMoveControls();
			},
			error: function () {
				alert('Сервер временно недоступен. Попробуйте еще раз.');
			},
			complete: function () {
				link.data('busy', 0);
			}
		});

		return false;
	});

	refreshMoveControls();
})(window.jQuery);
</script>
<?php
foot();
