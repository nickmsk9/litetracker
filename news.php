<?php
/*
===================================================================
LiteTracker Source
===================================================================
by Nick
-------------------------------------------------------------------
Назначение: Новости
===================================================================
*/

//Подключаем главный системный файл
require 'system/init.php';

$act = (string) ($_GET['act'] ?? '');
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$status = (string) ($_GET['status'] ?? '');

function lt_news_format_publication_date($date)
{
	global $language;

	$date = trim((string) $date);
	if ($date === '' || strpos($date, ' ') === false) {
		return convent_date($date);
	}

	$months = array(
		'01' => $language['month_1'],
		'02' => $language['month_2'],
		'03' => $language['month_3'],
		'04' => $language['month_4'],
		'05' => $language['month_5'],
		'06' => $language['month_6'],
		'07' => $language['month_7'],
		'08' => $language['month_8'],
		'09' => $language['month_9'],
		'10' => $language['month_10'],
		'11' => $language['month_11'],
		'12' => $language['month_12'],
	);

	list($datePart, $timePart) = explode(' ', $date, 2);
	$explodeDate = explode('-', $datePart);
	$explodeTime = explode(':', $timePart);

	if (count($explodeDate) !== 3 || count($explodeTime) < 2) {
		return convent_date($date);
	}

	$day = (int) $explodeDate[2];
	$month = ($months[$explodeDate[1]] ?? $explodeDate[1]);
	$year = (int) $explodeDate[0];
	$hour = (int) $explodeTime[0];
	$minute = str_pad((string) ((int) $explodeTime[1]), 2, '0', STR_PAD_LEFT);

	return $day.' '.$month.' '.$year.' в '.$hour.':'.$minute;
}

function lt_news_is_ajax_request()
{
	$requestedWith = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));
	$accept = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));

	return ($requestedWith === 'xmlhttprequest' || strpos($accept, 'application/json') !== false);
}

function lt_news_json_response($ok, $message = '', $extra = array())
{
	header('Content-Type: application/json; charset=UTF-8');
	echo json_encode(array_merge(
		array(
			'ok' => (int) (bool) $ok,
			'message' => (string) $message,
		),
		(array) $extra
	), JSON_UNESCAPED_UNICODE);
	die();
}




////////////////////////////////////////////////////////////////////
//Редактирование новости
////////////////////////////////////////////////////////////////////
if($act == 'edit' && $id) {

	//Только Администраторам , Модераторам
	if(!$PRIV['news_add']) {
		err($language['default_1'] , $language['default_10'] , 1);
	}

	$db->query("SELECT * FROM news WHERE id=".$id."");
	if(!$db->num_rows() ) {
		err($language['default_1'] , $language['news_1']);
	}
	$arr = $db->get_row();

	//Обработка новости
	if(count($_POST) ) {
		$isAjaxRequest = lt_news_is_ajax_request();
		$update = array();
		$updateParams = array();
		$updateTypes = '';

		//Название
		$name = lt_fix_utf8_mojibake(trim((string) ($_POST['name'] ?? '')));
		if($arr['name'] != $name) {
			if(empty($name) ) {
				if ($isAjaxRequest) {
					lt_news_json_response(false, $language['news_2']);
				}
				err($language['default_1'] , $language['news_2'] , 1);
			}
			$update[] = 'name=?'; $updateParams[] = $name; $updateTypes .= 's';
		}


		//Описание
		$text = lt_fix_utf8_mojibake((string) ($_POST['text'] ?? ''));
		if($arr['text'] != $text) {
			if(empty($text) ) {
				if ($isAjaxRequest) {
					lt_news_json_response(false, $language['news_3']);
				}
				err($language['default_1'] , $language['news_3'] , 1);
			}
			$update[] = 'text=?'; $updateParams[] = $text; $updateTypes .= 's';
		}

		//Поднятие новости
		$up = (int)$_POST['up'];
		if($up) {
			$update[] = "date=NOW()";
		}
		//Обновляем новость
		if(count($update)) {
			$updateParams[] = (int) $id; $updateTypes .= 'i';
			$db->pquery("UPDATE news SET ".implode(',' , $update)." WHERE id=?", $updateTypes, $updateParams);
		}

		//Удаляем старый кеш
		$memcached->delete('news');
		$memcached->delete('sidebar_news_all');

		if ($isAjaxRequest) {
			$updatedNews = $db->super_query("SELECT id, name, text, date FROM news WHERE id=".(int) $id." LIMIT 1");
			$updatedName = htmlspecialchars(lt_fix_utf8_mojibake((string) ($updatedNews['name'] ?? $name)), ENT_QUOTES, 'UTF-8');
			$updatedText = cleanhtml(lt_fix_utf8_mojibake((string) ($updatedNews['text'] ?? $text)));
			$updatedPublishedAt = lt_news_format_publication_date((string) ($updatedNews['date'] ?? $arr['date']));

			lt_news_json_response(true, (string) ($language['news_19'] ?? 'Новость сохранена'), array(
				'name' => $updatedName,
				'text' => $updatedText,
				'published_at' => $updatedPublishedAt,
			));
		}

		header("Location:news.php?id=".$id."");
		die();
	}

	head($language['news_4']);
	begin_frame($language['news_4']);
	?>
	<div class="comment-ajax-notice news-ajax-notice" data-news-edit-notice hidden></div>
	<form
		enctype="multipart/form-data"
		action="news.php?act=edit&id=<?=$id;?>"
		method="post"
		name="news"
		class="news-editor-form"
		data-news-edit-form="1"
		data-news-view-url="news.php?id=<?=$id;?>"
		data-label-submit="<?=htmlspecialchars((string) ($language['news_8'] ?? 'Редактировать'), ENT_QUOTES, 'UTF-8');?>"
		data-label-saving="<?=htmlspecialchars((string) ($language['default_4'] ?? 'Загрузка...'), ENT_QUOTES, 'UTF-8');?>"
		data-message-saved="<?=htmlspecialchars((string) ($language['news_19'] ?? 'Новость сохранена'), ENT_QUOTES, 'UTF-8');?>"
		data-message-save-error="<?=htmlspecialchars((string) ($language['news_20'] ?? 'Не удалось сохранить новость'), ENT_QUOTES, 'UTF-8');?>"
		data-message-save-error-retry="<?=htmlspecialchars((string) ($language['news_21'] ?? 'Не удалось сохранить новость. Попробуйте ещё раз.'), ENT_QUOTES, 'UTF-8');?>"
	>
		<div class="news-editor-grid">
			<label class="news-editor-field">
				<span class="news-editor-label"><?=$language['news_5'];?>:</span>
				<input class="news-editor-input" type="text" name="name" value="<?=htmlspecialchars(lt_fix_utf8_mojibake((string) $arr['name']), ENT_QUOTES, 'UTF-8');?>">
			</label>

			<label class="news-editor-field news-editor-field-full">
				<span class="news-editor-label">Текст новости:</span>
				<textarea class="news-editor-textarea" name="text"><?=htmlspecialchars(lt_fix_utf8_mojibake((string) $arr['text']), ENT_QUOTES, 'UTF-8');?></textarea>
			</label>

			<label class="news-editor-checkbox">
				<input type="checkbox" name="up" value="1">
				<span><?=$language['news_7'];?></span>
			</label>

			<div class="news-editor-actions">
				<input class="news-editor-submit" type="submit" value="<?=$language['news_8'];?>">
			</div>
		</div>
	</form>
	<script>
	document.addEventListener('DOMContentLoaded', function () {
		var form = document.querySelector('[data-news-edit-form]');
		var notice = document.querySelector('[data-news-edit-notice]');
		if (!form) {
			return;
		}

		function showNotice(message, isError) {
			if (!notice) {
				return;
			}
			notice.textContent = message || '';
			notice.className = 'comment-ajax-notice news-ajax-notice' + (isError ? ' comment-ajax-notice-error' : ' comment-ajax-notice-success');
			notice.hidden = !message;
		}

		form.addEventListener('submit', function (event) {
			event.preventDefault();
			var submit = form.querySelector('.news-editor-submit');
			var formData = new FormData(form);
			var submitLabel = form.getAttribute('data-label-submit') || 'Редактировать';
			var savingLabel = form.getAttribute('data-label-saving') || 'Загрузка...';
			var savedMessage = form.getAttribute('data-message-saved') || 'Новость сохранена';
			var saveErrorMessage = form.getAttribute('data-message-save-error') || 'Не удалось сохранить новость';
			var saveRetryMessage = form.getAttribute('data-message-save-error-retry') || 'Не удалось сохранить новость. Попробуйте ещё раз.';

			if (submit) {
				submit.disabled = true;
				submit.value = savingLabel;
			}
			showNotice('', false);

			fetch(form.getAttribute('action'), {
				method: 'POST',
				body: formData,
				headers: {
					'X-Requested-With': 'XMLHttpRequest',
					'Accept': 'application/json'
				}
			})
				.then(function (response) {
					return response.json();
				})
				.then(function (payload) {
					if (!payload || !payload.ok) {
						showNotice((payload && payload.message) ? payload.message : saveErrorMessage, true);
						return;
					}

					showNotice(payload.message || savedMessage);
				})
				.catch(function () {
					showNotice(saveRetryMessage, true);
				})
				.then(function () {
					if (submit) {
						submit.disabled = false;
						submit.value = submitLabel;
					}
				});
		});
	});
	</script>
	<?php
	end_frame();
	foot();
	die();
}



////////////////////////////////////////////////////////////////////
//Добавление новости
////////////////////////////////////////////////////////////////////
if($act == 'add') {

	//Только Администраторам , Модераторам
	if(!$PRIV['news_add']) {
		err($language['default_1'] , $language['default_10'] , 1);
	}


	//Обработка новости
	if(count($_POST) ) {
		//Название
		$name = lt_fix_utf8_mojibake(trim((string) ($_POST['name'] ?? '')));
		if(empty($name) ) {
			err($language['default_1'] , $language['news_2'] , 1);
		}

		//Описание
		$text = lt_fix_utf8_mojibake((string) ($_POST['text'] ?? ''));
		if(empty($text) ) {
			err($language['default_1'] , $language['news_3'] , 1);
		}

		//Добавляем новость
		$db->pquery("INSERT INTO news (name , text , id_user , date) VALUES (?, ?, ?, NOW())", 'ssi', [$name, $text, (int) $USER['id']]);
		$id = $db->insert_id();


		//Удаляем старый кеш
		$memcached->delete('news');
		$memcached->delete('sidebar_news_all');
		header("Location:news.php?id=".$id."");
		die();
	}

	head($language['news_9']);
	begin_frame($language['news_9']);
	?>
	<form enctype="multipart/form-data" action="news.php?act=add" method="post" name="news" class="news-editor-form">
		<div class="news-editor-grid">
			<label class="news-editor-field">
				<span class="news-editor-label"><?=$language['news_5'];?>:</span>
				<input class="news-editor-input" type="text" name="name" value="<?=htmlspecialchars(lt_fix_utf8_mojibake((string) ($_POST['name'] ?? '')), ENT_QUOTES, 'UTF-8');?>">
			</label>

			<label class="news-editor-field news-editor-field-full">
				<span class="news-editor-label">Текст новости:</span>
				<textarea class="news-editor-textarea" name="text"><?=htmlspecialchars(lt_fix_utf8_mojibake((string) ($_POST['text'] ?? '')), ENT_QUOTES, 'UTF-8');?></textarea>
			</label>

			<div class="news-editor-actions">
				<input class="news-editor-submit" type="submit" value="<?=$language['news_10'];?>">
			</div>
		</div>
	</form>
	<?php
	end_frame();
	foot();
	die();
}


////////////////////////////////////////////////////////////////////
//Удаление новости
////////////////////////////////////////////////////////////////////
if($act == 'delete' && $id) {


	//Только Администраторам , Модераторам
	if(!$PRIV['news_add']) {
		err($language['default_1'] , $language['default_10'] , 1);
	}

	//Проверяем , существует ли новость
	$db->query("SELECT * FROM news WHERE id=".$id."");
	if(!$db->num_rows() ) {
		err($language['default_1'] , $language['news_1']);
	}

	//Удаляем новость
	$db->query("DELETE FROM news WHERE id=".$id."");


	//Удаляем старый кеш
	$memcached->delete('news');
	$memcached->delete('sidebar_news_all');
	header("Location:news.php?status=1");
	die();
}




////////////////////////////////////////////////////////////////////
//Просмотр новости
////////////////////////////////////////////////////////////////////
if($id && $act == '') {


	$db->query("SELECT *
				FROM news
				WHERE id=".$id."");
	if(!$db->num_rows() ) {
		err($language['default_1'] , $language['news_1']);
	}
	$arr = $db->get_row();


	//Номер новости
	$id = $arr['id'];


	//Название новости
	$name = htmlspecialchars(lt_fix_utf8_mojibake((string) $arr['name']), ENT_QUOTES, 'UTF-8');

	//Текст новости
	$text = cleanhtml(lt_fix_utf8_mojibake((string) $arr['text']));

	//Дата добавления
	$date = convent_date($arr['date']);
	$published_at = lt_news_format_publication_date($arr['date']);

	/////////////////////////////////////////////////////////
	//Пользователь
	/////////////////////////////////////////////////////////
	$user = get_user_info($arr['id_user']);
	//ID пользователя
	$user_id = $user['id'];
	//Имя пользователя
	$user_name = $user['name'];
	//Класс пользователя
	$user_class = $user['class'];

	//Определяем  , что это детали новости
	define('NEWS_DETAILS' , true);

	head($name);

	//Выводим статусы
	comment_status();

	//Подключаем шаблон
	require 'templates/'.$config['template'].'/tpl.news.php';

	stdfoot();
	die();
}


////////////////////////////////////////////////////////////////////
//Все новости
////////////////////////////////////////////////////////////////////

//Заголовок
head($language['news_11']);

//Статусы
if($status == '1') {
	msg($language['news_12']);
}

$db->query("SELECT id, name, text, date FROM news ORDER BY date DESC");
?>
<div class="news-archive-page">
	<div class="news-archive-head">
		<h1 class="news-archive-page-title">Новости</h1>
		<?php if (!empty($PRIV['news_add'])) { ?>
		<a class="news-archive-manage-link" href="news.php?act=add"><?=$language['news_9'];?></a>
		<?php } ?>
	</div>

	<?php if(!$db->num_rows() ) { ?>
	<div class="news-empty-state"><div class="profile-empty-state"><?=$language['news_13'];?></div></div>
	<?php } else { ?>
	<div class="news-archive-list">
		<?php while($arr = $db->get_row() ) { ?>
		<?php
		$title = htmlspecialchars(lt_fix_utf8_mojibake((string) $arr['name']), ENT_QUOTES, 'UTF-8');
		$excerpt = trim(preg_replace('~\s+~u', ' ', strip_tags(cleanhtml(lt_fix_utf8_mojibake((string) $arr['text'])))));
		?>
		<article class="news-archive-item">
			<a class="news-archive-link" href="news.php?id=<?=(int) $arr['id'];?>">
				<h2 class="news-archive-title"><?=$title;?></h2>
				<div class="news-archive-date"><?=convent_date($arr['date']);?></div>
				<?php if ($excerpt !== '') { ?>
				<p class="news-archive-excerpt"><?=htmlspecialchars($excerpt, ENT_QUOTES, 'UTF-8');?></p>
				<?php } ?>
			</a>
		</article>
		<?php } ?>
	</div>
	<?php } ?>
</div>
<?php

//Подвал
stdfoot();


?>
