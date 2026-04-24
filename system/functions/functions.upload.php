<?php
/*
===================================================================
LiteTracker Source
===================================================================
by Nick
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
			'options' => lt_torrent_metadata_type_options_all(),
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

function lt_torrent_type_options_map()
{
	static $map = null;

	if ($map !== null) {
		return $map;
	}

	$map = array(
		'anime' => array(
			'tv' => 'ТВ',
			'movie' => 'Фильм',
			'ova' => 'OVA',
			'special' => 'Спец',
		),
		'shows' => array(
			'show' => 'ТВ-шоу',
			'reality' => 'Реалити-шоу',
			'concert' => 'Концерт',
			'special' => 'Спецвыпуск',
		),
		'music' => array(
			'album' => 'Альбом',
			'single' => 'Сингл',
			'discography' => 'Дискография',
			'concert' => 'Концерт',
		),
		'movies' => array(
			'movie' => 'Фильм',
			'series' => 'Сериал',
			'cartoon' => 'Мультфильм',
			'documentary' => 'Документальный',
		),
		'games' => array(
			'pc' => 'PC',
			'console' => 'Консольная',
			'mobile' => 'Мобильная',
			'repack' => 'Репак',
		),
		'software' => array(
			'windows' => 'Windows',
			'macos' => 'macOS',
			'linux' => 'Linux',
			'android' => 'Android',
		),
	);

	return $map;
}

function lt_torrent_metadata_type_options_all()
{
	$map = lt_torrent_type_options_map();
	$result = array();

	foreach ($map as $options) {
		foreach ($options as $value => $label) {
			if (!isset($result[$value])) {
				$result[$value] = $label;
			}
		}
	}

	return $result;
}

function lt_torrent_metadata_type_options_for_category($categoryNameOrKey = '')
{
	$key = trim((string) $categoryNameOrKey);
	$map = lt_torrent_type_options_map();

	if ($key === '' || empty($map[$key])) {
		$key = lt_torrent_description_template_key($categoryNameOrKey);
	}

	return (!empty($map[$key]) ? $map[$key] : array());
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

function lt_torrent_template_example_values($categoryNameOrKey = 'movies')
{
	$default = array(
		'Название' => 'Alan Wake 2',
		'Год выхода' => '2024',
		'Год выпуска' => '2024',
		'Продолжительность' => '1 ч 52 мин',
		'Количество эпизодов' => '12 из 12',
		'Режиссер' => 'Дени Вильнев',
		'В ролях' => 'Райан Гослинг, Эмили Блант, Аарон Тейлор-Джонсон',
		'Описание' => 'Коротко опишите сюжет, особенности релиза и чем он может заинтересовать пользователя. Достаточно 2-4 нормальных предложений.',
		'Формат' => 'WEB-DL',
		'Качество' => '1080p',
		'Видео' => 'H.264, 1920x1080, ~8000 Кбит/с',
		'Ведущий' => 'Иван Ургант',
		'Участники' => 'Гости выпуска, команды и приглашенные артисты',
		'Исполнитель' => 'Imagine Dragons',
		'Альбом' => 'Loom',
		'Треклист' => "01. Wake Up\n02. Nice to Meet You\n03. Eyes Closed",
		'Разработчик' => 'Remedy Entertainment',
		'Издатель' => 'Epic Games Publishing',
		'Таблетка' => 'вшита (RUNE)',
		'Системные требования' => "OS: Windows 10 64-bit\nCPU: Intel Core i5\nRAM: 16 GB\nGPU: GeForce RTX 2060",
		'Название программы' => 'Adobe Photoshop',
		'Версия программы' => '25.7.0',
		'Дата выпуска' => '15.03.2024',
		'Разрядность' => '64-bit',
	);

	$overrides = array(
		'movies' => array(
			'Описание' => 'Бывший детектив берется за последнее дело и выходит на след заговора. Напишите кратко о сюжете, атмосфере и особенностях именно этого релиза.',
		),
		'shows' => array(
			'Описание' => 'Опишите формат шоу, тему выпуска и чем эта раздача отличается: перевод, качество, состав участников или полный сезон.',
		),
		'anime' => array(
			'Описание' => 'Коротко расскажите о завязке, жанровом настроении и особенностях релиза: сезон, спецвыпуск, озвучка или субтитры.',
		),
		'music' => array(
			'Продолжительность' => '42 мин',
			'Формат' => 'FLAC',
			'Качество' => 'Lossless',
			'Описание' => 'Укажите стиль, общее настроение релиза, интересные треки или особенности издания: deluxe, live, remaster.',
		),
		'games' => array(
			'Описание' => 'Коротко объясните жанр, суть игры и что входит в релиз: DLC, бонусы, локализация или особенности сборки.',
			'Системные требования' => "OS: Windows 10 64-bit\nCPU: Intel Core i5-8600\nRAM: 16 GB\nGPU: GeForce RTX 2060",
		),
		'software' => array(
			'Описание' => 'Напишите, для чего нужна программа, кому она пригодится и что нового или полезного есть в этой версии.',
			'Системные требования' => "OS: Windows 10/11\nCPU: 2 GHz\nRAM: 4 GB\nDisk: 2 GB",
		),
	);

	$key = lt_torrent_description_template_key($categoryNameOrKey);

	return array_merge($default, (array) ($overrides[$key] ?? array()));
}

function lt_torrent_template_example_value($categoryNameOrKey = 'movies', $label = '')
{
	$examples = lt_torrent_template_example_values($categoryNameOrKey);
	$label = trim((string) $label);

	return ($label !== '' && isset($examples[$label]) ? (string) $examples[$label] : '');
}

function lt_torrent_template_example_map($categoryNameOrKey = 'movies')
{
	$template = lt_torrent_description_template($categoryNameOrKey);
	$items = (array) ($template['items'] ?? array());
	$result = array();

	foreach ($items as $item) {
		$type = trim((string) ($item['type'] ?? 'field'));
		$label = trim((string) ($item['label'] ?? ''));
		$auto = trim((string) ($item['auto'] ?? ''));

		if ($label === '' || $type === 'section' || $auto !== '') {
			continue;
		}

		$example = lt_torrent_template_example_value($categoryNameOrKey, $label);
		if ($example !== '') {
			$result[$label] = $example;
		}
	}

	return $result;
}

function lt_torrent_template_example_lines($categoryNameOrKey = 'movies', $limit = 5)
{
	$template = lt_torrent_description_template($categoryNameOrKey);
	$items = (array) ($template['items'] ?? array());
	$examples = lt_torrent_template_example_map($categoryNameOrKey);
	$limit = max(1, (int) $limit);
	$result = array();

	foreach ($items as $item) {
		$type = trim((string) ($item['type'] ?? 'field'));
		$label = trim((string) ($item['label'] ?? ''));
		$auto = trim((string) ($item['auto'] ?? ''));

		if ($label === '' || $type === 'section' || $auto !== '' || empty($examples[$label])) {
			continue;
		}

		$value = preg_replace('/\s*\n\s*/u', ' / ', (string) $examples[$label]);
		if (function_exists('mb_strlen') && function_exists('mb_substr')) {
			if (mb_strlen($value, 'UTF-8') > 110) {
				$value = rtrim(mb_substr($value, 0, 107, 'UTF-8')).'...';
			}
		} elseif (strlen($value) > 110) {
			$value = rtrim(substr($value, 0, 107)).'...';
		}

		$result[] = $label.': '.$value;
		if (count($result) >= $limit) {
			break;
		}
	}

	return $result;
}

function lt_torrent_form_help_text($key = '')
{
	$texts = array(
		'release_name' => 'Пример: Фуриоса / Furiosa (2024) WEB-DL 1080p | D | HDRip',
		'torrent_file' => 'Загрузите исходный .torrent файл без ZIP или RAR-архива.',
		'cover' => 'Лучше использовать один постер без коллажей, рамок и лишнего текста.',
		'screens' => 'Покажите 1-4 обычных кадра из релиза: без меню, черных рамок и склеек.',
		'tags' => 'Пример: боевик, 1080p, netflix, дубляж',
		'description' => 'Заполняйте строки шаблона по смыслу, а в пункте "Описание" пишите обычный человеческий текст.',
		'structured_description' => 'Заполняйте только ручные поля. Тип, жанр, аудио, субтитры и страна подставляются из отмеченных вариантов автоматически.',
	);

	$key = trim((string) $key);

	return ($key !== '' && isset($texts[$key]) ? (string) $texts[$key] : '');
}

function lt_torrent_lower($value)
{
	$value = trim((string) $value);

	if ($value === '') {
		return '';
	}

	return (function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value));
}

function lt_torrent_label_key($label)
{
	$label = strip_tags((string) $label);
	$label = str_replace(':', '', $label);
	$label = preg_replace('/\s+/u', ' ', trim($label));

	return lt_torrent_lower($label);
}

function lt_torrent_info_heading($categoryName)
{
	$name = lt_torrent_lower($categoryName);
	if ($name === '') {
		return 'Информация о релизе';
	}

	$map = array(
		'аниме' => 'Информация об аниме',
		'фильмы' => 'Информация о фильме',
		'телешоу' => 'Информация о телешоу',
		'музыка' => 'Информация о релизе',
		'игры' => 'Информация об игре',
		'программы' => 'Информация о программе',
	);

	return (!empty($map[$name]) ? $map[$name] : 'Информация о релизе');
}

function lt_torrent_format_date_label($date)
{
	$timestamp = strtotime((string) $date);
	if (!$timestamp) {
		return trim((string) convent_date((string) $date));
	}

	static $months = array(
		1 => 'января',
		2 => 'февраля',
		3 => 'марта',
		4 => 'апреля',
		5 => 'мая',
		6 => 'июня',
		7 => 'июля',
		8 => 'августа',
		9 => 'сентября',
		10 => 'октября',
		11 => 'ноября',
		12 => 'декабря',
	);

	return date('j', $timestamp).' '.$months[(int) date('n', $timestamp)].' в '.date('H:i', $timestamp);
}

function lt_torrent_render_text_html($text)
{
	$html = trim((string) format_comment((string) $text));
	$html = preg_replace('~^(?:<br\s*/?>\s*)+|(?:\s*<br\s*/?>)+$~i', '', $html);

	return $html;
}

function lt_torrent_truncate_plain_text($text, $length = 520)
{
	$text = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $text)));
	if ($text === '') {
		return '';
	}

	if (function_exists('mb_strlen') && function_exists('mb_substr')) {
		if (mb_strlen($text, 'UTF-8') > $length) {
			return rtrim(mb_substr($text, 0, $length, 'UTF-8')).'...';
		}

		return $text;
	}

	if (strlen($text) > $length) {
		return rtrim(substr($text, 0, $length)).'...';
	}

	return $text;
}

function lt_torrent_parse_description($text)
{
	$text = (string) $text;
	$lines = preg_split('/\r\n|\r|\n/', $text);
	$sections = array(
		array(
			'label' => '',
			'items' => array(),
		),
	);
	$intro = array();
	$currentSection = 0;
	$currentItem = -1;

	foreach ($lines as $line) {
		$line = trim((string) $line);
		if ($line === '') {
			if ($currentItem >= 0) {
				$currentValue = $sections[$currentSection]['items'][$currentItem]['value'];
				if ($currentValue !== '' && substr($currentValue, -1) !== "\n") {
					$sections[$currentSection]['items'][$currentItem]['value'] .= "\n";
				}
			}
			continue;
		}

		if (preg_match('/^\[u\](.+?)\[\/u\]$/iu', $line, $match)) {
			$sections[] = array(
				'label' => trim((string) $match[1]),
				'items' => array(),
			);
			$currentSection = count($sections) - 1;
			$currentItem = -1;
			continue;
		}

		if (preg_match('/^\[b\](.+?)\[\/b\]\s*(.*)$/iu', $line, $match)) {
			$label = trim((string) $match[1]);
			if (substr($label, -1) === ':') {
				$label = rtrim(substr($label, 0, -1));
			}

			$sections[$currentSection]['items'][] = array(
				'label' => $label,
				'value' => trim((string) $match[2]),
			);
			$currentItem = count($sections[$currentSection]['items']) - 1;
			continue;
		}

		if ($currentItem >= 0) {
			$currentValue = $sections[$currentSection]['items'][$currentItem]['value'];
			$sections[$currentSection]['items'][$currentItem]['value'] = trim($currentValue."\n".$line);
			continue;
		}

		$intro[] = $line;
	}

	return array(
		'intro' => $intro,
		'sections' => $sections,
	);
}

function lt_torrent_has_item($sections, $labelKeys)
{
	$labelKeys = (array) $labelKeys;

	foreach ((array) $sections as $section) {
		foreach ((array) ($section['items'] ?? array()) as $item) {
			if (in_array(lt_torrent_label_key($item['label'] ?? ''), $labelKeys, true)) {
				return true;
			}
		}
	}

	return false;
}

function lt_torrent_extract_item(&$sections, $labelKeys)
{
	$labelKeys = (array) $labelKeys;

	foreach ($sections as $sectionIndex => $section) {
		foreach ((array) ($section['items'] ?? array()) as $itemIndex => $item) {
			if (!in_array(lt_torrent_label_key($item['label'] ?? ''), $labelKeys, true)) {
				continue;
			}

			$value = trim((string) ($item['value'] ?? ''));
			unset($sections[$sectionIndex]['items'][$itemIndex]);
			$sections[$sectionIndex]['items'] = array_values($sections[$sectionIndex]['items']);

			return $value;
		}
	}

	return '';
}

function lt_torrent_append_item(&$sections, $sectionLabel, $label, $value)
{
	$value = trim((string) $value);
	if ($value === '') {
		return;
	}

	$sectionLabel = trim((string) $sectionLabel);
	$sectionIndex = null;

	foreach ($sections as $index => $section) {
		if (trim((string) ($section['label'] ?? '')) === $sectionLabel) {
			$sectionIndex = $index;
			break;
		}
	}

	if ($sectionIndex === null) {
		$sections[] = array(
			'label' => $sectionLabel,
			'items' => array(),
		);
		$sectionIndex = count($sections) - 1;
	}

	$sections[$sectionIndex]['items'][] = array(
		'label' => trim((string) $label),
		'value' => $value,
	);
}

function lt_torrent_cover_path($torrent, $category = array())
{
	$image = trim((string) ($torrent['image'] ?? ''));
	if ($image !== '' && is_file('public/downloads/images/'.$image)) {
		return 'public/downloads/images/'.$image;
	}

	$categoryImage = trim((string) ($category['image'] ?? ''));
	if ($categoryImage !== '' && is_file('public/images/categories/'.$categoryImage)) {
		return 'public/images/categories/'.$categoryImage;
	}

	return 'public/images/default_avatar.gif';
}

function lt_torrent_prepare_browse_sections($torrent, $categoryName = '')
{
	$parsed = lt_torrent_parse_description((string) ($torrent['descr'] ?? ''));
	$sections = array_values((array) ($parsed['sections'] ?? array()));

	$mainAutofill = array(
		array('keys' => array('страна'), 'label' => 'Страна', 'value' => lt_torrent_metadata_format('country', $torrent['countries'] ?? '')),
		array('keys' => array('тип', 'тип издания'), 'label' => 'Тип', 'value' => lt_torrent_metadata_format('type', $torrent['content_type'] ?? '')),
		array('keys' => array('жанр'), 'label' => 'Жанр', 'value' => lt_torrent_metadata_format('genre', $torrent['genres'] ?? '')),
	);

	foreach ($mainAutofill as $item) {
		if ($item['value'] !== '' && !lt_torrent_has_item($sections, $item['keys'])) {
			lt_torrent_append_item($sections, '', $item['label'], $item['value']);
		}
	}

	$extraAutofill = array(
		array('keys' => array('субтитры'), 'label' => 'Субтитры', 'value' => lt_torrent_metadata_format('subtitles', $torrent['subtitles'] ?? '')),
		array('keys' => array('язык', 'аудио', 'язык интерфейса', 'язык озвучки'), 'label' => 'Язык', 'value' => lt_torrent_metadata_format('language', $torrent['languages'] ?? '')),
		array('keys' => array('инфо'), 'label' => 'Инфо', 'value' => lt_torrent_metadata_format('info', $torrent['meta_info'] ?? '')),
	);

	foreach ($extraAutofill as $item) {
		if ($item['value'] !== '' && !lt_torrent_has_item($sections, $item['keys'])) {
			lt_torrent_append_item($sections, 'Дополнительно', $item['label'], $item['value']);
		}
	}

	$descriptionText = lt_torrent_extract_item($sections, array('описание', 'описание релиза', 'содержание', 'сюжет'));
	$infoNote = lt_torrent_extract_item($sections, array('инфо'));
	$updateReason = lt_torrent_extract_item($sections, array('причина'));

	if ($descriptionText === '' && !empty($parsed['intro'])) {
		$descriptionText = implode("\n", $parsed['intro']);
	}

	$mainItems = array();
	$extraSections = array();

	foreach ($sections as $section) {
		$items = array_values(array_filter((array) ($section['items'] ?? array()), function ($item) {
			return trim((string) ($item['value'] ?? '')) !== '';
		}));
		if (!$items) {
			continue;
		}

		$sectionLabel = trim((string) ($section['label'] ?? ''));
		if ($sectionLabel === '' && !$mainItems) {
			$mainItems = $items;
			continue;
		}

		if ($sectionLabel === '') {
			$mainItems = array_merge($mainItems, $items);
			continue;
		}

		$extraSections[] = array(
			'label' => $sectionLabel,
			'items' => $items,
		);
	}

	return array(
		'info_title' => lt_torrent_info_heading($categoryName),
		'main_items' => $mainItems,
		'description_text' => $descriptionText,
		'description_excerpt' => lt_torrent_truncate_plain_text($descriptionText, 720),
		'info_note' => $infoNote,
		'extra_sections' => $extraSections,
		'update_reason' => $updateReason,
	);
}

function lt_torrent_prepare_browse_card($torrent, $category = array(), $user = array())
{
	$torrentId = (int) ($torrent['id'] ?? 0);
	$categoryName = trim((string) ($category['name'] ?? 'Без категории'));
	$userId = (int) ($user['id'] ?? 0);
	$userName = trim((string) ($user['name'] ?? 'Неизвестно'));
	$userClass = (int) ($user['class'] ?? 0);
	$updatedAt = trim((string) ($torrent['last_action'] ?? ''));

	if ($updatedAt === '' || $updatedAt === '0000-00-00 00:00:00') {
		$updatedAt = trim((string) ($torrent['added'] ?? ''));
	}

	return array_merge(
		array(
			'id' => $torrentId,
			'name' => (string) ($torrent['name'] ?? ''),
			'details_href' => 'details.php?id='.$torrentId,
			'category_name' => $categoryName,
			'category_badge' => lt_torrent_lower($categoryName),
			'category_href' => 'browse.php?id_category='.(int) ($category['id'] ?? 0),
			'cover' => lt_torrent_cover_path($torrent, $category),
			'seeders' => number_format(max(0, (int) ($torrent['seeders'] ?? 0))),
			'leechers' => number_format(max(0, (int) ($torrent['leechers'] ?? 0))),
			'is_multitracker' => !empty($torrent['multi']),
			'external_tracker_count' => max(0, (int) ($torrent['external_tracker_count'] ?? 0)),
			'size' => mksize((float) ($torrent['size'] ?? 0)),
			'user_href' => profile_href($userId),
			'user_html' => get_user_color($userClass, htmlspecialchars($userName, ENT_QUOTES, 'UTF-8')),
			'updated_label' => lt_torrent_format_date_label($updatedAt),
			'is_banned' => !empty($torrent['banned']),
		),
		lt_torrent_prepare_browse_sections($torrent, $categoryName)
	);
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
