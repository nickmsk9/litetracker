<?php
/*
===================================================================
LiteTracker Source
-------------------------------------------------------------------
Назначение: JSON API экспериментального поиска метаданных
===================================================================
*/

api_require_post();
api_require_login();
api_require_csrf('metadata_search');
api_rate_limit('metadata_search', 10, 60);

$title = trim(lt_post_string('title'));
$year = trim(lt_post_string('year'));
$category = trim(lt_post_string('category'));
$type = trim(lt_post_string('type'));

if ($title === '') {
	api_json_error('Введите название релиза.', 400);
}

if ($year !== '' && !preg_match('~^\d{4}$~', $year)) {
	$year = '';
}

try {
	$items = lt_metadata_search(array(
		'title' => $title,
		'year' => $year,
		'category' => $category,
		'type' => $type,
	));
} catch (InvalidArgumentException $e) {
	api_json_error($e->getMessage(), 400);
} catch (RuntimeException $e) {
	api_json_error($e->getMessage(), 503);
} catch (Throwable $e) {
	api_json_error('Метаданные временно недоступны.', 503);
}

api_json_success(array('items' => $items));
