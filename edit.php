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

/**
 * @deprecated Use lt_torrent_description_service_parse()
 */
function lt_edit_parse_description($text)
{
	$parsed = lt_torrent_description_service_parse($text);
	$result = array();

	foreach ((array) ($parsed['sections'] ?? array()) as $section) {
		foreach ((array) ($section['items'] ?? array()) as $item) {
			$label = trim((string) ($item['label'] ?? ''));
			if ($label === '') {
				continue;
			}
			$result[$label] = trim((string) ($item['value'] ?? ''));
		}
	}

	return $result;
}

is_login();
lt_torrent_status_ensure_schema();

$act = isset($_GET['act']) ? (string) $_GET['act'] : '';
$screen = isset($_GET['screen']) ? (int) $_GET['screen'] : 0;
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$sql = $db->query('SELECT * FROM torrents WHERE id='.(int) $id);
if (!$db->num_rows($sql)) {
	err($language['default_1'], $language['edit_1'], 1);
}

$arr = $db->get_row($sql);

$isTorrentOwner = ((int) $arr['id_user'] === (int) $USER['id']);
$isTorrentModerator = lt_torrent_can_moderate($USER);
$torrentStatus = lt_torrent_status_normalize($arr['status'] ?? 'approved');

if (!$isTorrentOwner && !$isTorrentModerator) {
	err($language['default_1'], $language['edit_2'], 1);
}

if ($isTorrentOwner && !$isTorrentModerator && in_array($torrentStatus, array('hidden', 'rejected', 'deleted'), true)) {
	err($language['default_1'], 'Этот релиз нельзя редактировать в текущем статусе.', 1);
}

if ($act == 'delete_image') {
	if (!lt_csrf_validate('edit_media_'.$id)) {
		err($language['default_1'], 'Защитный токен устарел. Обновите страницу и попробуйте снова.', 1);
	}

	if (!empty($arr['image'])) {
		$db->query('UPDATE torrents SET image="" WHERE id='.(int) $id);
		$_p = 'public/downloads/images/'.basename((string) $arr['image']);
		if (is_file($_p)) { unlink($_p); }
	}

	lt_edit_redirect_to_details($id);
}

if ($act == 'delete_screen') {
	if (!lt_csrf_validate('edit_media_'.$id)) {
		err($language['default_1'], 'Защитный токен устарел. Обновите страницу и попробуйте снова.', 1);
	}

	if ($screen < 1 || $screen > 4) {
		err($language['default_1'], $language['default_6']);
	}

	if (!empty($arr['screen_'.$screen])) {
		$db->query('UPDATE torrents SET screen_'.$screen.'="" WHERE id='.(int) $id);
		$_p = 'public/downloads/screens/'.basename((string) $arr['screen_'.$screen]);
		if (is_file($_p)) { unlink($_p); }
	}

	lt_edit_redirect_to_details($id);
}

if ($act == 'take') {
	if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
		err($language['default_1'], $language['default_6'], 1);
	}
	if (!lt_csrf_validate('edit_torrent_'.$id)) {
		err($language['default_1'], 'Защитный токен устарел. Обновите страницу и попробуйте снова.', 1);
	}

	$update = array();
	$updateParams = array();
	$updateTypes = '';
	$filelist = array();
	$trackers = array();
	$fname = '';
	$tmpname = '';

	$categories = categories_array();
	$category = (int) ($_POST['category'] ?? 0);
	$categoryName = lt_torrent_category_name_from_list($categories, $category);
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
		$update[] = 'infohash=?'; $updateParams[] = $infohash; $updateTypes .= 's';
		$update[] = 'filename=?'; $updateParams[] = $fname;   $updateTypes .= 's';
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
		$update[] = 'name=?'; $updateParams[] = $name; $updateTypes .= 's';
	}

	$tags = lt_torrent_tags_to_string((string) ($_POST['tags'] ?? ''));
	if ((string) $arr['tags'] !== $tags) {
		$update[] = 'tags=?'; $updateParams[] = $tags; $updateTypes .= 's';
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
		$update[] = 'content_type=?'; $updateParams[] = $contentType; $updateTypes .= 's';
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
			$update[] = $column.'=?'; $updateParams[] = $csv; $updateTypes .= 's';
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
	$primaryDescriptionLabel = lt_torrent_description_service_primary_label($categoryName);
	if ($primaryDescriptionLabel !== '' && trim((string) ($templateValues[$primaryDescriptionLabel] ?? '')) === '') {
		err($language['default_1'], $language['upload_26'], 1);
	}

	$descr = lt_torrent_description_service_build($categoryName, $templateValues, $autoDescriptionValues);
	if ($descr === '') {
		err($language['default_1'], $language['upload_26'], 1);
	}
	if ((string) $arr['descr'] !== $descr) {
		$update[] = 'descr=?'; $updateParams[] = $descr; $updateTypes .= 's';
	}

	if (!empty($_FILES['image']['name'])) {
		$coverExtension = lt_upload_asset_validate_image((array) $_FILES['image'], 'Обложка', (int) $config['max_size_image'], true);
		$coverName = $id.'.'.$coverExtension;
		lt_upload_asset_move_uploaded_image((array) $_FILES['image'], 'public/downloads/images/', $coverName, 'обложку');

		if (!empty($arr['image']) && $arr['image'] !== $coverName) {
			$_p = 'public/downloads/images/'.basename((string) $arr['image']);
			if (is_file($_p)) { unlink($_p); }
		}

		$update[] = 'image=?'; $updateParams[] = $coverName; $updateTypes .= 's';
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

		$screenExtension = lt_upload_asset_validate_image($screenFile, 'Скриншот '.$slot, (int) $config['max_size_image'], true);
		$screenStoredName = $id.'_'.$index.'.'.$screenExtension;
		lt_upload_asset_move_uploaded_image($screenFile, 'public/downloads/screens/', $screenStoredName, 'скриншот '.$slot);

		if (!empty($arr['screen_'.$slot]) && $arr['screen_'.$slot] !== $screenStoredName) {
			$_p = 'public/downloads/screens/'.basename((string) $arr['screen_'.$slot]);
			if (is_file($_p)) { unlink($_p); }
		}

		$update[] = 'screen_'.$slot.'=?'; $updateParams[] = $screenStoredName; $updateTypes .= 's';
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
		$finalParams = array_merge($updateParams, [(int) $id]);
		$finalTypes = $updateTypes . 'i';
		$result = $db->pquery('UPDATE torrents SET '.implode(',', $update).' WHERE id=?', $finalTypes, $finalParams, 1);
		if (!$result) {
			err($language['default_1'], $language['upload_37'], 1);
		}
	}

	if ($fname !== '') {
		$db->query('DELETE FROM files WHERE id_torrent='.(int) $id);
		foreach ($filelist as $fileRow) {
			$db->pquery('INSERT INTO files (id_torrent, filename, size) VALUES (?, ?, ?)', 'isi', [(int) $id, $fileRow[0], (int) $fileRow[1]]);
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

			$db->pquery('UPDATE tags SET howmuch=howmuch+1 WHERE name LIKE ?', 's', [$tag]);
		}

		foreach ($missing as $tag) {
			$tag = trim((string) $tag);
			if ($tag === '') {
				continue;
			}

			$db->pquery('INSERT INTO tags (category, name, howmuch) VALUES (?, ?, 1)', 'is', [(int) $category, $tag]);
		}
	}

	lt_cache_invalidate_tags();
	if ($isTorrentOwner && !$isTorrentModerator && $torrentStatus === 'need_fix') {
		lt_torrent_submit_for_review($id, true);
	}
	lt_edit_redirect_to_details($id);
}

if ($act == 'delete') {
	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['take']) && (int) $_POST['take'] === 1) {
		if (!lt_csrf_validate('edit_delete_'.$id)) {
			err($language['default_1'], 'Защитный токен устарел. Обновите страницу и попробуйте снова.', 1);
		}

		lt_torrent_set_status($id, 'deleted', (int) $USER['id'], ($isTorrentModerator ? 'Удалено модератором' : 'Удалено владельцем'));
		header('Location:index.php');
		die();
	}

	head('Удалить релиз');
	msg('Удалить релиз', 'Вы действительно хотите удалить релиз?');
	echo '<form method="post" action="edit.php?act=delete&id='.(int) $id.'" style="display:inline-block;">';
	echo lt_csrf_input('edit_delete_'.$id);
	echo '<input type="hidden" name="take" value="1">';
	echo '<button type="submit">'.$language['upload_38'].'</button>';
	echo '</form> ';
	echo '<input type="button" value="'.$language['default_5'].'" onClick="history.go(-1)">';
	foot();
	die();
}

$categories = categories_array();
$categoryTemplateMap = lt_torrent_category_template_map($categories);

$selectedCategoryId = (int) ($arr['id_category'] ?? 0);
$selectedCategoryName = lt_torrent_category_name_from_list($categories, $selectedCategoryId);
$metadataSchema = lt_torrent_metadata_schema();
$metadataValues = lt_torrent_metadata_service_values($arr, $metadataSchema);
$typeOptionsMap = lt_torrent_type_options_map();
$descriptionTemplates = lt_torrent_description_templates();
$templateFieldExamples = lt_torrent_template_examples_map($descriptionTemplates);
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
$currentTemplateFields = lt_torrent_description_service_manual_fields($selectedCategoryName, $parsedDescriptionValues);
$tagSuggestions = taggenrelist($selectedCategoryId);
$currentScreens = lt_edit_collect_screens($arr);
$currentCover = trim((string) ($arr['image'] ?? ''));

head($language['edit_3'], true);
?>
<script type="text/javascript" src="public/js/tagto.js"></script>
<script type="text/javascript" src="/public/js/tags-suggest.js"></script>
<?php if (!empty($config['metadata_grabber_enabled'])) { ?>
<script type="text/javascript" src="/public/js/metadata-search.js"></script>
<?php } ?>

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
			<?=lt_csrf_input('edit_torrent_'.$id);?>

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
						<label class="upload-label">Сведения о релизе</label>
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
								<a class="upload-top-link upload-top-link-red" href="edit.php?id=<?=(int) $id;?>&amp;act=delete_image&amp;<?=lt_csrf_query('edit_media_'.$id);?>">Удалить</a>
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
										<a class="upload-top-link upload-top-link-red" href="edit.php?id=<?=(int) $id;?>&amp;act=delete_screen&amp;screen=<?=(int) $screenItem['index'];?>&amp;<?=lt_csrf_query('edit_media_'.$id);?>">Удалить</a>
									</div>
								</div>
								<?php } ?>
							</div>
						</div>
						<?php } ?>
					</div>

					<div class="upload-field">
						<label class="upload-label" for="edit_tags"><?=$language['upload_12'];?></label>
						<input id="edit_tags" class="upload-input" type="text" name="tags" value="<?=htmlspecialchars(lt_torrent_tags_to_string((string) $arr['tags']), ENT_QUOTES, 'UTF-8');?>" placeholder="через запятую" data-tags-suggest data-tags-suggest-url="/api/tags_suggest.php">
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

<script type="text/javascript" src="/public/js/torrent-description-form.js"></script>
<script type="text/javascript">
$(document).ready(function () {
	$('#from').tagTo('#edit_tags');
});

window.initTorrentDescriptionForm({
	formSelector: '.edit-upload-form',
	categorySelector: '#edit_category',
	typeSelector: '#edit_type_options',
	descriptionSelector: '#edit_descr',
	templateFieldsContainerSelector: '#edit_template_fields',
	fieldIdPrefix: 'edit_template_',
	typeOptions: <?=json_encode($typeOptionsMap, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);?>,
	templates: <?=json_encode($descriptionTemplates, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);?>,
	examples: <?=json_encode($templateFieldExamples, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);?>,
	defaultTemplateKey: 'movies',
	initialRenderFields: true
});
</script>
<?php
foot(true);
?>
