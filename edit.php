<?php
/*
===================================================================
LiteTracker Source
===================================================================
by Nick
-------------------------------------------------------------------
Назначение: Редактирование релиза
===================================================================
*/

require 'system/init.php';
require 'system/functions/functions.benc.php';

function lt_edit_redirect_to_details($id)
{
	header('Location:details.php?id='.(int) $id.'&edit=1');
	die();
}

function lt_edit_collect_screens($torrent)
{
	$result = array();

	for ($index = 1; $index <= 4; $index++) {
		$key = 'screen_'.$index;
		if (!empty($torrent[$key])) {
			$result[] = array(
				'index' => $index,
				'file' => (string) $torrent[$key],
			);
		}
	}

	return $result;
}

function lt_edit_category_name($categories, $categoryId)
{
	foreach ((array) $categories as $category) {
		if ((int) ($category['id'] ?? 0) !== (int) $categoryId) {
			continue;
		}

		return trim((string) ($category['name'] ?? ''));
	}

	return '';
}

function lt_edit_metadata_values($torrent, $schema)
{
	$result = array();

	foreach ((array) $schema as $group => $definition) {
		$column = trim((string) ($definition['column'] ?? ''));
		if ($column === '') {
			$result[$group] = array();
			continue;
		}

		if (($definition['input'] ?? '') === 'radio') {
			$result[$group] = trim((string) ($torrent[$column] ?? ''));
			continue;
		}

		$result[$group] = lt_torrent_metadata_parse($group, $torrent[$column] ?? '');
	}

	return $result;
}

function lt_edit_parse_description($text)
{
	$result = array();
	$currentLabel = '';
	$lines = preg_split('/\r?\n/u', (string) $text);

	foreach ($lines as $line) {
		$fieldMatch = array();
		$sectionMatch = array();

		if (preg_match('/^\[u\].+\[\/u\]$/ui', $line, $sectionMatch)) {
			$currentLabel = '';
			continue;
		}

		if (preg_match('/^\[b\]([^:\[]+):\[\/b\]\s*(.*)$/ui', $line, $fieldMatch)) {
			$currentLabel = trim((string) ($fieldMatch[1] ?? ''));
			if ($currentLabel === '') {
				continue;
			}

			$result[$currentLabel] = trim((string) ($fieldMatch[2] ?? ''));
			continue;
		}

		if ($currentLabel === '') {
			continue;
		}

		$result[$currentLabel] .= ($result[$currentLabel] !== '' ? "\n" : '').$line;
	}

	return $result;
}

function lt_edit_template_textarea_labels()
{
	return array(
		'Описание',
		'В ролях',
		'Треклист',
		'Системные требования',
	);
}

function lt_edit_template_field_type($label)
{
	return (in_array(trim((string) $label), lt_edit_template_textarea_labels(), true) ? 'textarea' : 'text');
}

function lt_edit_template_manual_fields($categoryNameOrKey, $values = array())
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
			'field_type' => lt_edit_template_field_type($label),
			'value' => (string) ($values[$label] ?? ''),
		);
	}

	return $result;
}

function lt_edit_primary_description_label($categoryNameOrKey)
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

function lt_edit_build_description($categoryNameOrKey, $templateValues, $autoValues = array())
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

function lt_edit_ensure_directory($path)
{
	if (is_dir($path)) {
		return true;
	}

	return @mkdir($path, 0777, true);
}

function lt_edit_image_extension($filename)
{
	$extension = strtolower((string) pathinfo((string) $filename, PATHINFO_EXTENSION));
	$allowed = array('jpg', 'jpeg', 'png', 'gif');

	return (in_array($extension, $allowed, true) ? ($extension === 'jpeg' ? 'jpg' : $extension) : '');
}

function lt_edit_validate_image($file, $label)
{
	global $config, $language;

	$name = (string) ($file['name'] ?? '');
	$tmp = (string) ($file['tmp_name'] ?? '');
	$size = (int) ($file['size'] ?? 0);
	$error = (int) ($file['error'] ?? UPLOAD_ERR_OK);

	if ($error !== UPLOAD_ERR_OK || $name === '' || $tmp === '' || !is_uploaded_file($tmp)) {
		err($language['default_1'], $label.' не был загружен.', 1);
	}

	$extension = lt_edit_image_extension($name);
	if ($extension === '') {
		err($language['default_1'], $label.' должен быть в формате JPG, PNG или GIF.', 1);
	}

	if ($size <= 0 || $size > (int) $config['max_size_image']) {
		err($language['default_1'], $label.' превышает допустимый размер '.mksize($config['max_size_image']).'.', 1);
	}

	$imageInfo = @getimagesize($tmp);
	if (!$imageInfo || empty($imageInfo[2]) || !in_array((int) $imageInfo[2], array(IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG), true)) {
		err($language['default_1'], $label.' не похож на изображение.', 1);
	}

	return $extension;
}

function lt_edit_move_uploaded_image($file, $directory, $targetName, $label)
{
	global $language;

	if (!lt_edit_ensure_directory($directory)) {
		err($language['default_1'], 'Не удалось подготовить каталог для загрузки файлов.', 1);
	}

	if (!@move_uploaded_file((string) ($file['tmp_name'] ?? ''), $directory.$targetName)) {
		err($language['default_1'], 'Не удалось сохранить '.$label.'.', 1);
	}

	return $targetName;
}

is_login();

$act = isset($_GET['act']) ? (string) $_GET['act'] : '';
$screen = isset($_GET['screen']) ? (int) $_GET['screen'] : 0;
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$sql = $db->query('SELECT * FROM torrents WHERE id='.(int) $id);
if (!$db->num_rows($sql)) {
	err($language['default_1'], $language['edit_1'], 1);
}

$arr = $db->get_row($sql);

if ($arr['id_user'] != $USER['id'] && !$PRIV['edit_release']) {
	err($language['default_1'], $language['edit_2'], 1);
}

if ($act == 'delete_image') {
	if (!empty($arr['image'])) {
		$db->query('UPDATE torrents SET image="" WHERE id='.(int) $id);
		@unlink('public/downloads/images/'.$arr['image']);
	}

	lt_edit_redirect_to_details($id);
}

if ($act == 'delete_screen') {
	if ($screen < 1 || $screen > 4) {
		err($language['default_1'], $language['default_6']);
	}

	if (!empty($arr['screen_'.$screen])) {
		$db->query('UPDATE torrents SET screen_'.$screen.'="" WHERE id='.(int) $id);
		@unlink('public/downloads/screens/'.$arr['screen_'.$screen]);
	}

	lt_edit_redirect_to_details($id);
}

if ($act == 'take') {
	$update = array();
	$filelist = array();
	$trackers = array();
	$fname = '';
	$tmpname = '';

	$categories = categories_array();
	$category = (int) ($_POST['category'] ?? 0);
	$categoryName = lt_edit_category_name($categories, $category);
	if ($category <= 0 || $categoryName === '') {
		err($language['default_1'], $language['upload_3'], 1);
	}

	$multi = (int) $arr['multi'];

	$file = (isset($_FILES['file']) && is_array($_FILES['file']) ? $_FILES['file'] : array());
	$fname = trim((string) ($file['name'] ?? ''));
	if ($fname !== '') {
		if (!validfilename($fname)) {
			err($language['default_1'], $language['upload_20'], 1);
		}

		if (!preg_match('/^(.+)\.torrent$/si', $fname)) {
			err($language['default_1'], $language['upload_21'], 1);
		}

		$tmpname = (string) ($file['tmp_name'] ?? '');
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

		$trackers = get_announce_urls($dict);
		$trackers = lt_torrent_external_trackers(is_array($trackers) ? $trackers : array());
		$multi = ($trackers ? 1 : 0);

		$dict = bdec(benc($dict));
		list($info) = dict_check($dict, 'info');
		list($dname, $plen, $pieces) = dict_check($info, 'name(string):piece length(integer):pieces(string)');

		if (strlen($pieces) % 20 != 0) {
			err('Invalid pieces');
		}

		$totallen = dict_get($info, 'length', 'integer');
		if (isset($totallen)) {
			$filelist[] = array($dname, $totallen);
			$type = 'single';
		} else {
			$flist = dict_get($info, 'files', 'list');
			if (!is_array($flist) || !count($flist)) {
				err('missing both length and files');
			}

			$totallen = 0;
			foreach ($flist as $fn) {
				list($ll, $ff) = dict_check($fn, 'length(integer):path(list)');
				$totallen += $ll;
				$ffa = array();

				foreach ($ff as $ffe) {
					if ($ffe['type'] != 'string') {
						err('filename error');
					}

					$ffa[] = $ffe['value'];
				}

				if (!$ffa) {
					err('filename error');
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
		$update[] = 'infohash="'.$db->safesql($infohash).'"';
		$update[] = 'filename="'.$db->safesql($fname).'"';
		$update[] = 'size="'.$totallen.'"';
		$update[] = 'multi="'.$multi.'"';
		$update[] = 'num_files="'.count($filelist).'"';
		$update[] = 'type="'.$type.'"';
	}

	if ((int) $arr['id_category'] !== $category) {
		$update[] = 'id_category="'.$category.'"';
	}

	$name = trim((string) ($_POST['name'] ?? ''));
	if ($name === '') {
		err($language['default_1'], $language['upload_25'], 1);
	}
	if ((string) $arr['name'] !== $name) {
		$update[] = 'name="'.$db->safesql($name).'"';
	}

	$tags = lt_torrent_tags_to_string((string) ($_POST['tags'] ?? ''));
	if ((string) $arr['tags'] !== $tags) {
		$update[] = 'tags="'.$db->safesql($tags).'"';
	}

	$typeOptions = lt_torrent_metadata_type_options_for_category($categoryName);
	$contentType = trim((string) ($_POST['content_type'] ?? ''));
	if ($contentType === '' || empty($typeOptions[$contentType])) {
		$contentType = (string) key($typeOptions);
	}
	if ($contentType === '') {
		$contentType = trim((string) ($arr['content_type'] ?? 'movie'));
	}
	if ((string) ($arr['content_type'] ?? '') !== $contentType) {
		$update[] = 'content_type="'.$db->safesql($contentType).'"';
	}

	$metadataSchema = lt_torrent_metadata_schema();
	$metadataCsv = array();
	foreach ($metadataSchema as $group => $definition) {
		$column = trim((string) ($definition['column'] ?? ''));
		if ($column === '' || $group === 'type') {
			continue;
		}

		$csv = lt_torrent_metadata_csv($group, $_POST[$group] ?? array());
		$metadataCsv[$group] = $csv;
		if ((string) ($arr[$column] ?? '') !== $csv) {
			$update[] = $column.'="'.$db->safesql($csv).'"';
		}
	}

	$templateValues = (isset($_POST['template_values']) && is_array($_POST['template_values']) ? $_POST['template_values'] : array());
	$autoDescriptionValues = array(
		'type' => lt_torrent_metadata_option_label('type', $contentType),
		'genre' => lt_torrent_metadata_format('genre', $metadataCsv['genre'] ?? ''),
		'language' => lt_torrent_metadata_format('language', $metadataCsv['language'] ?? ''),
		'subtitles' => lt_torrent_metadata_format('subtitles', $metadataCsv['subtitles'] ?? ''),
		'country' => lt_torrent_metadata_format('country', $metadataCsv['country'] ?? ''),
	);
	$primaryDescriptionLabel = lt_edit_primary_description_label($categoryName);
	if ($primaryDescriptionLabel !== '' && trim((string) ($templateValues[$primaryDescriptionLabel] ?? '')) === '') {
		err($language['default_1'], $language['upload_26'], 1);
	}

	$descr = lt_edit_build_description($categoryName, $templateValues, $autoDescriptionValues);
	if ($descr === '') {
		err($language['default_1'], $language['upload_26'], 1);
	}
	if ((string) $arr['descr'] !== $descr) {
		$update[] = 'descr="'.$db->safesql($descr).'"';
	}

	if (!empty($_FILES['image']['name'])) {
		$coverExtension = lt_edit_validate_image((array) $_FILES['image'], 'Обложка');
		$coverName = $id.'.'.$coverExtension;
		lt_edit_move_uploaded_image((array) $_FILES['image'], 'public/downloads/images/', $coverName, 'обложку');

		if (!empty($arr['image']) && $arr['image'] !== $coverName) {
			@unlink('public/downloads/images/'.$arr['image']);
		}

		$update[] = 'image="'.$db->safesql($coverName).'"';
	}

	$screenFiles = (isset($_FILES['screenshot']) && is_array($_FILES['screenshot']) ? $_FILES['screenshot'] : array());
	$screenNames = (isset($screenFiles['name']) && is_array($screenFiles['name']) ? $screenFiles['name'] : array());
	for ($index = 0; $index < 4; $index++) {
		$screenName = trim((string) ($screenNames[$index] ?? ''));
		if ($screenName === '') {
			continue;
		}

		$slot = $index + 1;
		$screenFile = array(
			'name' => $screenName,
			'tmp_name' => (string) ($screenFiles['tmp_name'][$index] ?? ''),
			'size' => (int) ($screenFiles['size'][$index] ?? 0),
		);

		$screenExtension = lt_edit_validate_image($screenFile, 'Скриншот '.$slot);
		$screenStoredName = $id.'_'.$index.'.'.$screenExtension;
		lt_edit_move_uploaded_image($screenFile, 'public/downloads/screens/', $screenStoredName, 'скриншот '.$slot);

		if (!empty($arr['screen_'.$slot]) && $arr['screen_'.$slot] !== $screenStoredName) {
			@unlink('public/downloads/screens/'.$arr['screen_'.$slot]);
		}

		$update[] = 'screen_'.$slot.'="'.$db->safesql($screenStoredName).'"';
	}

	if ($PRIV['edit_news']) {
		$news = (!empty($_POST['news']) ? '1' : '0');
		$update[] = 'news="'.$news.'"';
	}

	if ($PRIV['edit_banned']) {
		$banned = (!empty($_POST['banned']) ? '1' : '0');
		$update[] = 'banned="'.$banned.'"';
	}

	if ($update) {
		$result = $db->query('UPDATE torrents SET '.implode(',', $update).' WHERE id='.(int) $id, 1);
		if (!$result) {
			err($language['default_1'], $language['upload_37'], 1);
		}
	}

	if ($fname !== '') {
		$db->query('DELETE FROM files WHERE id_torrent='.(int) $id);
		foreach ($filelist as $fileRow) {
			$db->query('INSERT INTO files (id_torrent, filename, size) VALUES ('.(int) $id.', "'.$db->safesql($fileRow[0]).'", "'.$fileRow[1].'")');
		}

		move_uploaded_file($tmpname, 'public/downloads/torrents/'.(int) $id.'.torrent');
		lt_torrent_rewrite_file_announces('public/downloads/torrents/'.(int) $id.'.torrent', lt_torrent_site_announce_urls(null, false));

		$db->query('DELETE FROM trackers WHERE torrent='.(int) $id);
		lt_torrent_store_trackers($id, $trackers);
	}

	if ((string) $arr['tags'] !== $tags) {
		$existingTags = array();
		$res = $db->query('SELECT name FROM tags WHERE category='.(int) $category);
		while ($row = $db->get_row($res)) {
			$existingTags[] = (string) $row['name'];
		}

		$tagList = lt_torrent_tags_from_string($tags);
		$union = array_intersect($existingTags, $tagList);
		$missing = array_diff($tagList, $existingTags);

		foreach ($union as $tag) {
			$tag = trim((string) $tag);
			if ($tag === '') {
				continue;
			}

			$db->query('UPDATE tags SET howmuch=howmuch+1 WHERE name LIKE "'.$db->safesql($tag).'"');
		}

		foreach ($missing as $tag) {
			$tag = trim((string) $tag);
			if ($tag === '') {
				continue;
			}

			$db->query('INSERT INTO tags (category, name, howmuch) VALUES ("'.(int) $category.'", "'.$db->safesql($tag).'", 1)');
		}
	}

	$memcached->delete('tags');
	lt_edit_redirect_to_details($id);
}

if ($act == 'delete') {
	if (isset($_GET['take']) && (int) $_GET['take'] === 1) {
		$db->query('DELETE FROM torrents WHERE id='.(int) $id);
		$db->query('DELETE FROM trackers WHERE torrent='.(int) $id);
		$db->query('DELETE FROM peers WHERE torrent='.(int) $id);
		$db->query('DELETE FROM snatched WHERE torrent='.(int) $id);
		@unlink('public/downloads/images/'.$arr['image']);
		@unlink('public/downloads/torrents/'.(int) $id.'.torrent');
		@unlink('public/downloads/screens/'.$arr['screen_1']);
		@unlink('public/downloads/screens/'.$arr['screen_2']);
		@unlink('public/downloads/screens/'.$arr['screen_3']);
		@unlink('public/downloads/screens/'.$arr['screen_4']);
		header('Location:index.php');
		die();
	}

	head('Удалить релиз');
	msg('Удалить релиз', 'Вы действительно хотите удалить релиз?');
	echo '<input type="button" value="'.$language['upload_38'].'" onClick="window.location.href=\'edit.php?act=delete&id='.(int) $id.'&take=1\'"> ';
	echo '<input type="button" value="'.$language['default_5'].'" onClick="history.go(-1)">';
	foot();
	die();
}

$categories = categories_array();
$categoryTemplateMap = array();
foreach ($categories as $categoryItem) {
	$categoryTemplateMap[(int) $categoryItem['id']] = lt_torrent_description_template_key((string) ($categoryItem['name'] ?? ''));
}

$selectedCategoryId = (int) ($arr['id_category'] ?? 0);
$selectedCategoryName = lt_edit_category_name($categories, $selectedCategoryId);
$metadataSchema = lt_torrent_metadata_schema();
$metadataValues = lt_edit_metadata_values($arr, $metadataSchema);
$typeOptionsMap = lt_torrent_type_options_map();
$descriptionTemplates = lt_torrent_description_templates();
$templateFieldExamples = array();
foreach ($descriptionTemplates as $templateKey => $templateInfo) {
	$templateFieldExamples[$templateKey] = lt_torrent_template_example_map($templateKey);
}
$currentTypeOptions = lt_torrent_metadata_type_options_for_category($selectedCategoryName);
$currentContentType = trim((string) ($arr['content_type'] ?? ''));
if ($currentContentType === '' || empty($currentTypeOptions[$currentContentType])) {
	$currentContentType = (string) key($currentTypeOptions);
}
if ($currentContentType === '') {
	$currentContentType = 'movie';
}
$metadataValues['type'] = $currentContentType;
$parsedDescriptionValues = lt_edit_parse_description((string) ($arr['descr'] ?? ''));
$currentTemplateFields = lt_edit_template_manual_fields($selectedCategoryName, $parsedDescriptionValues);
$tagSuggestions = taggenrelist($selectedCategoryId);
$currentScreens = lt_edit_collect_screens($arr);
$currentCover = trim((string) ($arr['image'] ?? ''));

head($language['edit_3'], true);
?>
<script type="text/javascript" src="public/js/tagto.js"></script>

<div class="upload-page upload-page-edit">
	<section class="upload-shell edit-shell">
		<div class="upload-header">
			<h1 class="upload-title"><?=$language['edit_3'];?></h1>
			<div class="upload-actions">
				<a class="upload-top-link upload-top-link-green" href="details.php?id=<?=(int) $id;?>">Вернуться к раздаче</a>
				<div class="upload-top-danger">
					<a class="upload-top-link upload-top-link-red" href="edit.php?act=delete&id=<?=(int) $id;?>"><?=$language['upload_38'];?></a>
					<span class="upload-top-note">удаление раздачи целиком</span>
				</div>
			</div>
		</div>

		<form class="upload-form edit-upload-form" enctype="multipart/form-data" action="edit.php?act=take&id=<?=(int) $id;?>" method="post" name="upload">
			<input type="hidden" name="id" value="<?=(int) $id;?>">

			<div class="upload-grid">
				<div class="upload-grid-main">
					<div class="upload-field">
						<label class="upload-label" for="edit_name"><?=$language['upload_9'];?></label>
						<input id="edit_name" class="upload-input" type="text" name="name" value="<?=htmlspecialchars((string) $arr['name'], ENT_QUOTES, 'UTF-8');?>" required>
						<div class="upload-hint"><?=htmlspecialchars(lt_torrent_form_help_text('release_name'), ENT_QUOTES, 'UTF-8');?></div>
					</div>

					<div class="upload-field">
						<label class="upload-label" for="edit_category">Категория</label>
						<select id="edit_category" class="upload-select" name="category" required>
							<?php foreach ($categories as $category) { ?>
							<option value="<?=(int) $category['id'];?>" data-template-key="<?=htmlspecialchars((string) ($categoryTemplateMap[(int) $category['id']] ?? 'movies'), ENT_QUOTES, 'UTF-8');?>"<?=($selectedCategoryId === (int) $category['id'] ? ' selected' : '');?>><?=htmlspecialchars((string) $category['name'], ENT_QUOTES, 'UTF-8');?></option>
							<?php } ?>
						</select>
					</div>

					<div class="upload-field">
						<label class="upload-label">Карточка описания</label>
						<div class="upload-hint"><?=htmlspecialchars(lt_torrent_form_help_text('structured_description'), ENT_QUOTES, 'UTF-8');?></div>
						<div class="edit-template-fields" id="edit_template_fields">
							<?php foreach ($currentTemplateFields as $field) { ?>
							<div class="edit-template-field<?=($field['field_type'] === 'textarea' ? ' edit-template-field-full' : '');?>" data-template-label="<?=htmlspecialchars($field['label'], ENT_QUOTES, 'UTF-8');?>" data-template-type="<?=htmlspecialchars($field['field_type'], ENT_QUOTES, 'UTF-8');?>">
								<?php $fieldExample = lt_torrent_template_example_value($selectedCategoryName, $field['label']); ?>
								<label class="upload-label" for="edit_template_<?=md5($field['label']);?>"><?=htmlspecialchars($field['label'], ENT_QUOTES, 'UTF-8');?></label>
								<?php if ($field['field_type'] === 'textarea') { ?>
								<textarea id="edit_template_<?=md5($field['label']);?>" class="upload-textarea edit-template-textarea" name="template_values[<?=htmlspecialchars($field['label'], ENT_QUOTES, 'UTF-8');?>]" placeholder="<?=htmlspecialchars($fieldExample, ENT_QUOTES, 'UTF-8');?>"><?=htmlspecialchars($field['value'], ENT_QUOTES, 'UTF-8');?></textarea>
								<?php } else { ?>
								<input id="edit_template_<?=md5($field['label']);?>" class="upload-input" type="text" name="template_values[<?=htmlspecialchars($field['label'], ENT_QUOTES, 'UTF-8');?>]" value="<?=htmlspecialchars($field['value'], ENT_QUOTES, 'UTF-8');?>" placeholder="<?=htmlspecialchars($fieldExample, ENT_QUOTES, 'UTF-8');?>">
								<?php } ?>
								<?php if ($fieldExample !== '') { ?>
								<div class="upload-example-hint">Например: <?=nl2br(htmlspecialchars($fieldExample, ENT_QUOTES, 'UTF-8'));?></div>
								<?php } ?>
							</div>
							<?php } ?>
						</div>
						<textarea id="edit_descr" name="descr" hidden><?=htmlspecialchars((string) ($arr['descr'] ?? ''), ENT_QUOTES, 'UTF-8');?></textarea>
					</div>

					<?php foreach ($metadataSchema as $group => $definition) { ?>
					<fieldset class="upload-section upload-section-<?=$group;?>">
						<legend class="upload-section-title"><?=$definition['label'];?></legend>
						<div class="upload-option-grid upload-option-grid-cols-<?=max(2, (int) ($definition['columns'] ?? 4));?>"<?=($group === 'type' ? ' id="edit_type_options"' : '');?>>
							<?php foreach ($definition['options'] as $value => $label) { ?>
							<label class="upload-option">
								<?php if (($definition['input'] ?? '') === 'radio') { ?>
								<input type="radio" name="content_type" value="<?=htmlspecialchars($value, ENT_QUOTES, 'UTF-8');?>"<?=($currentContentType === $value ? ' checked' : '');?>>
								<?php } else { ?>
								<input type="checkbox" name="<?=$group;?>[]" value="<?=htmlspecialchars($value, ENT_QUOTES, 'UTF-8');?>"<?=(in_array($value, $metadataValues[$group] ?? array(), true) ? ' checked' : '');?>>
								<?php } ?>
								<span><?=htmlspecialchars($label, ENT_QUOTES, 'UTF-8');?></span>
							</label>
							<?php } ?>
						</div>
					</fieldset>
					<?php } ?>

					<?php if ($PRIV['edit_news'] || ($PRIV['edit_banned'] && $arr['id_user'] != $USER['id'])) { ?>
					<fieldset class="upload-section">
						<legend class="upload-section-title">Параметры</legend>
						<div class="upload-option-grid upload-option-grid-cols-2">
							<?php if ($PRIV['edit_news']) { ?>
							<label class="upload-option">
								<input type="checkbox" name="news" value="1"<?=(!empty($arr['news']) ? ' checked' : '');?>>
								<span><?=$language['upload_40'];?></span>
							</label>
							<?php } ?>
							<?php if ($PRIV['edit_banned'] && $arr['id_user'] != $USER['id']) { ?>
							<label class="upload-option">
								<input type="checkbox" name="banned" value="1"<?=(!empty($arr['banned']) ? ' checked' : '');?>>
								<span><?=$language['upload_43'];?></span>
							</label>
							<?php } ?>
						</div>
					</fieldset>
					<?php } ?>
				</div>

				<div class="upload-grid-side">
					<div class="upload-field">
						<label class="upload-label" for="edit_file"><?=$language['upload_4'];?></label>
						<input id="edit_file" class="upload-input upload-file-input" type="file" name="file" accept=".torrent">
						<div class="upload-hint"><?=htmlspecialchars(lt_torrent_form_help_text('torrent_file'), ENT_QUOTES, 'UTF-8');?></div>
						<div class="upload-hint">Текущий файл: <?=htmlspecialchars((string) $arr['filename'], ENT_QUOTES, 'UTF-8');?></div>
					</div>

					<div class="upload-field">
						<label class="upload-label" for="edit_cover"><?=$language['upload_5'];?></label>
						<input id="edit_cover" class="upload-input upload-file-input" type="file" name="image" accept=".jpg,.jpeg,.png,.gif">
						<div class="upload-hint"><?=htmlspecialchars(lt_torrent_form_help_text('cover'), ENT_QUOTES, 'UTF-8');?></div>
						<div class="upload-hint"><?=sprintf($language['upload_6'], mksize($config['max_size_image']));?></div>
						<?php if ($currentCover !== '') { ?>
						<div class="edit-media-card edit-media-card-cover">
							<div class="edit-media-card-head">
								<strong>Текущая обложка</strong>
								<a class="upload-top-link upload-top-link-red" href="edit.php?id=<?=(int) $id;?>&amp;act=delete_image">Удалить</a>
							</div>
							<a class="edit-media-preview edit-media-preview-cover" href="public/downloads/images/<?=htmlspecialchars($currentCover, ENT_QUOTES, 'UTF-8');?>" target="_blank" rel="noopener noreferrer">
								<img class="edit-media-preview-image edit-media-preview-image-cover" src="public/downloads/images/<?=htmlspecialchars($currentCover, ENT_QUOTES, 'UTF-8');?>" alt="Обложка">
							</a>
						</div>
						<?php } ?>
					</div>

					<div class="upload-field">
						<label class="upload-label" for="edit_screens"><?=$language['upload_7'];?></label>
						<input id="edit_screens" class="upload-input upload-file-input" type="file" name="screenshot[]" accept=".jpg,.jpeg,.png,.gif" multiple>
						<div class="upload-hint">до 4 изображений</div>
						<div class="upload-hint"><?=htmlspecialchars(lt_torrent_form_help_text('screens'), ENT_QUOTES, 'UTF-8');?></div>
						<?php if ($currentScreens) { ?>
						<div class="edit-media-card">
							<div class="edit-media-card-head">
								<strong>Текущие скриншоты</strong>
								<span class="upload-top-note"><?=count($currentScreens);?> из 4</span>
							</div>
							<div class="edit-media-grid">
								<?php foreach ($currentScreens as $screenItem) { ?>
								<div class="edit-media-grid-item">
									<a class="edit-media-preview" href="public/downloads/screens/<?=htmlspecialchars($screenItem['file'], ENT_QUOTES, 'UTF-8');?>" target="_blank" rel="noopener noreferrer">
										<img class="edit-media-preview-image" src="public/downloads/screens/<?=htmlspecialchars($screenItem['file'], ENT_QUOTES, 'UTF-8');?>" alt="Скриншот <?=htmlspecialchars((string) $screenItem['index'], ENT_QUOTES, 'UTF-8');?>">
									</a>
									<div class="edit-media-preview-actions">
										<span class="edit-media-preview-title">Скрин <?=htmlspecialchars((string) $screenItem['index'], ENT_QUOTES, 'UTF-8');?></span>
										<a class="upload-top-link upload-top-link-red" href="edit.php?id=<?=(int) $id;?>&amp;act=delete_screen&amp;screen=<?=(int) $screenItem['index'];?>">Удалить</a>
									</div>
								</div>
								<?php } ?>
							</div>
						</div>
						<?php } ?>
					</div>

					<div class="upload-field">
						<label class="upload-label" for="edit_tags"><?=$language['upload_12'];?></label>
						<input id="edit_tags" class="upload-input" type="text" name="tags" value="<?=htmlspecialchars(lt_torrent_tags_to_string((string) $arr['tags']), ENT_QUOTES, 'UTF-8');?>" placeholder="через запятую">
						<div class="upload-hint"><?=htmlspecialchars(lt_torrent_form_help_text('tags'), ENT_QUOTES, 'UTF-8');?></div>
						<div class="upload-hint" id="from">
							<?php if (!$tagSuggestions) { ?>
							<?=$language['upload_13'];?>
							<?php } else { ?>
							<?php foreach ($tagSuggestions as $tagRow) { ?>
							<a href="#"><?=htmlspecialchars((string) $tagRow['name'], ENT_QUOTES, 'UTF-8');?></a>
							<?php } ?>
							<?php } ?>
						</div>
					</div>
				</div>
			</div>

			<div class="upload-footer">
				<button class="upload-submit" type="submit"><?=$language['details_23'];?></button>
			</div>
		</form>
	</section>
</div>

<script type="text/javascript">
$(document).ready(function () {
	$('#from').tagTo('#edit_tags');
});

(function () {
	var form = document.querySelector('.edit-upload-form');
	var categorySelect = document.getElementById('edit_category');
	var templateFieldsContainer = document.getElementById('edit_template_fields');
	var descriptionField = document.getElementById('edit_descr');
	var typeOptionsContainer = document.getElementById('edit_type_options');
	var templates = <?=json_encode($descriptionTemplates, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);?>;
	var templateExamples = <?=json_encode($templateFieldExamples, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);?>;
	var typeOptions = <?=json_encode($typeOptionsMap, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);?>;
	var defaultTemplateKey = 'movies';

	if (!form || !categorySelect || !templateFieldsContainer || !descriptionField || !typeOptionsContainer) {
		return;
	}

	function selectedCategoryTemplateKey() {
		var option = categorySelect.options[categorySelect.selectedIndex];
		return option && option.getAttribute('data-template-key') ? option.getAttribute('data-template-key') : defaultTemplateKey;
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
			var fieldId = 'edit_template_' + label.toLowerCase().replace(/[^a-zа-я0-9]+/gi, '_');

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
	renderTemplateFields();
	syncDescription();
})();
</script>
<?php
foot(true);
?>
