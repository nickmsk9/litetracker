<?php
/*
===================================================================
LiteTracker Source
===================================================================
by Nick

	// $tags = str_replace($replace, ",", $_POST["tags"], MB_CASE_TITLE, $config['mysql']['charset'])));
$act = isset($_GET['act']) ? (string)$_GET['act'] : '';
$screen = isset($_GET['screen']) ? (int)$_GET['screen'] : 0;
$cats = '';
$tags_echo = '';


//Проверяем права
if($arr['id_user'] != $USER['id'] && !$PRIV['edit_release']) {
	err($language['default_1'] , $language['edit_2'] , 1);
}



//////////////////////////////////////////////////////////////////////////
//Удаление обложки
//////////////////////////////////////////////////////////////////////////
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

function lt_edit_redirect_to_details($id) {
	header('Location:details.php?id='.(int) $id.'&edit=1');
	die();
}

function lt_edit_collect_screens($torrent) {
	$result = array();

	for ($index = 1; $index <= 4; $index++) {
		$key = 'screen_'.$index;
		if (!empty($torrent[$key])) {
			$result[] = array(
				'index' => $index,
				'file' => $torrent[$key],
			);
		}
	}

	return $result;
}

is_login();

$act = isset($_GET['act']) ? (string) $_GET['act'] : '';
$screen = isset($_GET['screen']) ? (int) $_GET['screen'] : 0;
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$delete = array();

$sql = $db->query('SELECT * FROM torrents WHERE id='.$id);
if (!$db->num_rows($sql)) {
	err($language['default_1'], $language['edit_1'], 1);
}

$arr = $db->get_row($sql);

if ($arr['id_user'] != $USER['id'] && !$PRIV['edit_release']) {
	err($language['default_1'], $language['edit_2'], 1);
}

if ($act == 'delete_image') {
	if ($arr['image']) {
		$db->query('UPDATE torrents SET image="" WHERE id='.$id);
		@unlink('public/downloads/images/'.$arr['image']);
	}

	lt_edit_redirect_to_details($id);
}

if ($act == 'delete_screen') {
	if ($screen > 4 || $screen < 1) {
		err($language['default_1'], $language['default_6']);
	}

	if ($arr['screen_'.$screen]) {
		$db->query('UPDATE torrents SET screen_'.$screen.'="" WHERE id='.$id);
		@unlink('public/downloads/screens/'.$arr['screen_'.$screen]);
	}

	lt_edit_redirect_to_details($id);
}

if ($act == 'take') {
	$update = array();
	$anarray = array();
	$filelist = array();
	$fname = '';
	$tmpname = '';

	$multi = (!empty($_POST['multi']) ? 1 : 0);
	if ($arr['multi'] != $multi) {
		$update[] = 'multi="'.$multi.'"';
	}

	$f = $_FILES['file'];
	$fname = trim((string) $f['name']);
	if ($fname !== '') {
		if (!validfilename($fname)) {
			err($language['default_1'], $language['upload_20'], 1);
		}

		if (!preg_match('/^(.+)\.torrent$/si', $fname, $matches)) {
			err($language['default_1'], $language['upload_21'], 1);
		}

		$tmpname = $f['tmp_name'];
		if (!is_uploaded_file($tmpname)) {
			err($language['default_1'], $language['upload_22'], 1);
		}

		if (!filesize($tmpname)) {
			err($language['default_1'], $language['upload_23'], 1);
		}

		$dict = bdec_file($tmpname, (1024 * 1024));
		unset($dict['value']['nodes']);
		unset($dict['value']['azureus_properties']);
		unset($dict['value']['comment']);
		unset($dict['value']['created by']);
		unset($dict['value']['publisher']);
		unset($dict['value']['publisher.windows-1251']);
		unset($dict['value']['publisher-url']);
		unset($dict['value']['publisher-url.windows-1251']);

		if (!$multi) {
			unset($dict['value']['announce-list']);
			unset($dict['value']['announce']);
		} else {
			$anarray = get_announce_urls($dict);
		}

		if ($multi && !$anarray) {
			err($language['default_1'], $language['upload_24'], 1);
		}

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
			if (!is_array($flist)) {
				err('missing both length and files');
			}
			if (count($flist) === 0) {
				err('no files');
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

				if (!count($ffa)) {
					err('filename error');
				}

				$ffe = implode('/', $ffa);
				$filelist[] = array($ffe, $ll);

				if ($ffe == 'Thumbs.db') {
					err($language['default_1'], $language['upload_44'], 1);
				}
			}

			$type = 'multi';
		}

		$infohash = sha1($info['string']);
		$update[] = 'infohash="'.$db->safesql($infohash).'"';
		$update[] = 'filename = "'.$db->safesql($fname).'"';
		$update[] = 'size = "'.$totallen.'"';
		$update[] = 'multi = "'.$multi.'"';
		$update[] = 'num_files = "'.count($filelist).'"';
		$update[] = 'type = "'.$type.'"';
	}

	$category = (int) $_POST['category'];
	if ($arr['id_category'] != $category) {
		$db->query('SELECT * FROM categories WHERE id='.$category);
		if ($db->num_rows() == 0) {
			err($language['default_1'], $language['upload_3'], 1);
		}
		$update[] = 'id_category="'.$category.'"';
	} else {
		$category = (int) $arr['id_category'];
	}

	$name = trim((string) $_POST['name']);
	if ($arr['name'] != $name) {
		if ($name === '') {
			err($language['default_1'], $language['upload_25'], 1);
		}
		$update[] = 'name="'.$db->safesql($name).'"';
	}

	$descr = isset($_POST['descr']) ? (string) $_POST['descr'] : '';
	if ($arr['descr'] != $descr) {
		if ($descr === '') {
			err($language['default_1'], $language['upload_26'], 1);
		}
		$update[] = 'descr="'.$db->safesql($descr).'"';
	}

	$allowed_types = array(
		'image/gif' => 'gif',
		'image/pjpeg' => 'jpg',
		'image/jpeg' => 'jpg',
		'image/jpg' => 'jpg',
		'image/png' => 'png',
	);

	if (!empty($_FILES['image']['name'])) {
		if (!array_key_exists($_FILES['image']['type'], $allowed_types)) {
			err($language['default_1'], $language['upload_27'], 1);
		}

		if (!preg_match('/^(.+)\.(jpg|jpeg|png|gif)$/si', $_FILES['image']['name'])) {
			err($language['default_1'], $language['upload_28'], 1);
		}

		if ($_FILES['image']['size'] > $config['max_size_image']) {
			err($language['default_1'], sprintf($language['upload_29'], mksize($config['max_size_image'])), 1);
		}

		$uploaddir = 'public/downloads/images/';
		$ifile = $_FILES['image']['tmp_name'];
		$ifilename = $id.substr($_FILES['image']['name'], strlen($_FILES['image']['name']) - 4, 4);

		@unlink($uploaddir.$arr['image']);
		$copy = copy($ifile, $uploaddir.$ifilename);
		if (!$copy) {
			err($language['default_1'], $language['upload_30'], 1);
		}

		$update[] = 'image="'.$db->safesql($ifilename).'"';
	}

	for ($x = 0; $x < 4; $x++) {
		$screenName = isset($_FILES['screenshot']['name'][$x]) ? (string) $_FILES['screenshot']['name'][$x] : '';
		if ($screenName === '') {
			continue;
		}

		$y = $x + 1;
		if (!array_key_exists($_FILES['screenshot']['type'][$x], $allowed_types)) {
			err($language['default_1'], sprintf($language['upload_32'], $y), 1);
		}

		if (!preg_match('/^(.+)\.(jpg|jpeg|png|gif)$/si', $screenName)) {
			err($language['default_1'], sprintf($language['upload_33'], $y), 1);
		}

		if ($_FILES['screenshot']['size'][$x] > $config['max_size_image']) {
			err($language['default_1'], sprintf($language['upload_34'], $y), 1);
		}

		$uploaddir_screen = 'public/downloads/screens/';
		$ifile = $_FILES['screenshot']['tmp_name'][$x];
		$ifilename_screen = $id.$x.substr($screenName, strlen($screenName) - 4, 4);

		@unlink($uploaddir_screen.$arr['screen_'.$y]);
		$copy_screen = copy($ifile, $uploaddir_screen.$ifilename_screen);
		if (!$copy_screen) {
			err($language['default_1'], sprintf($language['upload_35'], $y), 1);
		}

		$update[] = 'screen_'.$y.'="'.$db->safesql($ifilename_screen).'"';
	}

	$tags = trim((string) $_POST['tags']);
	if ($arr['tags'] != $tags) {
		$update[] = 'tags="'.$db->safesql($tags).'"';
	}

	if ($PRIV['edit_news']) {
		$news = (!empty($_POST['news']) ? '1' : '0');
		$update[] = 'news="'.$news.'"';
	} else {
		$update[] = 'news="0"';
	}

	if ($PRIV['edit_banned']) {
		$banned = (!empty($_POST['banned']) ? '1' : '0');
		$update[] = 'banned="'.$banned.'"';
	}

	if ($update) {
		$add = $db->query('UPDATE torrents SET '.implode(',', $update).' WHERE id='.$id, 1);
		if (!$add) {
			err($language['default_1'], $language['upload_37'], 1);
		}
	}

	if ($fname !== '') {
		$db->query('DELETE FROM files WHERE id_torrent='.$id);
		foreach ($filelist as $file) {
			$db->query('INSERT INTO files (id_torrent, filename, size) VALUES ('.$id.', "'.$db->safesql($file[0]).'", "'.$file[1].'")');
		}
		move_uploaded_file($tmpname, 'public/downloads/torrents/'.$id.'.torrent');

		$db->query('DELETE FROM trackers WHERE torrent='.$id);
		$db->query('INSERT INTO trackers (torrent,tracker) VALUES ("'.$id.'","localhost")');
		if ($anarray) {
			foreach ($anarray as $anurl) {
				$db->query('INSERT INTO trackers (torrent,tracker) VALUES ("'.$id.'","'.$db->safesql($anurl).'")');
			}
		}
	}

	if ($arr['id_category'] != $category && !empty($descr_array) && is_array($descr_array)) {
		foreach ($descr_array as $this_val => $this_name) {
			$delete[] = $this_val.'=""';
		}

		if ($delete) {
			$db->query('UPDATE torrents SET '.implode(',', $delete).' WHERE id='.$id);
		}
	}

	if ($arr['tags'] != $tags) {
		$ret = array();
		$res = $db->query('SELECT name FROM tags WHERE category = '.$category);
		while ($row = $db->get_row()) {
			$ret[] = $row['name'];
		}

		$tag_list = array_map('trim', explode(',', $tags));
		$tag_list = array_filter($tag_list, 'strlen');
		$union = array_intersect($ret, $tag_list);
		$ununion = array_diff($tag_list, $ret);

		foreach ($union as $tag) {
			$tag = trim($tag);
			if ($tag === '') {
				continue;
			}
			$db->query('UPDATE tags SET howmuch=howmuch+1 WHERE name LIKE "'.$db->safesql($tag).'"');
		}

		foreach ($ununion as $tag) {
			$tag = trim($tag);
			if ($tag === '') {
				continue;
			}
			$db->query('INSERT INTO tags (category, name, howmuch) VALUES ("'.$category.'", "'.$db->safesql($tag).'", 1)');
		}
	}

	$memcached->delete('tags');
	lt_edit_redirect_to_details($id);
}

if ($act == 'delete') {
	if (isset($_GET['take']) && (int) $_GET['take'] === 1) {
		$db->query('DELETE FROM torrents WHERE id='.$id);
		$db->query('DELETE FROM trackers WHERE torrent='.$id);
		$db->query('DELETE FROM peers WHERE torrent='.$id);
		$db->query('DELETE FROM snatched WHERE torrent='.$id);
		@unlink('public/downloads/images/'.$arr['image']);
		@unlink('public/downloads/torrents/'.$id.'.torrent');
		@unlink('public/downloads/screens/'.$arr['screen_1']);
		@unlink('public/downloads/screens/'.$arr['screen_2']);
		@unlink('public/downloads/screens/'.$arr['screen_3']);
		@unlink('public/downloads/screens/'.$arr['screen_4']);
		header('Location:index.php');
		die();
	}

	head('Удалить релиз');
	msg('Удалить релиз', 'Вы действительно хотите удалить релиз?');
	echo '<input type="button" value="'.$language['upload_38'].'" onClick="window.location.href=\'edit.php?act=delete&id='.$id.'&take=1\'"> ';
	echo '<input type="button" value="'.$language['default_5'].'" onClick="history.go(-1)">';
	foot();
	die();
}

$categories = categories_array();
$categoryOptions = '';
foreach ($categories as $categoryItem) {
	$categoryOptions .= '<option value="'.$categoryItem['id'].'"'.($arr['id_category'] == $categoryItem['id'] ? ' selected' : '').'>'.htmlspecialchars($categoryItem['name'], ENT_QUOTES, 'UTF-8').'</option>';
}

$tagSuggestions = taggenrelist($arr['id_category']);
$currentScreens = lt_edit_collect_screens($arr);

head($language['edit_3'], true);
?>
<script type="text/javascript" src="public/js/tagto.js"></script>
<script type="text/javascript">
	$(document).ready(function () {
		$('#from').tagTo('#edit_tags');
	});
</script>

<div class="upload-page">
	<section class="upload-shell">
		<div class="upload-header">
			<h1 class="upload-title"><?=$language['edit_3'];?></h1>
			<div class="upload-actions">
				<a class="upload-top-link upload-top-link-green" href="details.php?id=<?=$id;?>">Вернуться к раздаче</a>
				<div class="upload-top-danger">
					<a class="upload-top-link upload-top-link-red" href="edit.php?act=delete&id=<?=$id;?>"><?=$language['upload_38'];?></a>
					<span class="upload-top-note">удаление раздачи целиком</span>
				</div>
			</div>
		</div>

		<form class="upload-form" enctype="multipart/form-data" action="edit.php?act=take&id=<?=$id;?>" method="post" name="upload">
			<input type="hidden" value="<?=$id;?>" name="id">

			<div class="upload-grid">
				<div class="upload-grid-main">
					<div class="upload-field">
						<label class="upload-label" for="edit_name"><?=$language['upload_9'];?></label>
						<input id="edit_name" class="upload-input" type="text" name="name" value="<?=htmlspecialchars($arr['name'], ENT_QUOTES, 'UTF-8');?>" required>
					</div>

					<div class="upload-field">
						<label class="upload-label" for="edit_category">Категория</label>
						<select id="edit_category" class="upload-select" name="category" required>
							<?=$categoryOptions;?>
						</select>
					</div>

					<div class="upload-field upload-field-description">
						<label class="upload-label" for="edit_descr">Описание</label>
						<textarea id="edit_descr" class="upload-textarea" name="descr" required><?=htmlspecialchars($arr['descr'], ENT_QUOTES, 'UTF-8');?></textarea>
					</div>

					<div class="upload-field">
						<label class="upload-label" for="edit_tags"><?=$language['upload_12'];?></label>
						<input id="edit_tags" class="upload-input" type="text" name="tags" value="<?=htmlspecialchars($arr['tags'], ENT_QUOTES, 'UTF-8');?>" placeholder="через запятую">
						<div class="upload-hint" id="from">
							<?php if (!$tagSuggestions) { ?>
								<?=$language['upload_13'];?>
							<?php } else { ?>
								<?php foreach ($tagSuggestions as $tagRow) { ?>
								<a href="#"><?=htmlspecialchars($tagRow['name'], ENT_QUOTES, 'UTF-8');?></a>
								<?php } ?>
							<?php } ?>
						</div>
					</div>

					<fieldset class="upload-section">
						<legend class="upload-section-title">Параметры</legend>
						<div class="upload-option-grid upload-option-grid-cols-2">
							<label class="upload-option">
								<input type="checkbox" name="multi" value="1"<?=($arr['multi'] ? ' checked' : '');?>>
								<span><?=$language['upload_15'];?></span>
							</label>
							<?php if ($PRIV['edit_news']) { ?>
							<label class="upload-option">
								<input type="checkbox" name="news" value="1"<?=($arr['news'] ? ' checked' : '');?> >
								<span><?=$language['upload_40'];?></span>
							</label>
							<?php } ?>
							<?php if ($PRIV['edit_banned'] && $arr['id_user'] != $USER['id']) { ?>
							<label class="upload-option">
								<input type="checkbox" name="banned" value="1"<?=($arr['banned'] ? ' checked' : '');?> >
								<span><?=$language['upload_43'];?></span>
							</label>
							<?php } ?>
						</div>
					</fieldset>
				</div>

				<div class="upload-grid-side">
					<div class="upload-field">
						<label class="upload-label" for="edit_file"><?=$language['upload_4'];?></label>
						<input id="edit_file" class="upload-input upload-file-input" type="file" name="file" accept=".torrent">
						<div class="upload-hint">Текущий файл: <?=htmlspecialchars((string) $arr['filename'], ENT_QUOTES, 'UTF-8');?></div>
					</div>

					<div class="upload-field">
						<label class="upload-label" for="edit_cover"><?=$language['upload_5'];?></label>
						<input id="edit_cover" class="upload-input upload-file-input" type="file" name="image" accept=".jpg,.jpeg,.png,.gif">
						<div class="upload-hint"><?=sprintf($language['upload_6'], mksize($config['max_size_image']));?></div>
						<?php if (!empty($arr['image'])) { ?>
						<div class="upload-hint">
							<a class="upload-top-link upload-top-link-red" href="edit.php?id=<?=$id;?>&act=delete_image">Удалить текущую обложку</a>
						</div>
						<a href="public/downloads/images/<?=htmlspecialchars($arr['image'], ENT_QUOTES, 'UTF-8');?>" target="_blank" rel="noopener noreferrer">
							<img src="public/downloads/images/<?=htmlspecialchars($arr['image'], ENT_QUOTES, 'UTF-8');?>" alt="Обложка" style="width:100%;border-radius:18px;display:block;">
						</a>
						<?php } ?>
					</div>

					<div class="upload-field">
						<label class="upload-label" for="edit_screens"><?=$language['upload_7'];?></label>
						<input id="edit_screens" class="upload-input upload-file-input" type="file" name="screenshot[]" accept=".jpg,.jpeg,.png,.gif" multiple>
						<div class="upload-hint">до 4 изображений</div>
						<?php if ($currentScreens) { ?>
						<div class="upload-hint">
							<?php foreach ($currentScreens as $screenItem) { ?>
							<div style="margin-top:10px;">
								<a href="public/downloads/screens/<?=htmlspecialchars($screenItem['file'], ENT_QUOTES, 'UTF-8');?>" target="_blank" rel="noopener noreferrer">
									<img src="public/downloads/screens/<?=htmlspecialchars($screenItem['file'], ENT_QUOTES, 'UTF-8');?>" alt="Скриншот <?=$screenItem['index'];?>" style="width:100%;border-radius:14px;display:block;">
								</a>
								<div style="margin-top:6px;">
									<a class="upload-top-link upload-top-link-red" href="edit.php?id=<?=$id;?>&act=delete_screen&screen=<?=$screenItem['index'];?>">Удалить скрин <?=$screenItem['index'];?></a>
								</div>
							</div>
							<?php } ?>
						</div>
						<?php } ?>
					</div>
				</div>
			</div>

			<div class="upload-footer">
				<button class="upload-submit" type="submit"><?=$language['details_23'];?></button>
			</div>
		</form>
	</section>
</div>
<?php
foot(true);
$cache_result = categories_array();
