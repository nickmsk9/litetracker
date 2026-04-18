<?php
/*
===================================================================
LiteTracker Source
===================================================================
by jenaDI
-------------------------------------------------------------------
Назначение: Функции загрузки
===================================================================
*/

function lt_torrent_metadata_schema()
{
	static $schema = null;

	if ($schema !== null) {
		return $schema;
	}

	$schema = array(
		'type' => array(
			'column' => 'content_type',
			'label' => 'Тип',
			'input' => 'radio',
			'columns' => 4,
			'options' => array(
				'movie' => 'Фильм',
				'series' => 'Сериал',
				'cartoon' => 'Мультфильм',
				'show' => 'ТВ-шоу',
			),
		),
		'subtitles' => array(
			'column' => 'subtitles',
			'label' => 'Субтитры',
			'input' => 'checkbox',
			'columns' => 4,
			'options' => array(
				'russian' => 'русские',
				'english' => 'английские',
				'spanish' => 'испанские',
				'italian' => 'итальянские',
				'chinese' => 'китайские',
				'korean' => 'корейские',
				'german' => 'немецкие',
				'portuguese' => 'португальские',
				'thai' => 'тайские',
				'french' => 'французские',
				'japanese' => 'японские',
				'ukrainian' => 'украинские',
			),
		),
		'language' => array(
			'column' => 'languages',
			'label' => 'Язык',
			'input' => 'checkbox',
			'columns' => 4,
			'options' => array(
				'russian' => 'русский',
				'english' => 'английский',
				'spanish' => 'испанский',
				'italian' => 'итальянский',
				'chinese' => 'китайский',
				'korean' => 'корейский',
				'german' => 'немецкий',
				'portuguese' => 'португальский',
				'thai' => 'тайский',
				'french' => 'французский',
				'japanese' => 'японский',
				'ukrainian' => 'украинский',
			),
		),
		'genre' => array(
			'column' => 'genres',
			'label' => 'Жанр',
			'input' => 'checkbox',
			'columns' => 4,
			'options' => array(
				'action' => 'боевик',
				'detective' => 'детектив',
				'drama' => 'драма',
				'comedy' => 'комедия',
				'melodrama' => 'мелодрама',
				'thriller' => 'триллер',
				'horror' => 'ужасы',
				'fantasy' => 'фэнтези',
				'science_fiction' => 'фантастика',
				'adventure' => 'приключения',
				'documentary' => 'документальный',
				'crime' => 'криминал',
				'family' => 'семейный',
				'war' => 'военный',
				'history' => 'исторический',
				'music' => 'музыка',
				'sport' => 'спорт',
				'animation' => 'анимация',
				'mystic' => 'мистика',
				'biography' => 'биография',
			),
		),
		'info' => array(
			'column' => 'meta_info',
			'label' => 'Инфо',
			'input' => 'checkbox',
			'columns' => 3,
			'options' => array(
				'hevc' => 'HEVC',
				'licensed' => 'Лицензия',
				'uncensored' => 'Без цензуры',
				'has_torrent' => 'Есть торренты',
			),
		),
		'country' => array(
			'column' => 'countries',
			'label' => 'Страна',
			'input' => 'checkbox',
			'columns' => 4,
			'options' => array(
				'russia' => 'Россия',
				'usa' => 'США',
				'uk' => 'Великобритания',
				'germany' => 'Германия',
				'france' => 'Франция',
				'japan' => 'Япония',
				'south_korea' => 'Южная Корея',
				'china' => 'Китай',
				'india' => 'Индия',
				'turkey' => 'Турция',
				'ukraine' => 'Украина',
				'canada' => 'Канада',
			),
		),
	);

	return $schema;
}

function lt_torrent_metadata_group($group)
{
	$schema = lt_torrent_metadata_schema();

	return (!empty($schema[$group]) ? $schema[$group] : array());
}

function lt_torrent_metadata_normalize_values($group, $values)
{
	$definition = lt_torrent_metadata_group($group);
	$options = (!empty($definition['options']) ? $definition['options'] : array());
	$result = array();

	if (!is_array($values)) {
		$values = array($values);
	}

	foreach ($values as $value) {
		$value = trim((string) $value);
		if ($value === '' || !isset($options[$value])) {
			continue;
		}

		$result[$value] = $value;
	}

	return array_values($result);
}

function lt_torrent_metadata_csv($group, $values)
{
	$values = lt_torrent_metadata_normalize_values($group, $values);

	return implode(',', $values);
}

function lt_torrent_metadata_parse($group, $value)
{
	if ($value === null || $value === '') {
		return array();
	}

	return lt_torrent_metadata_normalize_values($group, explode(',', (string) $value));
}

function lt_torrent_metadata_format($group, $value, $separator = ', ')
{
	$definition = lt_torrent_metadata_group($group);
	$options = (!empty($definition['options']) ? $definition['options'] : array());
	$values = lt_torrent_metadata_parse($group, $value);
	$result = array();

	foreach ($values as $item) {
		if (!isset($options[$item])) {
			continue;
		}

		$result[] = $options[$item];
	}

	return implode($separator, $result);
}

function lt_torrent_metadata_option_label($group, $value)
{
	$definition = lt_torrent_metadata_group($group);
	$options = (!empty($definition['options']) ? $definition['options'] : array());
	$value = trim((string) $value);

	return (!empty($options[$value]) ? (string) $options[$value] : '');
}

function lt_torrent_description_templates()
{
	static $templates = null;

	if ($templates !== null) {
		return $templates;
	}

	$templates = array(
		'movies' => array(
			'label' => 'Фильмы',
			'items' => array(
				array('type' => 'field', 'label' => 'Тип', 'auto' => 'type'),
				array('type' => 'field', 'label' => 'Жанр', 'auto' => 'genre'),
				array('type' => 'field', 'label' => 'Год выхода'),
				array('type' => 'field', 'label' => 'Продолжительность'),
				array('type' => 'field', 'label' => 'Режиссер'),
				array('type' => 'field', 'label' => 'В ролях'),
				array('type' => 'field', 'label' => 'Описание'),
				array('type' => 'section', 'label' => 'Дополнительно'),
				array('type' => 'field', 'label' => 'Формат'),
				array('type' => 'field', 'label' => 'Качество'),
				array('type' => 'field', 'label' => 'Видео'),
				array('type' => 'field', 'label' => 'Аудио', 'auto' => 'language'),
				array('type' => 'field', 'label' => 'Субтитры', 'auto' => 'subtitles'),
				array('type' => 'field', 'label' => 'Страна', 'auto' => 'country'),
			),
		),
		'shows' => array(
			'label' => 'Телешоу',
			'items' => array(
				array('type' => 'field', 'label' => 'Тип', 'auto' => 'type'),
				array('type' => 'field', 'label' => 'Жанр', 'auto' => 'genre'),
				array('type' => 'field', 'label' => 'Год выхода'),
				array('type' => 'field', 'label' => 'Продолжительность'),
				array('type' => 'field', 'label' => 'Ведущий'),
				array('type' => 'field', 'label' => 'Участники'),
				array('type' => 'field', 'label' => 'Описание'),
				array('type' => 'section', 'label' => 'Дополнительно'),
				array('type' => 'field', 'label' => 'Формат'),
				array('type' => 'field', 'label' => 'Качество'),
				array('type' => 'field', 'label' => 'Видео'),
				array('type' => 'field', 'label' => 'Аудио', 'auto' => 'language'),
				array('type' => 'field', 'label' => 'Субтитры', 'auto' => 'subtitles'),
				array('type' => 'field', 'label' => 'Страна', 'auto' => 'country'),
			),
		),
		'anime' => array(
			'label' => 'Аниме',
			'items' => array(
				array('type' => 'field', 'label' => 'Тип', 'auto' => 'type'),
				array('type' => 'field', 'label' => 'Жанр', 'auto' => 'genre'),
				array('type' => 'field', 'label' => 'Год выхода'),
				array('type' => 'field', 'label' => 'Количество эпизодов'),
				array('type' => 'field', 'label' => 'Продолжительность'),
				array('type' => 'field', 'label' => 'Режиссер'),
				array('type' => 'field', 'label' => 'Описание'),
				array('type' => 'section', 'label' => 'Дополнительно'),
				array('type' => 'field', 'label' => 'Формат'),
				array('type' => 'field', 'label' => 'Качество'),
				array('type' => 'field', 'label' => 'Видео'),
				array('type' => 'field', 'label' => 'Аудио', 'auto' => 'language'),
				array('type' => 'field', 'label' => 'Субтитры', 'auto' => 'subtitles'),
				array('type' => 'field', 'label' => 'Страна', 'auto' => 'country'),
			),
		),
		'music' => array(
			'label' => 'Музыка',
			'items' => array(
				array('type' => 'field', 'label' => 'Исполнитель'),
				array('type' => 'field', 'label' => 'Альбом'),
				array('type' => 'field', 'label' => 'Год выпуска'),
				array('type' => 'field', 'label' => 'Жанр', 'auto' => 'genre'),
				array('type' => 'field', 'label' => 'Продолжительность'),
				array('type' => 'field', 'label' => 'Описание'),
				array('type' => 'section', 'label' => 'Дополнительно'),
				array('type' => 'field', 'label' => 'Формат'),
				array('type' => 'field', 'label' => 'Качество'),
				array('type' => 'field', 'label' => 'Аудио', 'auto' => 'language'),
				array('type' => 'field', 'label' => 'Треклист'),
				array('type' => 'field', 'label' => 'Страна', 'auto' => 'country'),
			),
		),
		'games' => array(
			'label' => 'Игры',
			'items' => array(
				array('type' => 'field', 'label' => 'Название'),
				array('type' => 'field', 'label' => 'Год выпуска'),
				array('type' => 'field', 'label' => 'Жанр', 'auto' => 'genre'),
				array('type' => 'field', 'label' => 'Разработчик'),
				array('type' => 'field', 'label' => 'Издатель'),
				array('type' => 'field', 'label' => 'Тип издания', 'auto' => 'type'),
				array('type' => 'field', 'label' => 'Описание'),
				array('type' => 'section', 'label' => 'Дополнительно'),
				array('type' => 'field', 'label' => 'Язык интерфейса', 'auto' => 'language'),
				array('type' => 'field', 'label' => 'Язык озвучки', 'auto' => 'language'),
				array('type' => 'field', 'label' => 'Таблетка'),
				array('type' => 'field', 'label' => 'Системные требования'),
			),
		),
		'software' => array(
			'label' => 'Программы',
			'items' => array(
				array('type' => 'field', 'label' => 'Название программы'),
				array('type' => 'field', 'label' => 'Версия программы'),
				array('type' => 'field', 'label' => 'Дата выпуска'),
				array('type' => 'field', 'label' => 'Разработчик'),
				array('type' => 'field', 'label' => 'Описание'),
				array('type' => 'section', 'label' => 'Дополнительно'),
				array('type' => 'field', 'label' => 'Язык интерфейса', 'auto' => 'language'),
				array('type' => 'field', 'label' => 'Разрядность'),
				array('type' => 'field', 'label' => 'Таблетка'),
				array('type' => 'field', 'label' => 'Системные требования'),
			),
		),
	);

	return $templates;
}

function lt_torrent_description_template_key($categoryName)
{
	$normalized = trim((string) $categoryName);
	if ($normalized === '') {
		return 'movies';
	}

	if (function_exists('mb_strtolower')) {
		$normalized = mb_strtolower($normalized, 'UTF-8');
	} else {
		$normalized = strtolower($normalized);
	}

	$normalized = preg_replace('~\s+~u', '', $normalized);

	$map = array(
		'фильмы' => 'movies',
		'телешоу' => 'shows',
		'аниме' => 'anime',
		'музыка' => 'music',
		'игры' => 'games',
		'программы' => 'software',
	);

	return (!empty($map[$normalized]) ? $map[$normalized] : 'movies');
}

function lt_torrent_description_template($categoryNameOrKey = 'movies')
{
	$templates = lt_torrent_description_templates();
	$key = trim((string) $categoryNameOrKey);

	if ($key === '' || empty($templates[$key])) {
		$key = lt_torrent_description_template_key($categoryNameOrKey);
	}

	return (!empty($templates[$key]) ? $templates[$key] : $templates['movies']);
}

function lt_torrent_description_build($categoryNameOrKey = 'movies', $values = array())
{
	$template = lt_torrent_description_template($categoryNameOrKey);
	$items = (!empty($template['items']) ? $template['items'] : array());
	$values = (is_array($values) ? $values : array());
	$lines = array();

	foreach ($items as $item) {
		$type = trim((string) ($item['type'] ?? 'field'));
		$label = trim((string) ($item['label'] ?? ''));

		if ($label === '') {
			continue;
		}

		if ($type === 'section') {
			if ($lines) {
				$lines[] = '';
			}

			$lines[] = '[u]'.$label.'[/u]';
			continue;
		}

		$value = trim((string) ($values[$label] ?? ''));
		$lines[] = '[b]'.$label.':[/b]'.($value !== '' ? ' '.$value : '');
	}

	return implode("\n", $lines);
}

function lt_torrent_tags_from_string($value)
{
	$result = array();
	$parts = preg_split('/[,;]+/u', (string) $value);

	foreach ($parts as $part) {
		$part = trim((string) $part);
		if ($part === '') {
			continue;
		}

		if (function_exists('mb_substr')) {
			$part = mb_substr($part, 0, 30, 'UTF-8');
		} else {
			$part = substr($part, 0, 30);
		}

		$key = (function_exists('mb_strtolower') ? mb_strtolower($part, 'UTF-8') : strtolower($part));
		$result[$key] = $part;
	}

	return array_values($result);
}

function lt_torrent_tags_to_string($value)
{
	return implode(',', lt_torrent_tags_from_string($value));
}

function lt_torrent_default_description($categoryNameOrKey = 'movies', $values = array())
{
	return lt_torrent_description_build($categoryNameOrKey, $values);
}

function upload_text_category($type)
{
	return lt_torrent_default_description($type);
}

?>
