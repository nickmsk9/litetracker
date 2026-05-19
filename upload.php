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

require __DIR__ . '/app/system/init.php';
require __DIR__ . '/app/system/functions/functions.benc.php';
require_once __DIR__ . '/app/helpers/UploadAssetHelper.php';

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

function lt_upload_next_torrent_id()
{
	global $db;

	$row = $db->super_query("SHOW TABLE STATUS LIKE 'torrents'");

	return (!empty($row['Auto_increment']) ? (int) $row['Auto_increment'] : 0);
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
	global $config;

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
		$extension = lt_upload_asset_validate_image($screenshot, 'Скринлист', (int) $config['max_size_image'], false);
		$filename = $nextId.'_'.$index.'.'.$extension;
		$result[] = lt_upload_asset_move_uploaded_image($screenshot, 'public/downloads/screens/', $filename, 'скринлист');
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

lt_torrent_status_ensure_schema();

$metadataSchema = lt_torrent_metadata_schema();
$categories = lt_upload_categories_list();
$defaultCategoryId = lt_upload_default_category_id($categories);
$defaultCategoryName = lt_torrent_category_name_from_list($categories, $defaultCategoryId);
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
	if (!lt_csrf_validate('upload_torrent')) {
		err($language['default_1'], 'Защитный токен устарел. Обновите страницу и попробуйте снова.', 1);
	}

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
	$primaryDescriptionLabel = lt_torrent_description_primary_label((string) ($categoryInfo['name'] ?? ''));
	if ($primaryDescriptionLabel !== '' && trim((string) ($form['template_values'][$primaryDescriptionLabel] ?? '')) === '') {
		err($language['default_1'], $language['upload_26'], 1);
	}

	$form['descr'] = lt_torrent_description_build_with_auto((string) ($categoryInfo['name'] ?? ''), $form['template_values'], $autoDescriptionValues);
	if ($form['descr'] === '') {
		err($language['default_1'], $language['upload_26'], 1);
	}

	$nextId = lt_upload_next_torrent_id();
	if ($nextId <= 0) {
		err('Ошибка', 'Не удалось подготовить загрузку торрента.', 1);
	}

	$coverExtension = lt_upload_asset_validate_image((array) ($_FILES['image'] ?? array()), 'Обложка', (int) $config['max_size_image'], false);
	$coverName = lt_upload_asset_move_uploaded_image((array) $_FILES['image'], 'public/downloads/images/', $nextId.'.'.$coverExtension, 'обложку');
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

	if (lt_torrent_can_auto_approve($USER)) {
		lt_torrent_set_status($id, 'approved', (int) $USER['id'], '');
	} else {
		lt_torrent_submit_for_review($id, true);
	}

	if (!lt_upload_asset_ensure_directory('public/downloads/torrents/')) {
		err('Ошибка', 'Не удалось подготовить каталог для torrent-файлов.', 1);
	}

	if (!move_uploaded_file($torrent['tmp_name'], 'public/downloads/torrents/'.$id.'.torrent')) {
		err('Ошибка', 'Релиз добавлен, но torrent-файл не удалось сохранить на сервер.', 1);
	}

	lt_torrent_rewrite_file_announces('public/downloads/torrents/'.$id.'.torrent', lt_torrent_site_announce_urls(null, false));

	lt_cache_invalidate_cats();
	lt_cache_delete('news_releases');
	lt_cache_invalidate_tags();
	lt_cache_invalidate_tags_genre($form['catid']);

	header('Location:/details.php?id='.$id.(!lt_torrent_can_auto_approve($USER) ? '&moderation=pending' : ''));
	die();
}

$form = $defaults;
$descriptionTemplates = lt_torrent_description_templates();
$categoryTemplateMap = lt_torrent_category_template_map($categories);
$templateFieldExamples = lt_torrent_template_examples_map($descriptionTemplates);

$currentCategoryName = lt_torrent_category_name_from_list($categories, (int) ($form['catid'] ?? $defaultCategoryId));
$currentTemplateKey = (string) ($categoryTemplateMap[(int) ($form['catid'] ?? $defaultCategoryId)] ?? 'movies');
$currentTemplateFields = lt_torrent_description_manual_fields($currentCategoryName, (array) ($form['template_values'] ?? array()));
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
			<?=lt_csrf_input('upload_torrent');?>
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
<script type="text/javascript" src="/public/js/torrent-description-form.js"></script>
<script>
window.initTorrentDescriptionForm({
	formSelector: '.upload-form',
	categorySelector: '#upload_category',
	typeSelector: '#upload_type_options',
	descriptionSelector: '#upload_descr',
	templateFieldsContainerSelector: '#upload_template_fields',
	fieldIdPrefix: 'upload_template_',
	typeOptions: <?=json_encode($typeOptionsMap, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);?>,
	templates: <?=json_encode($descriptionTemplates, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);?>,
	examples: <?=json_encode($templateFieldExamples, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);?>,
	defaultTemplateKey: 'movies',
	initialRenderFields: false
});
</script>
<?php
foot();
?>
