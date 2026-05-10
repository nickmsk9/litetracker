<?php
/*
===================================================================
LiteTracker Source
===================================================================
by Nick
-------------------------------------------------------------------
Назначение: Загрузка торрента
===================================================================
*/

require 'system/init.php';
require 'system/functions/functions.benc.php';

$GLOBALS['LITETRACKER_HIDE_TOP_BLOCKS'] = true;
$GLOBALS['LITETRACKER_HIDE_BOTTOM_BLOCKS'] = true;
$GLOBALS['LITETRACKER_HIDE_STANDARD_SIDEBAR'] = true;

function lt_upload_categories_list()
{
	return categories_array();
}

function lt_upload_category_info($catid)
{
	global $db;

	$catid = (int) $catid;
	if ($catid <= 0) {
		return array();
	}

	return (array) $db->super_query("SELECT * FROM categories WHERE id = ".$catid." LIMIT 1");
}

function lt_upload_category_name($categories, $catid)
{
	$catid = (int) $catid;

	foreach ((array) $categories as $category) {
		if ((int) ($category['id'] ?? 0) !== $catid) {
			continue;
		}

		return trim((string) ($category['name'] ?? ''));
	}

	return '';
}

function lt_upload_default_category_id($categories)
{
	foreach ((array) $categories as $category) {
		$name = trim((string) ($category['name'] ?? ''));
		if (lt_torrent_description_template_key($name) === 'movies') {
			return (int) ($category['id'] ?? 0);
		}
	}

	return (int) ($categories[0]['id'] ?? 0);
}

function lt_upload_template_textarea_labels()
{
	return array(
		'Описание',
		'В ролях',
		'Треклист',
		'Системные требования',
	);
}

function lt_upload_template_field_type($label)
{
	return (in_array(trim((string) $label), lt_upload_template_textarea_labels(), true) ? 'textarea' : 'text');
}

function lt_upload_template_manual_fields($categoryNameOrKey, $values = array())
{
	$template = lt_torrent_description_template($categoryNameOrKey);
	$items = (array) ($template['items'] ?? array());
	$result = array();
	$values = (is_array($values) ? $values : array());

	foreach ($items as $item) {
		$type = trim((string) ($item['type'] ?? 'field'));
		$label = trim((string) ($item['label'] ?? ''));
		$auto = trim((string) ($item['auto'] ?? ''));

		if ($label === '' || $type === 'section' || $auto !== '') {
			continue;
		}

		$result[] = array(
			'label' => $label,
			'field_type' => lt_upload_template_field_type($label),
			'value' => (string) ($values[$label] ?? ''),
		);
	}

	return $result;
}

function lt_upload_primary_description_label($categoryNameOrKey)
{
	$template = lt_torrent_description_template($categoryNameOrKey);
	$items = (array) ($template['items'] ?? array());

	foreach ($items as $item) {
		$type = trim((string) ($item['type'] ?? 'field'));
		$label = trim((string) ($item['label'] ?? ''));
		if ($type === 'field' && $label === 'Описание') {
			return $label;
		}
	}

	return '';
}

function lt_upload_build_description($categoryNameOrKey, $templateValues, $autoValues = array())
{
	$template = lt_torrent_description_template($categoryNameOrKey);
	$items = (array) ($template['items'] ?? array());
	$templateValues = (is_array($templateValues) ? $templateValues : array());
	$autoValues = (is_array($autoValues) ? $autoValues : array());
	$lines = array();

	foreach ($items as $item) {
		$type = trim((string) ($item['type'] ?? 'field'));
		$label = trim((string) ($item['label'] ?? ''));
		$auto = trim((string) ($item['auto'] ?? ''));
		if ($label === '') {
			continue;
		}

		if ($type === 'section') {
			if ($lines && end($lines) !== '') {
				$lines[] = '';
			}

			$lines[] = '[u]'.$label.'[/u]';
			continue;
		}

		$value = '';
		if ($auto !== '' && isset($autoValues[$auto])) {
			$value = trim((string) $autoValues[$auto]);
		} else {
			$value = trim((string) ($templateValues[$label] ?? ''));
		}

		if (strpos($value, "\n") !== false) {
			$lines[] = '[b]'.$label.':[/b]'.($value !== '' ? "\n".$value : '');
			continue;
		}

		$lines[] = '[b]'.$label.':[/b]'.($value !== '' ? ' '.$value : '');
	}

	return trim(implode("\n", $lines));
}

function lt_upload_next_torrent_id()
{
	global $db;

	$row = $db->super_query("SHOW TABLE STATUS LIKE 'torrents'");

	return (!empty($row['Auto_increment']) ? (int) $row['Auto_increment'] : 0);
}

function lt_upload_ensure_directory($path)
{
	if (is_dir($path)) {
		return true;
	}

	$created = mkdir($path, 0777, true);
	if (!$created && !is_dir($path)) {
		return false;
	}
	return true;
}

function lt_upload_image_extension($filename)
{
	$ext = strtolower((string) pathinfo((string) $filename, PATHINFO_EXTENSION));
	$allowed = array('jpg', 'jpeg', 'png', 'gif');

	return (in_array($ext, $allowed, true) ? $ext : '');
}

function lt_upload_validate_image($file, $label)
{
	global $config, $language;

	$name = (string) ($file['name'] ?? '');
	$tmp = (string) ($file['tmp_name'] ?? '');
	$size = (int) ($file['size'] ?? 0);

	if ($name === '' || $tmp === '' || !is_uploaded_file($tmp)) {
		err($language['default_1'], $label.' не был загружен.', 1);
	}

	$extension = lt_upload_image_extension($name);
	if ($extension === '') {
		err($language['default_1'], $label.' должен быть в формате JPG, PNG или GIF.', 1);
	}

	if ($size <= 0 || $size > $config['max_size_image']) {
		err($language['default_1'], $label.' превышает допустимый размер '.mksize($config['max_size_image']).'.', 1);
	}

	$imageInfo = getimagesize($tmp);
	if (!$imageInfo || empty($imageInfo[2]) || !in_array((int) $imageInfo[2], array(IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG), true)) {
		err($language['default_1'], $label.' не похож на изображение.', 1);
	}

	return ($extension === 'jpeg' ? 'jpg' : $extension);
}

function lt_upload_move_uploaded_image($file, $directory, $targetName, $label)
{
	global $language;

	if (!lt_upload_ensure_directory($directory)) {
		err($language['default_1'], 'Не удалось подготовить каталог для загрузки файлов.', 1);
	}

	if (!move_uploaded_file((string) $file['tmp_name'], $directory.$targetName)) {
		err($language['default_1'], 'Не удалось сохранить '.$label.'.', 1);
	}

	return $targetName;
}

function lt_upload_retarget_asset($directory, $oldName, $newName)
{
	if ($oldName === '' || $oldName === $newName) {
		return $oldName;
	}

	$oldPath = $directory.$oldName;
	$newPath = $directory.$newName;

	if (!is_file($oldPath)) {
		return $oldName;
	}

	if (rename($oldPath, $newPath)) {
		return $newName;
	}

	return $oldName;
}

function lt_upload_collect_screenshots($nextId)
{
	$screenshots = array();
	$files = ($_FILES['screenshot'] ?? array());
	$names = (isset($files['name']) && is_array($files['name']) ? $files['name'] : array());
	$tmpNames = (isset($files['tmp_name']) && is_array($files['tmp_name']) ? $files['tmp_name'] : array());
	$sizes = (isset($files['size']) && is_array($files['size']) ? $files['size'] : array());

	foreach ($names as $index => $name) {
		$name = trim((string) $name);
		if ($name === '') {
			continue;
		}

		$screenshots[] = array(
			'name' => $name,
			'tmp_name' => (string) ($tmpNames[$index] ?? ''),
			'size' => (int) ($sizes[$index] ?? 0),
		);
	}

	if (!$screenshots) {
		err('Ошибка', 'Загрузите хотя бы один скринлист.', 1);
	}

	if (count($screenshots) > 4) {
		err('Ошибка', 'Можно загрузить не больше 4 изображений в скринлист.', 1);
	}

	$result = array();
	foreach ($screenshots as $index => $screenshot) {
		$extension = lt_upload_validate_image($screenshot, 'Скринлист');
		$filename = $nextId.'_'.$index.'.'.$extension;
		$result[] = lt_upload_move_uploaded_image($screenshot, 'public/downloads/screens/', $filename, 'скринлист');
	}

	return $result;
}

function lt_upload_collect_tags($value)
{
	return lt_torrent_tags_to_string($value);
}

function lt_upload_save_tags($catid, $tags)
{
	global $db;

	$tagList = lt_torrent_tags_from_string($tags);
	if (!$tagList) {
		return;
	}

	$existing = array();
	$res = $db->query("SELECT name FROM tags WHERE category = ".(int) $catid);
	while ($row = $db->get_row($res)) {
		$key = (function_exists('mb_strtolower') ? mb_strtolower(trim((string) $row['name']), 'UTF-8') : strtolower(trim((string) $row['name'])));
		$existing[$key] = trim((string) $row['name']);
	}

	foreach ($tagList as $tag) {
		$key = (function_exists('mb_strtolower') ? mb_strtolower($tag, 'UTF-8') : strtolower($tag));
		if (isset($existing[$key])) {
			$db->pquery("UPDATE tags SET howmuch = (howmuch + 1) WHERE category = ".(int) $catid." AND name = ?", 's', [$existing[$key]]);
			continue;
		}

		$db->pquery("INSERT INTO tags (category, name, howmuch) VALUES (".(int) $catid.", ?, 1)", 's', [$tag]);
		$existing[$key] = $tag;
	}
}

function lt_upload_parse_torrent()
{
	global $db, $language;

	$file = (isset($_FILES['file']) && is_array($_FILES['file']) ? $_FILES['file'] : array());
	$name = trim((string) ($file['name'] ?? ''));
	$tmpname = (string) ($file['tmp_name'] ?? '');

	if ($name === '') {
		err($language['default_1'], $language['upload_19'], 1);
	}

	if (!validfilename($name)) {
		err($language['default_1'], $language['upload_20'], 1);
	}

	if (!preg_match('/^(.+)\.torrent$/si', $name)) {
		err($language['default_1'], $language['upload_21'], 1);
	}

	if (!is_uploaded_file($tmpname)) {
		err($language['default_1'], $language['upload_22'], 1);
	}

	if (!filesize($tmpname)) {
		err($language['default_1'], $language['upload_23'], 1);
	}

	$dict = lt_torrent_decode_file($tmpname);
	if (!is_array($dict)) {
		err('Ошибка', 'Не удалось прочитать torrent-файл.', 1);
	}
	unset($dict['value']['nodes']);
	unset($dict['value']['azureus_properties']);
	unset($dict['value']['comment']);
	unset($dict['value']['created by']);
	unset($dict['value']['publisher']);
	unset($dict['value']['publisher.windows-1251']);
	unset($dict['value']['publisher-url']);
	unset($dict['value']['publisher-url.windows-1251']);

	$anarray = get_announce_urls($dict);
	$anarray = (is_array($anarray) ? array_values(array_unique($anarray)) : array());

	$dict = bdec(benc($dict));
	list($info) = dict_check($dict, "info");
	list($dname, $plen, $pieces) = dict_check($info, "name(string):piece length(integer):pieces(string)");

	if (strlen($pieces) % 20 != 0) {
		err('Ошибка', 'Некорректный список кусков в torrent-файле.', 1);
	}

	$filelist = array();
	$totallen = dict_get($info, "length", "integer");
	if (isset($totallen)) {
		$filelist[] = array($dname, $totallen);
		$type = 'single';
	} else {
		$flist = dict_get($info, "files", "list");
		if (!isset($flist) || !count($flist)) {
			err('Ошибка', 'В torrent-файле не найден список файлов.', 1);
		}

		$totallen = 0;
		foreach ($flist as $fn) {
			list($ll, $ff) = dict_check($fn, "length(integer):path(list)");
			$totallen += $ll;
			$ffa = array();
			foreach ($ff as $ffe) {
				if ($ffe['type'] != 'string') {
					err('Ошибка', 'Ошибка в структуре torrent-файла.', 1);
				}
				$ffa[] = $ffe['value'];
			}
			if (!$ffa) {
				err('Ошибка', 'Ошибка в путях файлов torrent-раздачи.', 1);
			}
			$filename = implode('/', $ffa);
			if ($filename === 'Thumbs.db') {
				err($language['default_1'], $language['upload_44'], 1);
			}
			$filelist[] = array($filename, $ll);
		}
		$type = 'multi';
	}

	$infohash = sha1($info['string']);
	$exists = $db->psuper_query("SELECT id FROM torrents WHERE infohash = ? LIMIT 1", 's', [$infohash]);
	if (!empty($exists['id'])) {
		err($language['default_1'], 'Данный релиз уже есть', 1);
	}

	return array(
		'file' => $file,
		'filename' => $name,
		'tmp_name' => $tmpname,
		'infohash' => $infohash,
		'filelist' => $filelist,
		'total_length' => (int) $totallen,
		'type' => $type,
		'trackers' => $anarray,
	);
}

is_login();

if (!$PRIV['upload']) {
	err($language['default_1'], $language['upload_41'], 1);
}

$metadataSchema = lt_torrent_metadata_schema();
$categories = lt_upload_categories_list();
$defaultCategoryId = lt_upload_default_category_id($categories);
$defaultCategoryName = lt_upload_category_name($categories, $defaultCategoryId);
$typeOptionsMap = lt_torrent_type_options_map();
$defaultTypeOptions = lt_torrent_metadata_type_options_for_category($defaultCategoryName);
$defaultContentType = (string) key($defaultTypeOptions);

if (!$categories) {
	head('Загрузить торрент');
	msg('Ошибка', 'На трекере нет категорий для загрузки.', 'error');
	foot();
	die();
}

$defaults = array(
	'name' => '',
	'catid' => $defaultCategoryId,
	'content_type' => ($defaultContentType !== '' ? $defaultContentType : 'movie'),
	'tags' => '',
	'descr' => '',
	'template_values' => array(),
);

foreach ($metadataSchema as $group => $definition) {
	$defaults[$group] = array();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$form = $defaults;
	$form['name'] = trim((string) ($_POST['name'] ?? ''));
	$form['catid'] = (int) ($_POST['catid'] ?? 0);
	$form['content_type'] = trim((string) ($_POST['content_type'] ?? ''));
	$form['tags'] = trim((string) ($_POST['tags'] ?? ''));
	$form['template_values'] = (isset($_POST['template_values']) && is_array($_POST['template_values']) ? $_POST['template_values'] : array());

	foreach ($metadataSchema as $group => $definition) {
		if ($group === 'type') {
			$form[$group] = ($form['content_type'] !== '' ? array($form['content_type']) : array());
			continue;
		}

		$form[$group] = (isset($_POST[$group]) && is_array($_POST[$group]) ? $_POST[$group] : array());
	}

	$categoryInfo = lt_upload_category_info($form['catid']);
	if (!$categoryInfo) {
		err($language['default_1'], $language['upload_3'], 1);
	}

	$currentTypeOptions = lt_torrent_metadata_type_options_for_category((string) ($categoryInfo['name'] ?? ''));
	if ($currentTypeOptions) {
		$metadataSchema['type']['options'] = $currentTypeOptions;
	}

	$torrent = lt_upload_parse_torrent();

	if ($form['name'] === '') {
		err($language['default_1'], $language['upload_25'], 1);
	}

	$contentType = lt_torrent_metadata_normalize_values('type', $form['type']);
	if (!$contentType) {
		err('Ошибка', 'Выберите тип раздачи.', 1);
	}
	$contentType = $contentType[0];

	$metadataValues = array(
		'subtitles' => lt_torrent_metadata_csv('subtitles', $form['subtitles']),
		'language' => lt_torrent_metadata_csv('language', $form['language']),
		'genre' => lt_torrent_metadata_csv('genre', $form['genre']),
		'info' => lt_torrent_metadata_csv('info', $form['info']),
		'country' => lt_torrent_metadata_csv('country', $form['country']),
	);

	$autoDescriptionValues = array(
		'type' => lt_torrent_metadata_option_label('type', $contentType),
		'genre' => lt_torrent_metadata_format('genre', $metadataValues['genre'] ?? ''),
		'language' => lt_torrent_metadata_format('language', $metadataValues['language'] ?? ''),
		'subtitles' => lt_torrent_metadata_format('subtitles', $metadataValues['subtitles'] ?? ''),
		'country' => lt_torrent_metadata_format('country', $metadataValues['country'] ?? ''),
	);
	$primaryDescriptionLabel = lt_upload_primary_description_label((string) ($categoryInfo['name'] ?? ''));
	if ($primaryDescriptionLabel !== '' && trim((string) ($form['template_values'][$primaryDescriptionLabel] ?? '')) === '') {
		err($language['default_1'], $language['upload_26'], 1);
	}

	$form['descr'] = lt_upload_build_description((string) ($categoryInfo['name'] ?? ''), $form['template_values'], $autoDescriptionValues);
	if ($form['descr'] === '') {
		err($language['default_1'], $language['upload_26'], 1);
	}

	$nextId = lt_upload_next_torrent_id();
	if ($nextId <= 0) {
		err('Ошибка', 'Не удалось подготовить загрузку торрента.', 1);
	}

	$coverExtension = lt_upload_validate_image((array) ($_FILES['image'] ?? array()), 'Обложка');
	$coverName = lt_upload_move_uploaded_image((array) $_FILES['image'], 'public/downloads/images/', $nextId.'.'.$coverExtension, 'обложку');
	$screenshots = lt_upload_collect_screenshots($nextId);
	$tags = lt_upload_collect_tags($form['tags']);

	$screenshots[0] = ($screenshots[0] ?? '');
	$screenshots[1] = ($screenshots[1] ?? '');
	$screenshots[2] = ($screenshots[2] ?? '');
	$screenshots[3] = ($screenshots[3] ?? '');

	$externalTrackers = lt_torrent_external_trackers($torrent['trackers']);
	$isMultitracker = ($externalTrackers ? 1 : 0);

	$insert = $db->pquery(
		"INSERT INTO torrents
		(name, filename, num_files, type, size, descr, infohash, tags, id_category, id_user, added, image, multi, downloaded, completed, last_action, screen_1, screen_2, screen_3, screen_4, video_vkontakte, news, content_type, subtitles, languages, genres, meta_info, countries)
		VALUES
		(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, 0, 0, NOW(), ?, ?, ?, ?, '', 0, ?, ?, ?, ?, ?, ?)",
		'ssssisssiisissssssssss',
		[
			$form['name'], $torrent['filename'], count($torrent['filelist']), $torrent['type'],
			(int) $torrent['total_length'], $form['descr'], $torrent['infohash'], $tags,
			(int) $form['catid'], (int) $USER['id'], $coverName, (string) $isMultitracker,
			$screenshots[0], $screenshots[1], $screenshots[2], $screenshots[3],
			$contentType, $metadataValues['subtitles'], $metadataValues['language'],
			$metadataValues['genre'], $metadataValues['info'], $metadataValues['country'],
		],
		0
	);

	if (!$insert) {
		$coverPath = 'public/downloads/images/'.$coverName;
		if (is_file($coverPath)) {
			unlink($coverPath);
		}
		foreach ($screenshots as $screen) {
			if ($screen !== '') {
				$screenPath = 'public/downloads/screens/'.$screen;
				if (is_file($screenPath)) {
					unlink($screenPath);
				}
			}
		}
		err($language['default_1'], $language['upload_37'], 1);
	}

	$id = (int) $db->insert_id();

	if ($id !== $nextId) {
		$coverExtension = (string) pathinfo($coverName, PATHINFO_EXTENSION);
		$coverName = lt_upload_retarget_asset('public/downloads/images/', $coverName, $id.($coverExtension !== '' ? '.'.$coverExtension : ''));

		foreach ($screenshots as $index => $screenName) {
			if ($screenName === '') {
				continue;
			}

			$screenExtension = (string) pathinfo($screenName, PATHINFO_EXTENSION);
			$screenshots[$index] = lt_upload_retarget_asset('public/downloads/screens/', $screenName, $id.'_'.$index.($screenExtension !== '' ? '.'.$screenExtension : ''));
		}
	}

	$screenshots[0] = ($screenshots[0] ?? '');
	$screenshots[1] = ($screenshots[1] ?? '');
	$screenshots[2] = ($screenshots[2] ?? '');
	$screenshots[3] = ($screenshots[3] ?? '');

	$db->pquery(
		"UPDATE torrents SET image=?, screen_1=?, screen_2=?, screen_3=?, screen_4=? WHERE id=?",
		'sssssi',
		[$coverName, $screenshots[0], $screenshots[1], $screenshots[2], $screenshots[3], $id]
	);

	$db->query("DELETE FROM files WHERE id_torrent = ".$id);
	foreach ($torrent['filelist'] as $fileRow) {
		$db->pquery("INSERT INTO files (id_torrent, filename, size) VALUES (?, ?, ?)", 'isi', [$id, $fileRow[0], (int) $fileRow[1]]);
	}

	lt_torrent_store_trackers($id, $externalTrackers);

	lt_upload_save_tags($form['catid'], $tags);

	if (!lt_upload_ensure_directory('public/downloads/torrents/')) {
		err('Ошибка', 'Не удалось подготовить каталог для torrent-файлов.', 1);
	}

	if (!move_uploaded_file($torrent['tmp_name'], 'public/downloads/torrents/'.$id.'.torrent')) {
		err('Ошибка', 'Релиз добавлен, но torrent-файл не удалось сохранить на сервер.', 1);
	}

	lt_torrent_rewrite_file_announces('public/downloads/torrents/'.$id.'.torrent', lt_torrent_site_announce_urls(null, false));

	$memcached->delete('upload_categories');
	$memcached->delete('news_releases');
	$memcached->delete('tags');
	$memcached->delete('taggenrelist_'.$form['catid']);

	header('Location:/details.php?id='.$id);
	die();
}

$form = $defaults;
$descriptionTemplates = lt_torrent_description_templates();
$categoryTemplateMap = array();
$templateFieldExamples = array();

foreach ($categories as $category) {
	$templateKey = lt_torrent_description_template_key((string) ($category['name'] ?? ''));
	$categoryTemplateMap[(int) $category['id']] = $templateKey;
	if (empty($templateFieldExamples[$templateKey])) {
		$templateFieldExamples[$templateKey] = lt_torrent_template_example_map($templateKey);
	}
}

$currentCategoryName = lt_upload_category_name($categories, (int) ($form['catid'] ?? $defaultCategoryId));
$currentTemplateKey = (string) ($categoryTemplateMap[(int) ($form['catid'] ?? $defaultCategoryId)] ?? 'movies');
$currentTemplateFields = lt_upload_template_manual_fields($currentCategoryName, (array) ($form['template_values'] ?? array()));
$currentTypeOptions = lt_torrent_metadata_type_options_for_category($currentCategoryName);
if ($currentTypeOptions) {
	$metadataSchema['type']['options'] = $currentTypeOptions;
	if (empty($metadataSchema['type']['options'][$form['content_type']])) {
		$form['content_type'] = (string) key($currentTypeOptions);
	}
}

head('Загрузить торрент');
?>
<div class="upload-page">
	<section class="upload-shell">
		<div class="upload-header">
			<h1 class="upload-title">Загрузить торрент</h1>
			<div class="upload-actions">
				<a class="upload-top-link upload-top-link-green" href="faq.php">Правила оформления раздач</a>
				<div class="upload-top-danger">
					<a class="upload-top-link upload-top-link-red" href="copyright.php">Список запрещенных раздач</a>
					<span class="upload-top-note">запрещено к загрузке на трекере</span>
				</div>
			</div>
		</div>

		<form class="upload-form" action="upload.php" method="post" enctype="multipart/form-data">
			<div class="upload-grid">
				<div class="upload-grid-main">
					<div class="upload-field">
						<label class="upload-label" for="upload_name">Название</label>
						<input id="upload_name" class="upload-input" type="text" name="name" value="<?=htmlspecialchars($form['name'], ENT_QUOTES, 'UTF-8');?>" placeholder="<?=htmlspecialchars(lt_torrent_form_help_text('release_name'), ENT_QUOTES, 'UTF-8');?>" required>
						<div class="upload-hint"><?=htmlspecialchars(lt_torrent_form_help_text('release_name'), ENT_QUOTES, 'UTF-8');?></div>
					</div>

					<div class="upload-field">
						<label class="upload-label" for="upload_category">Категория</label>
						<select id="upload_category" class="upload-select" name="catid" required>
							<?php foreach ($categories as $category) { ?>
							<option value="<?=(int) $category['id'];?>" data-template-key="<?=htmlspecialchars((string) ($categoryTemplateMap[(int) $category['id']] ?? 'movies'), ENT_QUOTES, 'UTF-8');?>"<?=((int) $form['catid'] === (int) $category['id'] ? ' selected' : '');?>><?=htmlspecialchars((string) $category['name'], ENT_QUOTES, 'UTF-8');?></option>
							<?php } ?>
						</select>
					</div>

					<div class="upload-field">
						<label class="upload-label">Сведения о релизе</label>
						<div class="upload-hint"><?=htmlspecialchars(lt_torrent_form_help_text('structured_description'), ENT_QUOTES, 'UTF-8');?></div>
						<div class="edit-template-fields upload-template-fields" id="upload_template_fields">
							<?php foreach ($currentTemplateFields as $field) { ?>
							<div class="edit-template-field<?=($field['field_type'] === 'textarea' ? ' edit-template-field-full' : '');?>" data-template-label="<?=htmlspecialchars($field['label'], ENT_QUOTES, 'UTF-8');?>" data-template-type="<?=htmlspecialchars($field['field_type'], ENT_QUOTES, 'UTF-8');?>">
								<?php $fieldExample = lt_torrent_template_example_value($currentCategoryName, $field['label']); ?>
								<label class="upload-label" for="upload_template_<?=md5($field['label']);?>"><?=htmlspecialchars($field['label'], ENT_QUOTES, 'UTF-8');?></label>
								<?php if ($field['field_type'] === 'textarea') { ?>
								<textarea id="upload_template_<?=md5($field['label']);?>" class="upload-textarea edit-template-textarea" name="template_values[<?=htmlspecialchars($field['label'], ENT_QUOTES, 'UTF-8');?>]" placeholder="<?=htmlspecialchars($fieldExample, ENT_QUOTES, 'UTF-8');?>"><?=htmlspecialchars($field['value'], ENT_QUOTES, 'UTF-8');?></textarea>
								<?php } else { ?>
								<input id="upload_template_<?=md5($field['label']);?>" class="upload-input" type="text" name="template_values[<?=htmlspecialchars($field['label'], ENT_QUOTES, 'UTF-8');?>]" value="<?=htmlspecialchars($field['value'], ENT_QUOTES, 'UTF-8');?>" placeholder="<?=htmlspecialchars($fieldExample, ENT_QUOTES, 'UTF-8');?>">
								<?php } ?>
								<?php if ($fieldExample !== '') { ?>
								<div class="upload-example-hint">Например: <?=nl2br(htmlspecialchars($fieldExample, ENT_QUOTES, 'UTF-8'));?></div>
								<?php } ?>
							</div>
							<?php } ?>
						</div>
						<textarea id="upload_descr" name="descr" hidden><?=htmlspecialchars($form['descr'], ENT_QUOTES, 'UTF-8');?></textarea>
						<?php if (!empty($config['metadata_grabber_enabled'])) { ?>
						<div class="metadata-search" data-metadata-search-root data-metadata-endpoint="/api/metadata_search.php" data-metadata-csrf="<?=htmlspecialchars(lt_csrf_token('metadata_search'), ENT_QUOTES, 'UTF-8');?>">
							<div class="metadata-search-row">
								<button class="metadata-search-button" type="button" data-metadata-search-button>🔎 Найти описание</button>
								<span class="metadata-search-note">Экспериментально: проверьте данные перед публикацией.</span>
							</div>
							<div class="metadata-search-status" data-metadata-search-status aria-live="polite"></div>
							<div class="metadata-search-results" data-metadata-search-results></div>
						</div>
						<?php } ?>
					</div>

					<?php foreach ($metadataSchema as $group => $definition) { ?>
					<fieldset class="upload-section upload-section-<?=$group;?>">
						<legend class="upload-section-title"><?=$definition['label'];?></legend>
						<div class="upload-option-grid upload-option-grid-cols-<?=max(2, (int) ($definition['columns'] ?? 4));?>"<?=($group === 'type' ? ' id="upload_type_options"' : '');?>>
							<?php foreach ($definition['options'] as $value => $label) { ?>
							<label class="upload-option">
								<?php if ($definition['input'] === 'radio') { ?>
								<input type="radio" name="content_type" value="<?=htmlspecialchars($value, ENT_QUOTES, 'UTF-8');?>"<?=($form['content_type'] === $value ? ' checked' : '');?>>
								<?php } else { ?>
								<input type="checkbox" name="<?=$group;?>[]" value="<?=htmlspecialchars($value, ENT_QUOTES, 'UTF-8');?>"<?=(in_array($value, $form[$group], true) ? ' checked' : '');?>>
								<?php } ?>
								<span><?=htmlspecialchars($label, ENT_QUOTES, 'UTF-8');?></span>
							</label>
							<?php } ?>
						</div>
					</fieldset>
					<?php } ?>
				</div>

				<div class="upload-grid-side">
					<div class="upload-field">
						<label class="upload-label" for="upload_file">Торрент файл</label>
						<input id="upload_file" class="upload-input upload-file-input" type="file" name="file" accept=".torrent" required>
						<div class="upload-hint"><?=htmlspecialchars(lt_torrent_form_help_text('torrent_file'), ENT_QUOTES, 'UTF-8');?></div>
					</div>

					<div class="upload-field">
						<label class="upload-label" for="upload_cover">Обложка</label>
						<input id="upload_cover" class="upload-input upload-file-input" type="file" name="image" accept=".jpg,.jpeg,.png,.gif" required>
						<div class="upload-hint"><?=htmlspecialchars(lt_torrent_form_help_text('cover'), ENT_QUOTES, 'UTF-8');?></div>
					</div>

					<div class="upload-field">
						<label class="upload-label" for="upload_screens">Скринлист</label>
						<input id="upload_screens" class="upload-input upload-file-input" type="file" name="screenshot[]" accept=".jpg,.jpeg,.png,.gif" multiple required>
						<div class="upload-hint">до 4 изображений</div>
						<div class="upload-hint"><?=htmlspecialchars(lt_torrent_form_help_text('screens'), ENT_QUOTES, 'UTF-8');?></div>
					</div>

					<div class="upload-field">
						<label class="upload-label" for="upload_tags">Тэги</label>
						<input id="upload_tags" class="upload-input" type="text" name="tags" value="<?=htmlspecialchars($form['tags'], ENT_QUOTES, 'UTF-8');?>" placeholder="боевик, 1080p, netflix" data-tags-suggest data-tags-suggest-url="/api/tags_suggest.php">
						<div class="upload-hint"><?=htmlspecialchars(lt_torrent_form_help_text('tags'), ENT_QUOTES, 'UTF-8');?></div>
					</div>
				</div>
			</div>

			<div class="upload-footer">
				<button class="upload-submit" type="submit">Загрузить</button>
			</div>
		</form>
	</section>
</div>
<script type="text/javascript" src="/public/js/tags-suggest.js"></script>
<?php if (!empty($config['metadata_grabber_enabled'])) { ?>
<script type="text/javascript" src="/public/js/metadata-search.js"></script>
<?php } ?>
<script>
(function () {
	var form = document.querySelector('.upload-form');
	var categorySelect = document.getElementById('upload_category');
	var templateFieldsContainer = document.getElementById('upload_template_fields');
	var descriptionField = document.getElementById('upload_descr');
	var typeOptionsContainer = document.getElementById('upload_type_options');
	var templates = <?=json_encode($descriptionTemplates, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);?>;
	var templateExamples = <?=json_encode($templateFieldExamples, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);?>;
	var typeOptions = <?=json_encode($typeOptionsMap, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);?>;
	var defaultTemplateKey = 'movies';

	if (!form || !categorySelect || !templateFieldsContainer || !descriptionField || !typeOptionsContainer) {
		return;
	}

	function escapeHtml(value) {
		return String(value || '')
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;');
	}

	function escapeAttribute(value) {
		return escapeHtml(value).replace(/"/g, '&quot;');
	}

	function selectedCategoryTemplateKey() {
		var option = categorySelect.options[categorySelect.selectedIndex];
		return option && option.getAttribute('data-template-key') ? option.getAttribute('data-template-key') : defaultTemplateKey;
	}

	function selectedTexts(selector) {
		var nodes = form.querySelectorAll(selector);
		var result = [];

		Array.prototype.forEach.call(nodes, function (node) {
			var label = node.parentNode ? node.parentNode.querySelector('span') : null;
			var text = label ? String(label.textContent || '').trim() : '';
			if (text !== '') {
				result.push(text);
			}
		});

		return result;
	}

	function selectedRadioText(name) {
		var input = form.querySelector('input[name="' + name + '"]:checked');
		if (!input || !input.parentNode) {
			return '';
		}

		var label = input.parentNode.querySelector('span');
		return label ? String(label.textContent || '').trim() : '';
	}

	function currentTypeOptions() {
		return typeOptions[selectedCategoryTemplateKey()] || {};
	}

	function currentTemplateExamples() {
		return templateExamples[selectedCategoryTemplateKey()] || templateExamples[defaultTemplateKey] || {};
	}

	function templateItems() {
		var templateKey = selectedCategoryTemplateKey();
		var template = templates[templateKey] || templates[defaultTemplateKey] || { items: [] };
		return Array.isArray(template.items) ? template.items : [];
	}

	function fieldTypeForLabel(label) {
		return ['Описание', 'В ролях', 'Треклист', 'Системные требования'].indexOf(String(label || '').trim()) !== -1 ? 'textarea' : 'text';
	}

	function collectTemplateValues() {
		var values = {};
		var nodes = templateFieldsContainer.querySelectorAll('[data-template-label]');

		Array.prototype.forEach.call(nodes, function (node) {
			var label = String(node.getAttribute('data-template-label') || '').trim();
			var input = node.querySelector('input, textarea');
			if (!label || !input) {
				return;
			}

			values[label] = String(input.value || '');
		});

		return values;
	}

	function renderTypeOptions() {
		var options = currentTypeOptions();
		var currentInput = form.querySelector('input[name="content_type"]:checked');
		var currentValue = currentInput ? String(currentInput.value || '') : '';
		var html = '';

		Object.keys(options).forEach(function (value, index) {
			var label = String(options[value] || '').trim();
			var checked = '';

			if ((currentValue !== '' && currentValue === value) || (currentValue === '' && index === 0)) {
				checked = ' checked';
			}

			html += '<label class="upload-option">'
				+ '<input type="radio" name="content_type" value="' + value.replace(/"/g, '&quot;') + '"' + checked + '>'
				+ '<span>' + label.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</span>'
				+ '</label>';
		});

		typeOptionsContainer.innerHTML = html;
	}

	function renderTemplateFields() {
		var values = collectTemplateValues();
		var examples = currentTemplateExamples();
		var html = '';

		templateItems().forEach(function (item) {
			var itemType = String(item.type || 'field');
			var label = String(item.label || '').trim();
			var auto = String(item.auto || '').trim();
			var fieldType = fieldTypeForLabel(label);
			var value = String(values[label] || '');
			var example = String(examples[label] || '');
			var fieldId = 'upload_template_' + label.toLowerCase().replace(/[^a-zа-я0-9]+/gi, '_');

			if (!label || itemType === 'section' || auto !== '') {
				return;
			}

			html += '<div class="edit-template-field' + (fieldType === 'textarea' ? ' edit-template-field-full' : '') + '" data-template-label="' + escapeAttribute(label) + '" data-template-type="' + fieldType + '">';
			html += '<label class="upload-label" for="' + fieldId + '">' + escapeHtml(label) + '</label>';
			if (fieldType === 'textarea') {
				html += '<textarea id="' + fieldId + '" class="upload-textarea edit-template-textarea" name="template_values[' + escapeAttribute(label) + ']" placeholder="' + escapeAttribute(example) + '">' + escapeHtml(value) + '</textarea>';
			} else {
				html += '<input id="' + fieldId + '" class="upload-input" type="text" name="template_values[' + escapeAttribute(label) + ']" value="' + escapeAttribute(value) + '" placeholder="' + escapeAttribute(example) + '">';
			}
			if (example !== '') {
				html += '<div class="upload-example-hint">Например: ' + escapeHtml(example).replace(/\n/g, '<br>') + '</div>';
			}
			html += '</div>';
		});

		templateFieldsContainer.innerHTML = html;
	}

	function buildDescription() {
		var currentValues = collectTemplateValues();
		var autoValues = {
			type: selectedRadioText('content_type'),
			genre: selectedTexts('input[name="genre[]"]:checked').join(', '),
			language: selectedTexts('input[name="language[]"]:checked').join(', '),
			subtitles: selectedTexts('input[name="subtitles[]"]:checked').join(', '),
			country: selectedTexts('input[name="country[]"]:checked').join(', ')
		};
		var lines = [];

		templateItems().forEach(function (item) {
			var itemType = String(item.type || 'field');
			var label = String(item.label || '').trim();
			var value = '';

			if (!label) {
				return;
			}

			if (itemType === 'section') {
				if (lines.length > 0 && lines[lines.length - 1] !== '') {
					lines.push('');
				}
				lines.push('[u]' + label + '[/u]');
				return;
			}

			if (item.auto && typeof autoValues[item.auto] !== 'undefined' && autoValues[item.auto] !== '') {
				value = autoValues[item.auto];
			} else {
				value = String(currentValues[label] || '').trim();
			}

			if (value.indexOf('\n') !== -1) {
				lines.push('[b]' + label + ':[/b]' + (value !== '' ? '\n' + value : ''));
				return;
			}

			lines.push('[b]' + label + ':[/b]' + (value !== '' ? ' ' + value : ''));
		});

		return lines.join('\n');
	}

	function syncDescription() {
		descriptionField.value = buildDescription();
	}

	categorySelect.addEventListener('change', function () {
		renderTypeOptions();
		renderTemplateFields();
		syncDescription();
	});

	templateFieldsContainer.addEventListener('input', function () {
		syncDescription();
	});

	form.addEventListener('change', function (event) {
		var target = event.target;
		if (!target || !target.name) {
			return;
		}

		if (target.name === 'content_type' || target.name === 'genre[]' || target.name === 'language[]' || target.name === 'subtitles[]' || target.name === 'country[]') {
			syncDescription();
		}
	});

	form.addEventListener('submit', function () {
		syncDescription();
	});

	renderTypeOptions();
	syncDescription();
})();
</script>
<?php
foot();
?>
