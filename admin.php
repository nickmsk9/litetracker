<?php
/*
===================================================================
LiteTracker Source
===================================================================
by Nick
-------------------------------------------------------------------
Назначение: Единая админ-панель
===================================================================
*/

require 'system/init.php';

is_login();

$GLOBALS['LITETRACKER_HIDE_TOP_BLOCKS'] = true;
$GLOBALS['LITETRACKER_HIDE_BOTTOM_BLOCKS'] = true;
$GLOBALS['LITETRACKER_HIDE_STANDARD_SIDEBAR'] = true;

if (!admin_dashboard_can_access($USER, $PRIV)) {
	err('Ошибка', 'У вас нет прав для входа в админку.');
}

function admin_dashboard_build_href($params = array())
{
	$query = array();
	$currentTab = trim((string) ($_GET['tab'] ?? 'overview'));

	if ($currentTab !== '') {
		$query['tab'] = $currentTab;
	}

	foreach ((array) $params as $key => $value) {
		if ($value === null || $value === '') {
			unset($query[$key]);
			continue;
		}

		$query[$key] = $value;
	}

	return 'admin.php'.($query ? '?'.http_build_query($query) : '');
}

function admin_dashboard_redirect($tab, $notice)
{
	header('Location: '.admin_dashboard_build_href(array(
		'tab' => $tab,
		'notice' => $notice,
	)));
	die();
}

function admin_dashboard_stat_value($sql, $field = 'c')
{
	global $db;

	$row = $db->super_query($sql);

	return (int) ($row[$field] ?? 0);
}

function admin_dashboard_role_map($user, $priv)
{
	$superadmin = admin_dashboard_is_superadmin($user, $priv);
	$content = ($superadmin || !empty($priv['edit_release']) || !empty($priv['cats']) || !empty($priv['news_add']) || !empty($priv['edit_news']) || !empty($priv['faq_moderate']));
	$users = ($superadmin || !empty($priv['user_add']) || !empty($priv['setting_user']) || !empty($priv['messages']));
	$moderation = ($superadmin || !empty($priv['comments_edit']) || !empty($priv['comments_delete']) || !empty($priv['ip_util']) || !empty($priv['multitracker_accounts']) || user_wall_reports_can_moderate());
	$monitoring = ($superadmin || !empty($priv['sessions_view']) || !empty($priv['search_query']));

	return array(
		'overview' => true,
		'content' => $content,
		'users' => $users,
		'moderation' => $moderation,
		'monitoring' => $monitoring,
		'site-settings' => $superadmin,
		'tracker-settings' => $superadmin,
		'feature-settings' => $superadmin,
		'superadmin' => $superadmin,
	);
}

function admin_dashboard_normalize_tab($tab, $roles)
{
	$tab = trim((string) $tab);
	$allowedTabs = array();

	foreach ((array) $roles as $roleKey => $allowed) {
		if ($roleKey === 'superadmin') {
			continue;
		}

		if ($allowed) {
			$allowedTabs[] = $roleKey;
		}
	}

	if (!$allowedTabs) {
		return 'overview';
	}

	return (in_array($tab, $allowedTabs, true) ? $tab : $allowedTabs[0]);
}

function admin_dashboard_settings_schema()
{
	return array(
		'site-settings' => array(
			'title' => 'Настройки сайта',
			'description' => 'Главные публичные переключатели движка. Эти параметры влияют на вход, регистрацию и базовое имя проекта.',
			'fields' => array(
				array('key' => 'sitename', 'label' => 'Название сайта', 'type' => 'text', 'required' => true, 'description' => 'Показывается в заголовках и системных местах. Пример: LiteTracker.'),
				array('key' => 'siteonline', 'label' => 'Сайт открыт', 'type' => 'checkbox', 'description' => 'Если выключить, обычные пользователи не смогут пользоваться сайтом во время работ.'),
				array('key' => 'registeronline', 'label' => 'Регистрация открыта', 'type' => 'checkbox', 'description' => 'Разрешает создание новых аккаунтов через публичную форму регистрации.'),
				array('key' => 'gzip', 'label' => 'Gzip-сжатие', 'type' => 'checkbox', 'description' => 'Сжимает HTML-ответы, если сервер и браузер это поддерживают.'),
				array('key' => 'begin_money', 'label' => 'Стартовый баланс', 'type' => 'text', 'description' => 'Сколько бонусных единиц получает новый пользователь после регистрации.'),
				array('key' => 'project_help_text', 'label' => 'Текст блока помощи проекту', 'type' => 'text', 'description' => 'Короткое описание цели сбора. Пример: “Оплата аренды сервера”.'),
				array('key' => 'project_help_button_label', 'label' => 'Кнопка помощи проекту', 'type' => 'text', 'description' => 'Текст кнопки в блоке помощи. Пример: “Помочь проекту”.'),
				array('key' => 'project_help_button_href', 'label' => 'Ссылка кнопки помощи', 'type' => 'text', 'description' => 'URL платежной страницы или темы форума. Оставьте пустым, если кнопка не нужна.'),
			),
		),
		'tracker-settings' => array(
			'title' => 'Настройки трекера',
			'description' => 'Announce, retracker, cron и тайминги torrent-части. Меняйте осторожно: эти значения напрямую влияют на скачивание и обновление пиров.',
			'fields' => array(
				array('key' => 'announce_url', 'label' => 'Основной announce URL', 'type' => 'text', 'required' => true, 'description' => 'Попадает в скачиваемые torrent-файлы. Пример: https://site.ru/announce.php.'),
				array('key' => 'local_retracker_url', 'label' => 'Локальный retracker URL', 'type' => 'text', 'required' => true, 'description' => 'Дополнительный локальный retracker для клиентов. Обычно совпадает с доменом сайта.'),
				array('key' => 'announce_interval', 'label' => 'Интервал announce, секунд', 'type' => 'int', 'required' => true, 'min' => 60, 'description' => 'Как часто клиент должен сообщать трекеру о себе. Нормально: 1800 секунд.'),
				array('key' => 'remote_tracker_timeout', 'label' => 'Таймаут remote tracker, секунд', 'type' => 'int', 'required' => true, 'min' => 1, 'description' => 'Сколько ждать ответ внешнего трекера в мультитрекерных раздачах. Меньше значение быстрее открывает details.php.'),
				array('key' => 'releases_news', 'label' => 'Срок метки Новинка, дней', 'type' => 'int', 'required' => true, 'min' => 1, 'description' => 'Сколько дней релиз считается новым в списках.'),
				array('key' => 'crontab', 'label' => 'Внешний cron', 'type' => 'checkbox', 'description' => 'Если включено, автоочистку и обновление пиров должен запускать внешний планировщик.'),
				array('key' => 'cron_token', 'label' => 'Токен cron', 'type' => 'text', 'description' => 'Секрет для вызова служебных cron URL. Оставьте пустым только для локальной разработки.'),
				array('key' => 'announce_connectivity_probe', 'label' => 'Проверка доступности announce', 'type' => 'checkbox', 'description' => 'Включает служебную проверку доступности announce-адреса. Полезно после смены домена.'),
			),
		),
		'feature-settings' => array(
			'title' => 'Системные функции',
			'description' => 'Переключатели поиска и защитных механизмов, включая локальную CAPTCHA для форм сайта.',
			'fields' => array(
				array('key' => 'search_forum', 'label' => 'Форумный вид поиска', 'type' => 'checkbox', 'description' => 'Показывает список категорий на странице browse.php?act=all.'),
				array('key' => 'search_image', 'label' => 'Поиск по изображениям', 'type' => 'checkbox', 'description' => 'Включает отдельный модуль поиска по изображениям.'),
				array('key' => 'search_image_lenght', 'label' => 'Минимум символов для изображений', 'type' => 'int', 'min' => 0, 'description' => 'С какого размера запроса показывать результаты по изображениям. 0 значит без ограничения.'),
				array('key' => 'bonus_source', 'label' => 'Источник бонусов', 'type' => 'select', 'options' => array('seeding' => 'Сидирование', 'online' => 'Онлайн на сайте'), 'description' => 'За что начислять бонусы при запуске autoclean. Режим “Онлайн” считает активные сессии за последний интервал cron.'),
				array('key' => 'bonus_price', 'label' => 'Бонусов за час', 'type' => 'int', 'min' => 0, 'description' => 'Сколько бонусов начислять за один час выбранной активности. По умолчанию: 10.'),
				array('key' => 'captcha', 'label' => 'Включить локальную CAPTCHA', 'type' => 'checkbox', 'description' => 'Главный переключатель локальной проверки без внешних сервисов.'),
				array('key' => 'reCaptcha_login', 'label' => 'CAPTCHA на входе', 'type' => 'checkbox', 'description' => 'Показывать проверку на странице авторизации.'),
				array('key' => 'reCaptcha_signup', 'label' => 'CAPTCHA при регистрации', 'type' => 'checkbox', 'description' => 'Показывать проверку при создании нового аккаунта.'),
				array('key' => 'reCaptcha_download', 'label' => 'CAPTCHA при скачивании', 'type' => 'checkbox', 'description' => 'Показывать проверку перед скачиванием torrent-файла.'),
			),
		),
	);
}

function admin_dashboard_settings_field_map($schema)
{
	$result = array();

	foreach ((array) $schema as $tab) {
		foreach ((array) ($tab['fields'] ?? array()) as $field) {
			$result[$field['key']] = $field;
		}
	}

	return $result;
}

function admin_dashboard_php_literal($value, $type)
{
	if ($type === 'checkbox' || $type === 'int') {
		return (string) (int) $value;
	}

	return "'".str_replace(array('\\', "'"), array('\\\\', "\\'"), (string) $value)."'";
}

function admin_dashboard_config_path()
{
	return __DIR__.DIRECTORY_SEPARATOR.'system'.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'config.php';
}

function admin_dashboard_display_path($path)
{
	$root = rtrim(str_replace('\\', '/', __DIR__), '/');
	$normalizedPath = str_replace('\\', '/', (string) $path);

	if ($root !== '' && strpos($normalizedPath, $root.'/') === 0) {
		return substr($normalizedPath, strlen($root) + 1);
	}

	return $normalizedPath;
}

function admin_dashboard_ensure_writable($path)
{
	if (is_writable($path)) {
		return true;
	}

	if (is_file($path)) {
		@chmod($path, 0664);
	}

	return is_writable($path);
}

function admin_dashboard_update_config_values($updates, $fieldMap)
{
	$configPath = admin_dashboard_config_path();
	if (!admin_dashboard_ensure_writable($configPath)) {
		return 'Файл system/config/config.php недоступен для записи. Проверьте права файла или владельца процесса PHP.';
	}

	$content = file_get_contents($configPath);

	if ($content === false) {
		return 'Не удалось прочитать system/config/config.php.';
	}

	foreach ((array) $updates as $key => $value) {
		if (empty($fieldMap[$key])) {
			continue;
		}

		$field = $fieldMap[$key];
		$literal = admin_dashboard_php_literal($value, $field['type']);
		$pattern = '/(^[ \t]*\'' . preg_quote($key, '/') . '\'\s*=>\s*)(.*?)(\s*,\s*(?:(?:\/\/.*)?)$)/m';
		$count = 0;
		$content = preg_replace_callback(
			$pattern,
			function ($matches) use ($literal) {
				return $matches[1].$literal.$matches[3];
			},
			$content,
			1,
			$count
		);

		if ($count !== 1) {
			return 'Не удалось обновить параметр '.$key.' в конфиге.';
		}
	}

	if (file_put_contents($configPath, $content) === false) {
		return 'Не удалось записать изменения в system/config/config.php.';
	}

	return true;
}

function admin_dashboard_flush_cache()
{
	global $config, $memcached;

	$flushed = false;

	if (is_object($memcached)) {
		if (isset($memcached->client) && is_object($memcached->client) && method_exists($memcached->client, 'flush')) {
			$memcached->client->flush();
			$flushed = true;
		} elseif (method_exists($memcached, 'flush')) {
			$memcached->flush();
			$flushed = true;
		}
	}

	$filecacheDir = rtrim((string) ($config['filecache']['dir'] ?? ''), '/');
	if ($filecacheDir !== '' && is_dir($filecacheDir)) {
		$files = glob($filecacheDir.'/*');
		if (is_array($files)) {
			foreach ($files as $file) {
				if (is_file($file)) {
					unlink($file);
				}
			}
		}
		$flushed = true;
	}

	return $flushed;
}

function admin_dashboard_optimize_database()
{
	global $db;

	$tables = array();
	$sql = $db->query('SHOW TABLES');
	while ($row = $db->get_row($sql)) {
		$values = array_values($row);
		if (!empty($values[0])) {
			$tables[] = (string) $values[0];
		}
	}
	$db->free($sql);

	foreach ($tables as $table) {
		$db->query('OPTIMIZE TABLE `'.str_replace('`', '``', $table).'`');
	}

	return count($tables);
}

function admin_dashboard_notice_meta($code)
{
	$messages = array(
		'sessions_cleared' => array('type' => 'success', 'text' => 'Сессии очищены.'),
		'search_queries_cleared' => array('type' => 'success', 'text' => 'Мониторинг поиска очищен.'),
		'cache_flushed' => array('type' => 'success', 'text' => 'Кэш очищен.'),
		'db_optimized' => array('type' => 'success', 'text' => 'Оптимизация БД выполнена.'),
		'setting_toggled' => array('type' => 'success', 'text' => 'Системный переключатель обновлен.'),
		'settings_saved' => array('type' => 'success', 'text' => 'Настройки сохранены.'),
		'no_changes' => array('type' => 'success', 'text' => 'Изменений не было.'),
		'action_denied' => array('type' => 'error', 'text' => 'У вас нет прав на это действие.'),
		'action_failed' => array('type' => 'error', 'text' => 'Операция не выполнена.'),
	);

	return ($messages[$code] ?? null);
}

$roles = admin_dashboard_role_map($USER, $PRIV);
$settingsSchema = admin_dashboard_settings_schema();
$settingsFieldMap = admin_dashboard_settings_field_map($settingsSchema);
$settingsDraft = array();
$activeTab = admin_dashboard_normalize_tab($_GET['tab'] ?? 'overview', $roles);
$flashMessage = admin_dashboard_notice_meta(trim((string) ($_GET['notice'] ?? '')));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$postedTab = admin_dashboard_normalize_tab($_POST['tab'] ?? $activeTab, $roles);
	$activeTab = $postedTab;
	$action = trim((string) ($_POST['admin_action'] ?? ''));

	if (!lt_csrf_validate('admin_dashboard')) {
		$flashMessage = array(
			'type' => 'error',
			'text' => 'Защитный токен устарел. Обновите страницу и повторите действие.',
		);
		$action = '';
	}

	if ($action === 'quick_action') {
		$quickAction = trim((string) ($_POST['quick_action'] ?? ''));

		if ($quickAction === 'clear_sessions') {
			if (empty($PRIV['sessions_clear'])) {
				admin_dashboard_redirect($activeTab, 'action_denied');
			}

			$db->query('DELETE FROM sessions');
			admin_dashboard_redirect($activeTab, 'sessions_cleared');
		}

		if ($quickAction === 'clear_search_queries') {
			if (!$roles['superadmin']) {
				admin_dashboard_redirect($activeTab, 'action_denied');
			}

			$db->query('DELETE FROM search_query');
			admin_dashboard_redirect($activeTab, 'search_queries_cleared');
		}

		if ($quickAction === 'flush_cache') {
			if (!$roles['superadmin']) {
				admin_dashboard_redirect($activeTab, 'action_denied');
			}

			admin_dashboard_redirect($activeTab, (admin_dashboard_flush_cache() ? 'cache_flushed' : 'action_failed'));
		}

		if ($quickAction === 'optimize_db') {
			if (!$roles['superadmin']) {
				admin_dashboard_redirect($activeTab, 'action_denied');
			}

			admin_dashboard_optimize_database();
			admin_dashboard_redirect($activeTab, 'db_optimized');
		}

		if ($quickAction === 'toggle_siteonline' || $quickAction === 'toggle_registeronline') {
			if (!$roles['superadmin']) {
				admin_dashboard_redirect($activeTab, 'action_denied');
			}

			$key = str_replace('toggle_', '', $quickAction);
			$currentValue = !empty($config[$key]) ? 1 : 0;
			$result = admin_dashboard_update_config_values(array(
				$key => ($currentValue ? 0 : 1),
			), $settingsFieldMap);

			admin_dashboard_redirect($activeTab, ($result === true ? 'setting_toggled' : 'action_failed'));
		}
	}

	if ($action === 'save_settings') {
		$settingsTab = trim((string) ($_POST['settings_tab'] ?? ''));
		if (empty($settingsSchema[$settingsTab]) || empty($roles[$settingsTab])) {
			$flashMessage = admin_dashboard_notice_meta('action_denied');
		} else {
			$updates = array();
			$errors = array();

			foreach ($settingsSchema[$settingsTab]['fields'] as $field) {
				$key = $field['key'];
				$type = $field['type'];

				if ($type === 'checkbox') {
					$value = (!empty($_POST[$key]) ? 1 : 0);
				} else {
					$value = trim((string) ($_POST[$key] ?? ''));
				}

				$settingsDraft[$key] = $value;

				if (!empty($field['required']) && $type !== 'checkbox' && $value === '') {
					$errors[] = 'Поле «'.$field['label'].'» обязательно.';
					continue;
				}

				if ($type === 'int') {
					if ($value === '' || !preg_match('/^-?\d+$/', (string) $value)) {
						$errors[] = 'Поле «'.$field['label'].'» должно быть числом.';
						continue;
					}

					$value = (int) $value;
					if (isset($field['min']) && $value < (int) $field['min']) {
						$errors[] = 'Поле «'.$field['label'].'» должно быть не меньше '.(int) $field['min'].'.';
						continue;
					}
				}

				if ($type === 'select') {
					$options = (array) ($field['options'] ?? array());
					if (!array_key_exists((string) $value, $options)) {
						$errors[] = 'Поле «'.$field['label'].'» содержит неизвестное значение.';
						continue;
					}
				}

				if ($type === 'text' && (strpos((string) $value, "\n") !== false || strpos((string) $value, "\r") !== false)) {
					$errors[] = 'Поле «'.$field['label'].'» не должно содержать перевод строки.';
					continue;
				}

				$currentValue = $config[$key] ?? '';
				if ((string) $currentValue !== (string) $value) {
					$updates[$key] = $value;
				}
			}

			if ($errors) {
				$flashMessage = array(
					'type' => 'error',
					'text' => implode(' ', $errors),
				);
			} elseif (!$updates) {
				admin_dashboard_redirect($settingsTab, 'no_changes');
			} else {
				$result = admin_dashboard_update_config_values($updates, $settingsFieldMap);
				if ($result === true) {
					admin_dashboard_redirect($settingsTab, 'settings_saved');
				}

				$flashMessage = array(
					'type' => 'error',
					'text' => $result,
				);
			}
		}
	}

}

$openWallReportsCount = 0;
if (user_wall_reports_can_moderate()) {
	user_wall_reports_ensure_table();
	$openWallReportsCount = admin_dashboard_stat_value("SELECT COUNT(*) AS c FROM `".user_wall_reports_table_name()."` WHERE status = 'open'");
}

$statsRow = $db->super_query("SELECT
	(SELECT COUNT(*) FROM users) AS users_count,
	(SELECT COUNT(*) FROM torrents) AS torrents_count,
	(SELECT COUNT(*) FROM categories) AS categories_count,
	(SELECT COUNT(*) FROM sessions) AS sessions_count");

$stats = array(
	array('label' => 'Пользователи', 'value' => (int) ($statsRow['users_count'] ?? 0), 'href' => 'users.php'),
	array('label' => 'Торренты', 'value' => (int) ($statsRow['torrents_count'] ?? 0), 'href' => 'browse.php?act=all'),
	array('label' => 'Категории', 'value' => (int) ($statsRow['categories_count'] ?? 0), 'href' => 'categories.php'),
	array('label' => 'Сессии', 'value' => (int) ($statsRow['sessions_count'] ?? 0), 'href' => 'sessions.php'),
);

if (user_wall_reports_can_moderate()) {
	$stats[] = array('label' => 'Жалобы', 'value' => $openWallReportsCount, 'href' => user_wall_reports_href());
}

$tabs = array(
	'overview' => array('label' => 'Обзор', 'allowed' => $roles['overview']),
	'content' => array('label' => 'Контент', 'allowed' => $roles['content']),
	'users' => array('label' => 'Пользователи', 'allowed' => $roles['users']),
	'moderation' => array('label' => 'Модерация', 'allowed' => $roles['moderation']),
	'monitoring' => array('label' => 'Мониторинг', 'allowed' => $roles['monitoring']),
	'site-settings' => array('label' => 'Сайт', 'allowed' => $roles['site-settings']),
	'tracker-settings' => array('label' => 'Трекер', 'allowed' => $roles['tracker-settings']),
	'feature-settings' => array('label' => 'Функции', 'allowed' => $roles['feature-settings']),
);

$sections = array(
	array(
		'tab' => 'content',
		'title' => 'Контент',
		'description' => 'Управление наполнением сайта и структурой каталога.',
		'items' => array(
			array('label' => 'Релизы', 'description' => 'Каталог торрентов, переход к редактированию и модерации.', 'href' => 'browse.php?act=all', 'allowed' => true),
			array('label' => 'Категории', 'description' => 'Создание, редактирование и перенос релизов между категориями.', 'href' => 'categories.php', 'allowed' => !empty($PRIV['cats'])),
			array('label' => 'Новости', 'description' => 'Публикация и редактирование новостей проекта.', 'href' => 'news.php', 'allowed' => !empty($PRIV['news_add']) || !empty($PRIV['edit_news'])),
			array('label' => 'FAQ', 'description' => 'Управление разделом вопросов и ответов.', 'href' => 'faq.php', 'allowed' => !empty($PRIV['faq_moderate'])),
		),
	),
	array(
		'tab' => 'users',
		'title' => 'Пользователи',
		'description' => 'Аккаунты, роли, права и коммуникации с аудиторией.',
		'items' => array(
			array('label' => 'Список пользователей', 'description' => 'Поиск, просмотр и переход к настройкам профилей.', 'href' => 'users.php', 'allowed' => !empty($PRIV['users_view'])),
			array('label' => 'Добавить пользователя', 'description' => 'Ручное создание аккаунтов с нужным классом.', 'href' => 'user_add.php', 'allowed' => !empty($PRIV['user_add'])),
			array('label' => 'Классы и права', 'description' => 'Редактирование классов и массовый перенос пользователей.', 'href' => 'edit_priv.php', 'allowed' => !empty($PRIV['EDIT_PRIV'])),
			array('label' => 'Массовая рассылка', 'description' => 'Отправка служебных сообщений выбранным группам пользователей.', 'href' => 'messages.php', 'allowed' => !empty($PRIV['messages'])),
		),
	),
	array(
		'tab' => 'moderation',
		'title' => 'Модерация и безопасность',
		'description' => 'Жалобы, IP и подозрительная активность.',
		'items' => array(
			array('label' => 'Жалобы на стену', 'description' => 'Модерация жалоб на комментарии в профилях.', 'href' => user_wall_reports_href(), 'allowed' => user_wall_reports_can_moderate(), 'badge' => ($openWallReportsCount > 0 ? $openWallReportsCount.' открыто' : '')),
			array('label' => 'IP и блокировки', 'description' => 'Проверка IP, диапазонов и ручное управление банами.', 'href' => 'ip.util.php', 'allowed' => !empty($PRIV['ip_util'])),
			array('label' => 'Мультитрекерные аккаунты', 'description' => 'Отлов подозрительных пользователей по IP и торрентам.', 'href' => 'multitracker_accounts.php', 'allowed' => !empty($PRIV['multitracker_accounts'])),
		),
	),
	array(
		'tab' => 'monitoring',
		'title' => 'Мониторинг',
		'description' => 'Состояние активности сайта и служебные журналы.',
		'items' => array(
			array('label' => 'Сессии', 'description' => 'Кто сейчас на сайте и какие страницы открыты.', 'href' => 'sessions.php', 'allowed' => !empty($PRIV['sessions_view'])),
			array('label' => 'Мониторинг поиска', 'description' => 'Запросы пользователей и оповещения по найденным релизам.', 'href' => 'search_query.php', 'allowed' => !empty($PRIV['search_query'])),
		),
	),
);

$quickActions = array(
	array('id' => 'toggle_siteonline', 'label' => (!empty($config['siteonline']) ? 'Закрыть сайт' : 'Открыть сайт'), 'description' => (!empty($config['siteonline']) ? 'Сразу перевести сайт в закрытый режим.' : 'Снова открыть доступ к сайту.'), 'allowed' => $roles['superadmin'], 'confirm' => 'Изменить публичный статус сайта?'),
	array('id' => 'toggle_registeronline', 'label' => (!empty($config['registeronline']) ? 'Закрыть регистрацию' : 'Открыть регистрацию'), 'description' => 'Мгновенно переключить доступность регистрации новых пользователей.', 'allowed' => $roles['superadmin'], 'confirm' => 'Изменить доступность регистрации?'),
	array('id' => 'clear_sessions', 'label' => 'Очистить сессии', 'description' => 'Удалить все активные записи из таблицы сессий.', 'allowed' => !empty($PRIV['sessions_clear']), 'confirm' => 'Очистить все сессии?'),
	array('id' => 'clear_search_queries', 'label' => 'Очистить мониторинг поиска', 'description' => 'Стереть накопленные поисковые запросы пользователей.', 'allowed' => $roles['superadmin'], 'confirm' => 'Очистить мониторинг поиска?'),
	array('id' => 'flush_cache', 'label' => 'Очистить кэш', 'description' => 'Сбросить memcached и файловый кэш.', 'allowed' => $roles['superadmin'], 'confirm' => 'Очистить весь кэш?'),
	array('id' => 'optimize_db', 'label' => 'Оптимизировать БД', 'description' => 'Запустить OPTIMIZE TABLE для таблиц базы. Полезно после массовых удалений и чистки логов.', 'allowed' => $roles['superadmin'], 'confirm' => 'Запустить оптимизацию таблиц базы данных?'),
);

$shortcuts = array(
	array('label' => 'Добавить пользователя', 'href' => 'user_add.php', 'allowed' => !empty($PRIV['user_add'])),
	array('label' => 'Добавить новость', 'href' => 'news.php?act=add', 'allowed' => !empty($PRIV['news_add']) || !empty($PRIV['edit_news'])),
	array('label' => 'Редактировать новости', 'href' => 'news.php', 'allowed' => !empty($PRIV['news_add']) || !empty($PRIV['edit_news'])),
	array('label' => 'Открыть классы и права', 'href' => 'edit_priv.php', 'allowed' => !empty($PRIV['EDIT_PRIV'])),
	array('label' => 'Открыть жалобы', 'href' => user_wall_reports_href(), 'allowed' => user_wall_reports_can_moderate()),
);

$roleBadges = array();
if ($roles['superadmin']) {
	$roleBadges[] = 'Суперадминистратор';
}
if ($roles['content']) {
	$roleBadges[] = 'Контент';
}
if ($roles['users']) {
	$roleBadges[] = 'Пользователи';
}
if ($roles['moderation']) {
	$roleBadges[] = 'Модерация';
}
if ($roles['monitoring']) {
	$roleBadges[] = 'Мониторинг';
}

$classPermissionLabels = array(
	'upload' => 'загрузка релизов',
	'details_view' => 'просмотр релизов',
	'edit_release' => 'редактирование релизов',
	'comments_edit' => 'редактирование комментариев',
	'comments_delete' => 'удаление комментариев',
	'download_torrent' => 'скачивание torrent',
	'download_magnet' => 'скачивание magnet',
	'setting_user' => 'редактирование аккаунтов',
	'users_view' => 'список пользователей',
	'user_add' => 'добавление пользователей',
	'news_add' => 'новости',
	'cats' => 'категории',
	'ip_util' => 'IP-утилиты',
	'sessions_view' => 'сессии',
	'messages' => 'рассылка',
	'EDIT_PRIV' => 'классы и права',
);
$classPermissionRows = array();
if (!empty($PRIV['EDIT_PRIV'])) {
	foreach (get_classes_list() as $classRow) {
		$enabled = array();
		foreach ($classPermissionLabels as $permissionKey => $permissionLabel) {
			if (!empty($classRow[$permissionKey])) {
				$enabled[] = $permissionLabel;
			}
		}
		$classPermissionRows[] = array(
			'id' => (int) $classRow['id'],
			'name' => (string) $classRow['NAME'],
			'enabled' => $enabled,
		);
	}
}

$configPath = admin_dashboard_config_path();
$configDisplayPath = admin_dashboard_display_path($configPath);
$configWritable = admin_dashboard_ensure_writable($configPath);
head('Админка');
?>
<style>
.admin-dashboard {
	max-width: 1320px;
	margin: 16px auto 0;
	padding: 0 18px 28px;
	box-sizing: border-box;
	color: #1c2733;
}

.admin-hero,
.admin-tabs,
.admin-card,
.admin-settings-form,
.admin-inline-message {
	border: 1px solid #dfe7ef;
	border-radius: 6px;
	background: #fff;
	box-sizing: border-box;
}

.admin-hero {
	display: grid;
	grid-template-columns: minmax(0, 1fr) auto;
	gap: 18px;
	align-items: end;
	padding: 20px 22px;
	margin-bottom: 12px;
	border-top: 4px solid #334e68;
}

.admin-hero-kicker {
	margin: 0 0 5px;
	color: #738091;
	font-size: 12px;
	font-weight: 700;
	line-height: 1.2;
	text-transform: uppercase;
}

.admin-hero-title {
	margin: 0;
	color: #111827;
	font-size: 28px;
	line-height: 1.15;
}

.admin-hero-text {
	max-width: 820px;
	margin: 8px 0 0;
	color: #536170;
	font-size: 14px;
	line-height: 1.55;
}

.admin-role-badges {
	display: flex;
	flex-wrap: wrap;
	justify-content: flex-end;
	gap: 6px;
}

.admin-role-badge,
.admin-link-badge {
	display: inline-flex;
	align-items: center;
	min-height: 24px;
	padding: 0 9px;
	border: 1px solid #d7e1eb;
	border-radius: 999px;
	background: #f4f7fa;
	color: #45576a;
	font-size: 12px;
	font-weight: 700;
	line-height: 1;
	white-space: nowrap;
}

.admin-inline-message {
	padding: 12px 14px;
	margin-bottom: 12px;
	font-size: 14px;
	line-height: 1.45;
}

.admin-inline-message-success {
	border-color: #c9e0cf;
	background: #f0f8f2;
	color: #235c33;
}

.admin-inline-message-error {
	border-color: #ecc8c8;
	background: #fff3f3;
	color: #783131;
}

.admin-tabs {
	position: sticky;
	top: 0;
	z-index: 20;
	display: flex;
	flex-wrap: wrap;
	gap: 4px;
	margin-bottom: 14px;
	padding: 6px;
}

.admin-tab-link {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	min-height: 34px;
	padding: 0 12px;
	border-radius: 4px;
	color: #536170;
	font-size: 13px;
	font-weight: 700;
	text-decoration: none;
}

.admin-tab-link:hover {
	background: #f2f6fa;
	color: #111827;
}

.admin-tab-link-active {
	background: #334e68;
	color: #fff;
}

.admin-stats,
.admin-action-grid,
.admin-shortcut-grid,
.admin-grid,
.admin-settings-grid,
.admin-system-grid,
.admin-permission-list {
	display: grid;
	gap: 12px;
}

.admin-stats {
	grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
	margin-bottom: 14px;
}

.admin-action-grid,
.admin-shortcut-grid,
.admin-grid,
.admin-system-grid {
	grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
}

.admin-settings-grid {
	grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
	margin-top: 16px;
}

.admin-card,
.admin-settings-form {
	padding: 18px 20px;
	margin-bottom: 14px;
}

.admin-stat-card,
.admin-link-card,
.admin-action-card,
.admin-permission-row,
.admin-system-item {
	display: block;
	padding: 14px 16px;
	border: 1px solid #e1e8ef;
	border-radius: 5px;
	background: #fbfcfd;
	color: inherit;
	text-decoration: none;
	box-sizing: border-box;
}

.admin-stat-card:hover,
.admin-link-card:hover,
.admin-permission-row:hover {
	border-color: #b8c9d8;
	background: #fff;
}

.admin-stat-value {
	display: block;
	color: #111827;
	font-size: 26px;
	font-weight: 800;
	line-height: 1;
}

.admin-stat-label,
.admin-link-text,
.admin-action-text,
.admin-card-text,
.admin-settings-text,
.admin-permission-text,
.admin-settings-help {
	color: #647283;
	font-size: 13px;
	line-height: 1.5;
}

.admin-stat-label,
.admin-link-text,
.admin-action-text,
.admin-permission-text {
	display: block;
	margin-top: 7px;
}

.admin-card-title,
.admin-settings-title {
	margin: 0 0 7px;
	color: #111827;
	font-size: 20px;
	line-height: 1.25;
}

.admin-card-text,
.admin-settings-text {
	margin: 0;
}

.admin-link-row,
.admin-action-row {
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: 12px;
}

.admin-link-title,
.admin-action-title,
.admin-permission-name {
	color: #111827;
	font-size: 15px;
	font-weight: 800;
	line-height: 1.3;
}

.admin-action-card {
	padding: 0;
	background: #fff;
	overflow: hidden;
}

.admin-action-form {
	display: block;
	padding: 14px 16px;
}

.admin-action-button,
.admin-settings-submit,
.admin-shortcut-link {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	min-height: 36px;
	padding: 0 13px;
	border: 1px solid #334e68;
	border-radius: 4px;
	background: #334e68;
	color: #fff;
	font-size: 13px;
	font-weight: 800;
	line-height: 1;
	text-decoration: none;
	cursor: pointer;
}

.admin-action-button:hover,
.admin-settings-submit:hover,
.admin-shortcut-link:hover {
	border-color: #263d55;
	background: #263d55;
	color: #fff;
}

.admin-shortcut-grid {
	margin-top: 14px;
}

.admin-settings-field {
	display: grid;
	gap: 7px;
}

.admin-settings-label {
	color: #1f2a35;
	font-size: 13px;
	font-weight: 800;
}

.admin-settings-help {
	margin: -2px 0 0;
}

.admin-settings-input,
.admin-settings-checkbox-row {
	width: 100%;
	border: 1px solid #cfd9e4;
	border-radius: 4px;
	background: #fff;
	box-sizing: border-box;
}

.admin-settings-input {
	height: 38px;
	padding: 0 10px;
	color: #1f2a35;
	font-size: 14px;
}

.admin-settings-checkbox-row {
	display: flex;
	align-items: center;
	gap: 9px;
	min-height: 38px;
	padding: 0 10px;
	color: #1f2a35;
	font-size: 14px;
}

.admin-settings-footer {
	display: flex;
	justify-content: flex-end;
	margin-top: 16px;
}

.admin-empty {
	padding: 16px;
	border: 1px dashed #cfd9e4;
	border-radius: 5px;
	background: #fbfcfd;
	color: #647283;
	font-size: 14px;
	line-height: 1.5;
}

.admin-system-label {
	color: #738091;
	font-size: 11px;
	font-weight: 800;
	text-transform: uppercase;
}

.admin-system-value {
	margin-top: 6px;
	color: #1f2a35;
	font-size: 13px;
	line-height: 1.45;
	word-break: break-word;
}

.admin-system-path {
	font-family: Consolas, Monaco, monospace;
	font-size: 12px;
}

@media (max-width: 720px) {
	.admin-dashboard {
		padding: 0 12px 20px;
	}

	.admin-hero {
		grid-template-columns: 1fr;
		align-items: start;
		padding: 18px;
	}

	.admin-role-badges {
		justify-content: flex-start;
	}

	.admin-card,
	.admin-settings-form {
		padding: 16px;
	}
}
</style>

<div class='admin-dashboard'>
		<section class='admin-hero'>
			<div>
				<p class='admin-hero-kicker'>LiteTracker control</p>
				<h1 class='admin-hero-title'>Админка</h1>
				<p class='admin-hero-text'>Быстрый доступ к живым разделам, настройкам и служебным действиям. Лишние визуальные блоки убраны, права по-прежнему ограничивают вкладки и операции.</p>
			</div>
			<?php if ($roleBadges) { ?>
			<div class='admin-role-badges'>
				<?php foreach ($roleBadges as $badge) { ?>
				<span class='admin-role-badge'><?=$badge;?></span>
				<?php } ?>
			</div>
			<?php } ?>
		</section>

	<?php if (!empty($flashMessage['text'])) { ?>
	<div class='admin-inline-message admin-inline-message-<?=($flashMessage['type'] === 'error' ? 'error' : 'success');?>'>
		<?=$flashMessage['text'];?>
	</div>
	<?php } ?>

	<nav class='admin-tabs' aria-label='Разделы админки'>
		<?php foreach ($tabs as $tabKey => $tabMeta) { ?>
		<?php if (empty($tabMeta['allowed'])) { continue; } ?>
		<a class='admin-tab-link<?=($activeTab === $tabKey ? ' admin-tab-link-active' : '');?>' href='<?=admin_dashboard_build_href(array('tab' => $tabKey, 'notice' => null));?>'><?=$tabMeta['label'];?></a>
		<?php } ?>
	</nav>

	<?php if ($activeTab === 'overview') { ?>
	<div class='admin-stats'>
		<?php foreach ($stats as $stat) { ?>
		<a class='admin-stat-card' href='<?=$stat['href'];?>'>
			<span class='admin-stat-value'><?=number_format((int) $stat['value']);?></span>
			<span class='admin-stat-label'><?=$stat['label'];?></span>
		</a>
		<?php } ?>
	</div>

	<section class='admin-card' style='margin-top:20px;'>
		<h2 class='admin-card-title'>Состояние системы</h2>
		<p class='admin-card-text'>Короткие технические индикаторы, чтобы было понятно, почему настройка может не сохраняться или почему блоки не видны.</p>
		<div class='admin-system-grid'>
			<div class='admin-system-item'>
				<div class='admin-system-label'>Конфиг</div>
				<div class='admin-system-value'><span class='admin-system-path'><?=htmlspecialchars($configDisplayPath, ENT_QUOTES, 'UTF-8');?></span><br><?=($configWritable ? 'доступен для записи из админки' : 'недоступен для записи, проверьте права файла');?></div>
			</div>
			<div class='admin-system-item'>
				<div class='admin-system-label'>CAPTCHA</div>
				<div class='admin-system-value'><?=(!empty($config['captcha']) ? 'включена' : 'выключена');?>; режим: локальная встроенная проверка.</div>
			</div>
		</div>
	</section>

	<section class='admin-card'>
		<h2 class='admin-card-title'>Быстрые действия</h2>
		<p class='admin-card-text'>Операции, которые можно выполнить прямо из админки без перехода в отдельные страницы.</p>
		<div class='admin-action-grid' style='margin-top:18px;'>
			<?php $hasQuickActions = false; ?>
			<?php foreach ($quickActions as $action) { ?>
			<?php if (empty($action['allowed'])) { continue; } ?>
			<?php $hasQuickActions = true; ?>
			<div class='admin-action-card'>
				<form class='admin-action-form' method='post' action='admin.php'<?=(empty($action['confirm']) ? '' : ' data-admin-confirm="'.htmlspecialchars($action['confirm'], ENT_QUOTES, 'UTF-8').'"');?> >
					<input type='hidden' name='tab' value='overview'>
					<input type='hidden' name='admin_action' value='quick_action'>
					<input type='hidden' name='quick_action' value='<?=$action['id'];?>'>
					<?=lt_csrf_input('admin_dashboard');?>
					<div class='admin-action-row'>
						<div class='admin-action-title'><?=$action['label'];?></div>
						<button class='admin-action-button' type='submit'>Выполнить</button>
					</div>
					<div class='admin-action-text'><?=$action['description'];?></div>
				</form>
			</div>
			<?php } ?>
		</div>
		<?php if (!$hasQuickActions) { ?>
		<div class='admin-empty' style='margin-top:18px;'>Для вашей роли нет прямых действий на этой странице.</div>
		<?php } ?>

		<div class='admin-shortcut-grid'>
			<?php $hasShortcuts = false; ?>
			<?php foreach ($shortcuts as $shortcut) { ?>
			<?php if (empty($shortcut['allowed'])) { continue; } ?>
			<?php $hasShortcuts = true; ?>
			<a class='admin-shortcut-link' href='<?=$shortcut['href'];?>'><?=$shortcut['label'];?></a>
			<?php } ?>
		</div>
		<?php if (!$hasShortcuts) { ?>
		<div class='admin-empty' style='margin-top:18px;'>Дополнительных быстрых переходов для вашей роли нет.</div>
		<?php } ?>
	</section>

	<?php foreach ($sections as $section) { ?>
	<?php
	$visibleItems = array();
	foreach ($section['items'] as $item) {
		if (!empty($item['allowed'])) {
			$visibleItems[] = $item;
		}
	}
	?>
	<?php if (!$visibleItems) { continue; } ?>
	<section class='admin-card'>
		<h2 class='admin-card-title'><?=$section['title'];?></h2>
		<p class='admin-card-text'><?=$section['description'];?></p>
		<div class='admin-grid' style='margin-top:18px;'>
			<?php foreach ($visibleItems as $item) { ?>
			<a class='admin-link-card' href='<?=$item['href'];?>'>
				<div class='admin-link-row'>
					<div class='admin-link-title'><?=$item['label'];?></div>
					<?php if (!empty($item['badge'])) { ?>
					<span class='admin-link-badge'><?=$item['badge'];?></span>
					<?php } ?>
				</div>
				<div class='admin-link-text'><?=$item['description'];?></div>
			</a>
			<?php } ?>
		</div>
	</section>
	<?php } ?>
	<?php } elseif (!empty($settingsSchema[$activeTab]) && !empty($roles[$activeTab])) { ?>
	<?php $settingsTab = $settingsSchema[$activeTab]; ?>
	<section class='admin-settings-form'>
		<h2 class='admin-settings-title'><?=$settingsTab['title'];?></h2>
		<p class='admin-settings-text'><?=$settingsTab['description'];?></p>
		<?php if (!$configWritable) { ?>
		<div class='admin-inline-message admin-inline-message-error' style='margin-top:16px;'>Файл <?=htmlspecialchars($configDisplayPath, ENT_QUOTES, 'UTF-8');?> сейчас недоступен для записи, поэтому сохранение настроек не пройдет.</div>
		<?php } ?>
		<form method='post' action='admin.php'>
			<input type='hidden' name='tab' value='<?=$activeTab;?>'>
			<input type='hidden' name='admin_action' value='save_settings'>
			<input type='hidden' name='settings_tab' value='<?=$activeTab;?>'>
			<?=lt_csrf_input('admin_dashboard');?>
			<div class='admin-settings-grid'>
				<?php foreach ($settingsTab['fields'] as $field) { ?>
				<?php
				$key = $field['key'];
				$value = (array_key_exists($key, $settingsDraft) ? $settingsDraft[$key] : ($config[$key] ?? ''));
				?>
				<div class='admin-settings-field'>
					<label class='admin-settings-label' for='admin-setting-<?=$key;?>'><?=$field['label'];?></label>
					<?php if (!empty($field['description'])) { ?>
					<div class='admin-settings-help'><?=htmlspecialchars($field['description'], ENT_QUOTES, 'UTF-8');?></div>
					<?php } ?>
					<?php if ($field['type'] === 'checkbox') { ?>
					<label class='admin-settings-checkbox-row' for='admin-setting-<?=$key;?>'>
						<input id='admin-setting-<?=$key;?>' type='checkbox' name='<?=$key;?>' value='1'<?=(!empty($value) ? ' checked' : '');?> >
						<span>Включено</span>
					</label>
					<?php } elseif ($field['type'] === 'select') { ?>
					<select class='admin-settings-input' id='admin-setting-<?=$key;?>' name='<?=$key;?>'>
						<?php foreach ((array) ($field['options'] ?? array()) as $optionValue => $optionLabel) { ?>
						<option value='<?=htmlspecialchars((string) $optionValue, ENT_QUOTES, 'UTF-8');?>'<?=((string) $value === (string) $optionValue ? ' selected' : '');?>><?=htmlspecialchars((string) $optionLabel, ENT_QUOTES, 'UTF-8');?></option>
						<?php } ?>
					</select>
					<?php } else { ?>
					<input class='admin-settings-input' id='admin-setting-<?=$key;?>' type='text' name='<?=$key;?>' value='<?=htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');?>'>
					<?php } ?>
				</div>
				<?php } ?>
			</div>
			<div class='admin-settings-footer'>
				<button class='admin-settings-submit' type='submit'>Сохранить</button>
			</div>
		</form>
	</section>
	<?php } else { ?>
	<?php $hasVisibleSection = false; ?>
	<?php foreach ($sections as $section) { ?>
	<?php if ($section['tab'] !== $activeTab) { continue; } ?>
	<?php
	$visibleItems = array();
	foreach ($section['items'] as $item) {
		if (!empty($item['allowed'])) {
			$visibleItems[] = $item;
		}
	}
	?>
	<?php if (!$visibleItems) { continue; } ?>
	<?php $hasVisibleSection = true; ?>
	<section class='admin-card'>
		<h2 class='admin-card-title'><?=$section['title'];?></h2>
		<p class='admin-card-text'><?=$section['description'];?></p>
		<div class='admin-grid' style='margin-top:18px;'>
			<?php foreach ($visibleItems as $item) { ?>
			<a class='admin-link-card' href='<?=$item['href'];?>'>
				<div class='admin-link-row'>
					<div class='admin-link-title'><?=$item['label'];?></div>
					<?php if (!empty($item['badge'])) { ?>
					<span class='admin-link-badge'><?=$item['badge'];?></span>
					<?php } ?>
				</div>
				<div class='admin-link-text'><?=$item['description'];?></div>
			</a>
			<?php } ?>
		</div>
	</section>
	<?php if ($activeTab === 'users' && $classPermissionRows) { ?>
	<section class='admin-card'>
		<h2 class='admin-card-title'>Что может каждый класс</h2>
		<p class='admin-card-text'>Краткая сводка включенных прав. Полный набор переключателей открывается через редактирование класса.</p>
		<div class='admin-permission-list'>
			<?php foreach ($classPermissionRows as $classRow) { ?>
			<a class='admin-permission-row' href='edit_priv.php?id=<?=$classRow['id'];?>&amp;act=edit'>
				<div class='admin-permission-name'><?=htmlspecialchars($classRow['name'], ENT_QUOTES, 'UTF-8');?></div>
				<div class='admin-permission-text'><?=htmlspecialchars($classRow['enabled'] ? implode(', ', $classRow['enabled']) : 'нет включенных прав', ENT_QUOTES, 'UTF-8');?></div>
			</a>
			<?php } ?>
		</div>
	</section>
	<?php } ?>
	<?php } ?>
	<?php if (!$hasVisibleSection) { ?>
	<div class='admin-empty'>Для этого раздела пока нет доступных модулей.</div>
	<?php } ?>
	<?php } ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
	function closestByClass(node, className) {
		while (node && node !== document) {
			if (node.classList && node.classList.contains(className)) {
				return node;
			}

			node = node.parentNode;
		}

		return null;
	}

	function replaceAdminDashboard(url, pushState) {
		if (typeof window.fetch !== 'function' || typeof window.DOMParser !== 'function') {
			window.location.href = url;
			return;
		}

		window.fetch(url, {
			method: 'GET',
			credentials: 'same-origin',
			headers: {
				'X-Requested-With': 'XMLHttpRequest',
				'Accept': 'text/html'
			}
		})
			.then(function (response) {
				if (!response.ok) {
					throw new Error('Request failed');
				}

				return response.text();
			})
			.then(function (html) {
				var parsed = new window.DOMParser().parseFromString(html, 'text/html');
				var nextDashboard = parsed.querySelector('.admin-dashboard');
				var currentDashboard = document.querySelector('.admin-dashboard');

				if (!nextDashboard || !currentDashboard) {
					throw new Error('Dashboard not found');
				}

				currentDashboard.innerHTML = nextDashboard.innerHTML;
				if (pushState) {
					window.history.pushState({adminAjax: true}, '', url);
				}
				document.title = parsed.title || document.title;
				window.scrollTo(0, 0);
			})
			.catch(function () {
				window.location.href = url;
			});
	}

	document.addEventListener('submit', function (event) {
		var form = event.target;
		var message = form && form.getAttribute ? (form.getAttribute('data-admin-confirm') || '') : '';

		if (message && !window.confirm(message)) {
			event.preventDefault();
		}
	});

	document.addEventListener('click', function (event) {
		var link = closestByClass(event.target, 'admin-tab-link');
		var href;

		if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
			return;
		}

		href = link.getAttribute('href') || '';
		if (!href) {
			return;
		}

		event.preventDefault();
		replaceAdminDashboard(href, true);
	});

	window.addEventListener('popstate', function () {
		replaceAdminDashboard(window.location.href, false);
	});
});
</script>
<?php
foot();
?>
