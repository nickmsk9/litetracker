<?php
/*
===================================================================
LiteTracker Source
===================================================================
by Nick
-------------------------------------------------------------------
Назначение: Редактирование категорий
===================================================================
*/

require 'system/init.php';

is_login();

if (empty($PRIV['cats'])) {
	err($language['default_1'], $language['default_12'], 1);
}

$act = trim((string) ($_GET['act'] ?? ''));
$status = trim((string) ($_GET['status'] ?? ''));

function lt_admin_categories_template_labels()
{
	global $language;

	return array(
		0 => 'Без отдельного шаблона',
		1 => ($language['cats_18'] ?? 'Шаблон 1'),
		2 => ($language['cats_19'] ?? 'Шаблон 2'),
		3 => ($language['cats_20'] ?? 'Шаблон 3'),
		4 => ($language['cats_21'] ?? 'Шаблон 4'),
		5 => ($language['cats_22'] ?? 'Шаблон 5'),
		6 => ($language['cats_23'] ?? 'Шаблон 6'),
	);
}

function lt_admin_categories_notice($status)
{
	$messages = array(
		'1' => 'Категория добавлена.',
		'2' => 'Категория удалена.',
		'3' => 'Категория обновлена.',
		'4' => 'Релизы перенесены.',
	);

	return ($messages[$status] ?? '');
}

function lt_admin_categories_options($selected = 0, $exclude = 0)
{
	global $db;

	$html = '';
	$sql = $db->query("SELECT id, name FROM categories ORDER BY name ASC");
	while ($row = $db->get_row($sql)) {
		$id = (int) $row['id'];
		if ($exclude && $id === (int) $exclude) {
			continue;
		}

		$html .= '<option value="'.$id.'"'.($id === (int) $selected ? ' selected' : '').'>'.htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8').'</option>';
	}
	$db->free($sql);

	return $html;
}

function lt_admin_categories_template_select($selected = 0)
{
	$html = '';
	foreach (lt_admin_categories_template_labels() as $id => $label) {
		$html .= '<option value="'.(int) $id.'"'.((int) $selected === (int) $id ? ' selected' : '').'>'.htmlspecialchars($label, ENT_QUOTES, 'UTF-8').'</option>';
	}

	return $html;
}

function lt_admin_categories_render_form($title, $action, $category)
{
	$id = (int) ($category['id'] ?? 0);
	$name = (string) ($category['name'] ?? '');
	$template = (int) ($category['template'] ?? 0);

	head($title);
	?>
	<div class="lt-admin-page">
		<section class="lt-admin-hero">
			<h1><?=$title;?></h1>
			<p class="lt-admin-lead">Категории теперь управляются без картинок: только название, шаблон карточки и понятные действия. Старое поле изображения в базе больше не заполняется из этой формы.</p>
			<div class="lt-admin-actions">
				<a class="lt-admin-link-button lt-admin-button-secondary" href="categories.php">К списку категорий</a>
			</div>
		</section>

		<section class="lt-admin-panel">
			<form class="lt-admin-form" method="post" action="<?=$action;?>">
				<?=lt_csrf_input('categories_admin');?>
				<div class="lt-admin-form-grid">
					<div class="lt-admin-field">
						<label class="lt-admin-label" for="category-name">Название</label>
						<input class="lt-admin-input" id="category-name" type="text" name="name" value="<?=htmlspecialchars($name, ENT_QUOTES, 'UTF-8');?>" maxlength="120" required>
						<div class="lt-admin-help">Например: Фильмы, Игры, Музыка. Это название увидят пользователи в каталоге.</div>
					</div>
					<div class="lt-admin-field">
						<label class="lt-admin-label" for="category-template">Шаблон релиза</label>
						<select class="lt-admin-select" id="category-template" name="template">
							<?=lt_admin_categories_template_select($template);?>
						</select>
						<div class="lt-admin-help">Шаблон определяет набор полей при загрузке релиза. Если не уверены, оставьте вариант без отдельного шаблона.</div>
					</div>
				</div>
				<div class="lt-admin-actions">
					<button class="lt-admin-button" type="submit"><?=($id ? 'Сохранить категорию' : 'Добавить категорию');?></button>
					<a class="lt-admin-link-button lt-admin-button-secondary" href="categories.php">Отмена</a>
				</div>
			</form>
		</section>
	</div>
	<?php
	foot();
	die();
}

if ($act === 'location') {
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		if (!lt_csrf_validate('categories_admin')) {
			err('Ошибка', 'Защитный токен устарел. Обновите страницу и повторите действие.', 1);
		}

		$locationFrom = (int) ($_POST['location_1'] ?? 0);
		$locationTo = (int) ($_POST['location_2'] ?? 0);

		if (!$locationFrom) {
			err($language['default_1'], $language['cats_1'], 1);
		}

		if (!$locationTo) {
			err($language['default_1'], $language['cats_2'], 1);
		}

		if ($locationFrom !== $locationTo) {
			$db->query("UPDATE torrents SET id_category=".$locationTo." WHERE id_category=".$locationFrom);
		}

		header('Location: categories.php?status=4');
		die();
	}

	head('Перенос релизов');
	?>
	<div class="lt-admin-page">
		<section class="lt-admin-hero">
			<h1>Перенос релизов</h1>
			<p class="lt-admin-lead">Перемещает все релизы из одной категории в другую. Пример: если закрываете старую категорию “HDTV”, можно перенести все релизы в “Фильмы”.</p>
			<div class="lt-admin-actions">
				<a class="lt-admin-link-button lt-admin-button-secondary" href="categories.php">К списку категорий</a>
			</div>
		</section>
		<section class="lt-admin-panel">
			<form class="lt-admin-form" method="post" action="categories.php?act=location">
				<?=lt_csrf_input('categories_admin');?>
				<div class="lt-admin-form-grid">
					<div class="lt-admin-field">
						<label class="lt-admin-label" for="location-from">Откуда</label>
						<select class="lt-admin-select" id="location-from" name="location_1" required>
							<option value="0">Выберите исходную категорию</option>
							<?=lt_admin_categories_options();?>
						</select>
					</div>
					<div class="lt-admin-field">
						<label class="lt-admin-label" for="location-to">Куда</label>
						<select class="lt-admin-select" id="location-to" name="location_2" required>
							<option value="0">Выберите новую категорию</option>
							<?=lt_admin_categories_options();?>
						</select>
					</div>
				</div>
				<div class="lt-admin-actions">
					<button class="lt-admin-button" type="submit">Перенести релизы</button>
				</div>
			</form>
		</section>
	</div>
	<?php
	foot();
	die();
}

if ($act === 'edit' && !empty($_GET['id'])) {
	$id = (int) $_GET['id'];
	$category = $db->super_query("SELECT * FROM categories WHERE id=".$id." LIMIT 1");
	if (empty($category['id'])) {
		err($language['default_1'], $language['cats_7'], 1);
	}

	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		if (!lt_csrf_validate('categories_admin')) {
			err('Ошибка', 'Защитный токен устарел. Обновите страницу и повторите действие.', 1);
		}

		$name = trim((string) ($_POST['name'] ?? ''));
		if ($name === '') {
			err($language['default_1'], $language['cats_8'], 1);
		}

		$template = (int) ($_POST['template'] ?? 0);
		if ($template < 0 || $template > 6) {
			$template = 0;
		}

		$db->query("UPDATE categories SET name='".$db->safesql($name)."', template=".$template." WHERE id=".$id);
		$memcached->delete('upload_categories');

		header('Location: categories.php?status=3');
		die();
	}

	lt_admin_categories_render_form('Редактирование категории', 'categories.php?act=edit&id='.$id, $category);
}

if ($act === 'del' && !empty($_GET['id'])) {
	$id = (int) $_GET['id'];
	$category = $db->super_query("SELECT * FROM categories WHERE id=".$id." LIMIT 1");
	if (empty($category['id'])) {
		err($language['default_1'], $language['cats_7'], 1);
	}

	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		if (!lt_csrf_validate('categories_admin')) {
			err('Ошибка', 'Защитный токен устарел. Обновите страницу и повторите действие.', 1);
		}

		$location = (int) ($_POST['location'] ?? 0);
		if ($location > 0) {
			$target = $db->super_query("SELECT id FROM categories WHERE id=".$location." LIMIT 1");
			if (empty($target['id'])) {
				err($language['default_1'], $language['cats_25'], 1);
			}

			$db->query("UPDATE torrents SET id_category=".$location." WHERE id_category=".$id);
		} else {
			$db->query("DELETE FROM torrents WHERE id_category=".$id);
		}

		$db->query("DELETE FROM categories WHERE id=".$id);
		$memcached->delete('upload_categories');

		header('Location: categories.php?status=2');
		die();
	}

	head('Удаление категории');
	?>
	<div class="lt-admin-page">
		<section class="lt-admin-hero">
			<h1>Удаление категории</h1>
			<p class="lt-admin-lead">Вы удаляете категорию “<?=htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8');?>”. Перед удалением можно перенести релизы в другую категорию, чтобы ничего не потерять.</p>
			<div class="lt-admin-actions">
				<a class="lt-admin-link-button lt-admin-button-secondary" href="categories.php">К списку категорий</a>
			</div>
		</section>
		<section class="lt-admin-panel">
			<form class="lt-admin-form" method="post" action="categories.php?act=del&id=<?=$id;?>">
				<?=lt_csrf_input('categories_admin');?>
				<div class="lt-admin-field">
					<label class="lt-admin-label" for="delete-location">Что сделать с релизами</label>
					<select class="lt-admin-select" id="delete-location" name="location">
						<option value="0">Удалить релизы вместе с категорией</option>
						<?=lt_admin_categories_options(0, $id);?>
					</select>
					<div class="lt-admin-help">Безопасный вариант: выбрать новую категорию и перенести релизы туда. Удаление релизов необратимо.</div>
				</div>
				<div class="lt-admin-actions">
					<button class="lt-admin-button lt-admin-danger" type="submit">Удалить категорию</button>
					<a class="lt-admin-link-button lt-admin-button-secondary" href="categories.php">Отмена</a>
				</div>
			</form>
		</section>
	</div>
	<?php
	foot();
	die();
}

if ($act === 'add') {
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		if (!lt_csrf_validate('categories_admin')) {
			err('Ошибка', 'Защитный токен устарел. Обновите страницу и повторите действие.', 1);
		}

		$name = trim((string) ($_POST['name'] ?? ''));
		if ($name === '') {
			err($language['default_1'], $language['cats_8'], 1);
		}

		$template = (int) ($_POST['template'] ?? 0);
		if ($template < 0 || $template > 6) {
			$template = 0;
		}

		$db->query("INSERT INTO categories(name, image, template, date) VALUES ('".$db->safesql($name)."', '', ".$template.", NOW())");
		$memcached->delete('upload_categories');

		header('Location: categories.php?status=1');
		die();
	}

	lt_admin_categories_render_form('Добавление категории', 'categories.php?act=add', array());
}

$categories = array();
$sql = $db->query("SELECT c.*, (SELECT COUNT(*) FROM torrents WHERE id_category = c.id) AS releases_count
				  FROM categories AS c
				  ORDER BY c.date ASC, c.name ASC");
while ($row = $db->get_row($sql)) {
	$categories[] = $row;
}
$db->free($sql);

head('Категории');
$notice = lt_admin_categories_notice($status);
?>
<div class="lt-admin-page">
	<section class="lt-admin-hero">
		<h1>Категории</h1>
		<p class="lt-admin-lead">Новый вид без картинок: список показывает название, шаблон, количество релизов и прямые действия. Это быстрее читать и проще поддерживать.</p>
		<div class="lt-admin-actions">
			<a class="lt-admin-link-button" href="categories.php?act=add">Добавить категорию</a>
			<a class="lt-admin-link-button lt-admin-button-secondary" href="categories.php?act=location">Перенести релизы</a>
			<a class="lt-admin-link-button lt-admin-button-secondary" href="admin.php?tab=content">Назад в админку</a>
		</div>
	</section>

	<?php if ($notice !== '') { ?>
	<div class="lt-admin-notice lt-admin-notice-success"><?=$notice;?></div>
	<?php } ?>

	<section class="lt-admin-panel">
		<h2>Список категорий</h2>
		<p class="lt-admin-panel-text">Редактирование меняет название и шаблон загрузки. Если нужно объединить разделы, используйте перенос релизов.</p>
		<?php if (!$categories) { ?>
		<div class="lt-admin-empty" style="margin-top:14px;">Категорий пока нет.</div>
		<?php } else { ?>
		<div class="lt-admin-table-wrap lt-table-scroll">
			<table class="lt-table lt-table-compact lt-table-actions lt-admin-table">
				<thead>
					<tr>
						<th>ID</th>
						<th>Название</th>
						<th>Шаблон</th>
						<th>Релизы</th>
						<th>Создана</th>
						<th>Действия</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($categories as $category) { ?>
					<?php
					$templateId = (int) ($category['template'] ?? 0);
					$templateLabels = lt_admin_categories_template_labels();
					?>
					<tr>
						<td><span class="lt-admin-code">#<?=(int) $category['id'];?></span></td>
						<td><a href="browse.php?id_category=<?=(int) $category['id'];?>"><?=htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8');?></a></td>
						<td><?=htmlspecialchars($templateLabels[$templateId] ?? 'Неизвестный шаблон', ENT_QUOTES, 'UTF-8');?></td>
						<td><?=number_format((int) ($category['releases_count'] ?? 0));?></td>
						<td><?=(!empty($category['date']) ? convent_date($category['date']) : 'Не указана');?></td>
						<td>
							<div class="lt-admin-inline-actions">
								<a href="categories.php?act=edit&id=<?=(int) $category['id'];?>">Редактировать</a>
								<a href="categories.php?act=del&id=<?=(int) $category['id'];?>">Удалить</a>
							</div>
						</td>
					</tr>
					<?php } ?>
				</tbody>
			</table>
		</div>
		<?php } ?>
	</section>
</div>
<?php
foot();
