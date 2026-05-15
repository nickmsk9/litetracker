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
require 'system/functions/functions.migrations.php';

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

function admin_torrent_moderation_statuses()
{
	return array('pending', 'need_fix', 'hidden', 'rejected', 'approved', 'deleted', 'all');
}

function admin_torrent_moderation_action_status($action)
{
	$map = array(
		'approve' => 'approved',
		'need_fix' => 'need_fix',
		'reject' => 'rejected',
		'hide' => 'hidden',
		'restore' => 'approved',
		'soft_delete' => 'deleted',
	);

	$action = trim((string) $action);
	return ($map[$action] ?? '');
}

function admin_torrent_moderation_json($ok, $message, $extra = array(), $statusCode = 200)
{
	// lt_json_response sets JSON header, HTTP code, and terminates request.
	lt_json_response(array_merge(array(
		'ok' => (bool) $ok,
		'message' => (string) $message,
	), (array) $extra), (int) $statusCode);
}

function admin_torrent_moderation_is_ajax()
{
	return (!empty($_POST['ajax']) || strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest');
}

function admin_torrent_moderation_items($status)
{
	global $db;

	lt_torrent_status_ensure_schema();

	$status = trim((string) $status);
	if (!in_array($status, admin_torrent_moderation_statuses(), true)) {
		$status = 'pending';
	}

	$where = array();
	if ($status !== 'all') {
		$where[] = "t.status = '".$db->safesql($status)."'";
	}

	$sql = $db->query(
		"SELECT t.*, u.name AS owner_name, u.class AS owner_class, c.name AS category_name, r.name AS reviewer_name, r.class AS reviewer_class
		 FROM torrents AS t
		 LEFT JOIN users AS u ON u.id = t.id_user
		 LEFT JOIN categories AS c ON c.id = t.id_category
		 LEFT JOIN users AS r ON r.id = t.reviewed_by
		 ".($where ? 'WHERE '.implode(' AND ', $where) : '')."
		 ORDER BY FIELD(t.status, 'pending', 'need_fix', 'hidden', 'rejected', 'approved', 'deleted'), t.added DESC
		 LIMIT 100"
	);

	$items = array();
	while ($row = $db->get_row($sql)) {
		$items[] = $row;
	}
	$db->free($sql);

	return $items;
}

function admin_torrent_moderation_counts()
{
	global $db;

	lt_torrent_status_ensure_schema();

	$counts = array(
		'pending' => 0,
		'need_fix' => 0,
		'hidden' => 0,
		'rejected' => 0,
		'approved' => 0,
		'deleted' => 0,
	);

	$sql = $db->query("SELECT status, COUNT(*) AS c FROM torrents GROUP BY status", 0);
	if ($sql) {
		while ($row = $db->get_row($sql)) {
			$status = lt_torrent_status_normalize($row['status'] ?? 'approved');
			if (array_key_exists($status, $counts)) {
				$counts[$status] = (int) ($row['c'] ?? 0);
			}
		}
		$db->free($sql);
	}

	return $counts;
}

function admin_moderation_log_actions()
{
	return array(
		'torrent_approve',
		'torrent_need_fix',
		'torrent_reject',
		'torrent_hide',
		'torrent_restore',
		'torrent_soft_delete',
	);
}

function admin_moderation_log_moderators()
{
	global $db;

	if (!lt_moderation_log_ensure_schema()) {
		return array();
	}

	$sql = $db->query(
		"SELECT DISTINCT u.id, u.name
		 FROM moderation_log AS ml
		 INNER JOIN users AS u ON u.id = ml.moderator_id
		 ORDER BY u.name ASC
		 LIMIT 200",
		0
	);

	$rows = array();
	if ($sql) {
		while ($row = $db->get_row($sql)) {
			$rows[] = $row;
		}
		$db->free($sql);
	}

	return $rows;
}

function admin_dashboard_role_map($user, $priv)
{
	$superadmin = admin_dashboard_is_superadmin($user, $priv);
	$content = ($superadmin || !empty($priv['edit_release']) || !empty($priv['cats']) || !empty($priv['news_add']) || !empty($priv['edit_news']) || !empty($priv['faq_moderate']));
	$users = ($superadmin || !empty($priv['user_add']) || !empty($priv['setting_user']) || !empty($priv['messages']));
	$moderation = ($superadmin || !empty($priv['edit_release']) || !empty($priv['comments_edit']) || !empty($priv['comments_delete']) || !empty($priv['ip_util']) || !empty($priv['multitracker_accounts']) || user_wall_reports_can_moderate());
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
		'migrations' => $superadmin,
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
				array('key' => 'default_theme', 'label' => 'Тема оформления по умолчанию', 'type' => 'select', 'options' => (function_exists('lt_themes_get_admin_options') ? lt_themes_get_admin_options() : array('default' => 'LiteTracker Default')), 'description' => 'Тема, которая применяется для всех гостей и пользователей, не выбравших свою тему в профиле. Каждый пользователь может сменить тему в настройках.'),
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
		'torrent_status_updated' => array('type' => 'success', 'text' => 'Статус релиза обновлен.'),
		'settings_saved' => array('type' => 'success', 'text' => 'Настройки сохранены.'),
		'no_changes' => array('type' => 'success', 'text' => 'Изменений не было.'),
		'dry_run_complete' => array('type' => 'success', 'text' => 'Сухой прогон завершен.'),
		'migrations_applied' => array('type' => 'success', 'text' => 'Миграции применены.'),
		'migrations_partial' => array('type' => 'error', 'text' => 'Некоторые миграции не применены.'),
		'no_migrations_selected' => array('type' => 'error', 'text' => 'Не выбрано ни одной миграции.'),
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
$activeSection = trim((string) ($_GET['section'] ?? ''));
$flashMessage = admin_dashboard_notice_meta(trim((string) ($_GET['notice'] ?? '')));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$postedTab = admin_dashboard_normalize_tab($_POST['tab'] ?? $activeTab, $roles);
	$activeTab = $postedTab;
	$action = trim((string) ($_POST['admin_action'] ?? ''));

	if (!lt_csrf_validate('admin_dashboard')) {
		if ($action === 'torrent_moderation' && admin_torrent_moderation_is_ajax()) {
			admin_torrent_moderation_json(false, 'Защитный токен устарел. Обновите страницу и повторите действие.', array(), 403);
		}
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

	if ($action === 'torrent_moderation') {
		if (!lt_torrent_can_moderate($USER)) {
			if (admin_torrent_moderation_is_ajax()) {
				admin_torrent_moderation_json(false, 'У вас нет прав на модерацию раздач.', array(), 403);
			}
			admin_dashboard_redirect('moderation', 'action_denied');
		}

		$torrentId = (int) ($_POST['torrent_id'] ?? 0);
		$moderationAction = trim((string) ($_POST['moderation_action'] ?? ''));
		$newStatus = admin_torrent_moderation_action_status($moderationAction);
		$reason = trim((string) ($_POST['status_reason'] ?? ''));
		if ($torrentId <= 0 || $newStatus === '') {
			if (admin_torrent_moderation_is_ajax()) {
				admin_torrent_moderation_json(false, 'Некорректное действие модерации.', array(), 400);
			}
			admin_dashboard_redirect('moderation', 'action_failed');
		}

		$ok = lt_torrent_set_status($torrentId, $newStatus, (int) $USER['id'], $reason);
		if (admin_torrent_moderation_is_ajax()) {
			admin_torrent_moderation_json((bool) $ok, ($ok ? 'Статус релиза обновлен.' : 'Операция не выполнена.'), array(
				'torrent_id' => $torrentId,
				'status' => $newStatus,
				'status_label' => lt_torrent_status_label($newStatus),
			), ($ok ? 200 : 400));
		}

		header('Location: admin.php?tab=moderation&section=torrents&status='.rawurlencode(trim((string) ($_POST['filter_status'] ?? 'pending'))).'&notice='.($ok ? 'torrent_status_updated' : 'action_failed'));
		die();
	}

	if ($action === 'migrations') {
		if (!$roles['superadmin']) {
			admin_dashboard_redirect('overview', 'action_denied');
		}

		$migrationsAction = trim((string) ($_POST['migrations_action'] ?? ''));

		if ($migrationsAction === 'dry_run') {
			// Dry run - just count pending migrations
			$dryRunResult = lt_migrations_dry_run();
			$_SESSION['admin_migrations_dryrun'] = $dryRunResult;
			admin_dashboard_redirect('migrations', 'dry_run_complete');
		}

		if ($migrationsAction === 'apply') {
			// Apply pending migrations
			$migrationNames = array();
			$selectedMigrations = (array) ($_POST['selected_migrations'] ?? array());

			foreach ($selectedMigrations as $name) {
				$name = trim((string) $name);
				if (!empty($name) && preg_match('~^[0-9_a-z.]+\\.sql$~i', $name)) {
					$migrationNames[] = $name;
				}
			}

			if (empty($migrationNames)) {
				admin_dashboard_redirect('migrations', 'no_migrations_selected');
			}

			$errors = array();
			$applied = 0;
			$migrations = lt_migrations_full_list();

			foreach ($migrations as $migration) {
				if (!in_array($migration['name'], $migrationNames, true)) {
					continue;
				}

				// Only apply pending or changed migrations
				if (!in_array($migration['status'], array('pending', 'changed'), true)) {
					continue;
				}

				$errorMsg = '';
				if (lt_migrations_execute($migration['name'], $migration['content'], $errorMsg)) {
					$applied++;
				} else {
					$errors[] = $migration['name'].': '.$errorMsg;
				}
			}

			$_SESSION['admin_migrations_applied'] = $applied;
			$_SESSION['admin_migrations_errors'] = $errors;

			admin_dashboard_redirect('migrations', (!empty($errors) ? 'migrations_partial' : 'migrations_applied'));
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
					// Invalidate themes cache if the default theme changed
					if (isset($updates['default_theme']) && function_exists('lt_themes_invalidate_cache')) {
						lt_themes_invalidate_cache();
					}
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

$torrentModerationCounts = array();
$torrentModerationQueueCount = 0;
if (lt_torrent_can_moderate($USER)) {
	$torrentModerationCounts = admin_torrent_moderation_counts();
	$torrentModerationQueueCount = (int) ($torrentModerationCounts['pending'] ?? 0) + (int) ($torrentModerationCounts['need_fix'] ?? 0);
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
if (lt_torrent_can_moderate($USER)) {
	$stats[] = array('label' => 'На модерации', 'value' => $torrentModerationQueueCount, 'href' => 'admin.php?tab=moderation&section=torrents&status=pending');
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
	'migrations' => array('label' => 'БД / Миграции', 'allowed' => $roles['migrations']),
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
			array('label' => 'Модерация раздач', 'description' => 'Очередь релизов: одобрение, доработка, скрытие, отклонение и soft delete.', 'href' => 'admin.php?tab=moderation&section=torrents', 'allowed' => lt_torrent_can_moderate($USER), 'badge' => ($torrentModerationQueueCount > 0 ? $torrentModerationQueueCount.' в очереди' : '')),
			array('label' => 'Журнал модерации', 'description' => 'Кто, когда и какой статус изменил у раздач.', 'href' => 'admin.php?tab=moderation&section=log', 'allowed' => lt_torrent_can_moderate($USER)),
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
	array('label' => 'Добавить пользователя', 'description' => 'Создать аккаунт вручную и сразу перейти к настройкам профиля.', 'href' => 'user_add.php', 'allowed' => !empty($PRIV['user_add'])),
	array('label' => 'Добавить новость', 'description' => 'Опубликовать короткое объявление или новость проекта.', 'href' => 'news.php?act=add', 'allowed' => !empty($PRIV['news_add']) || !empty($PRIV['edit_news'])),
	array('label' => 'Редактировать новости', 'description' => 'Открыть список публикаций для правки и снятия с сайта.', 'href' => 'news.php', 'allowed' => !empty($PRIV['news_add']) || !empty($PRIV['edit_news'])),
	array('label' => 'Открыть классы и права', 'description' => 'Проверить роли, разрешения и доступ к админским функциям.', 'href' => 'edit_priv.php', 'allowed' => !empty($PRIV['EDIT_PRIV'])),
	array('label' => 'Открыть жалобы', 'description' => 'Разобрать открытые жалобы на комментарии в профилях.', 'href' => user_wall_reports_href(), 'allowed' => user_wall_reports_can_moderate()),
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
	--admin-accent: #4f7f5a;
	--admin-accent-hover: #416b49;
	max-width: 1320px;
	margin: 16px auto 0;
	padding: 0 18px 28px;
	box-sizing: border-box;
	color: var(--text);
}

.admin-hero,
.admin-tabs,
.admin-card,
.admin-settings-form,
.admin-inline-message {
	border: 1px solid var(--line);
	border-radius: 6px;
	background: var(--surface);
	box-sizing: border-box;
}

.admin-hero {
	display: grid;
	grid-template-columns: minmax(0, 1fr) auto;
	gap: 18px;
	align-items: end;
	padding: 20px 22px;
	margin-bottom: 12px;
}

.admin-hero-kicker {
	margin: 0 0 5px;
	color: var(--muted);
	font-size: 12px;
	font-weight: 700;
	line-height: 1.2;
	text-transform: uppercase;
}

.admin-hero-title {
	margin: 0;
	color: var(--text);
	font-size: 28px;
	line-height: 1.15;
}

.admin-hero-text {
	max-width: 820px;
	margin: 8px 0 0;
	color: var(--muted);
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
	border: 1px solid var(--line);
	border-radius: 4px;
	background: var(--surface-muted);
	color: var(--muted);
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
	box-shadow: none;
}

.admin-tab-link {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	gap: 7px;
	min-height: 34px;
	padding: 0 12px;
	border: 1px solid transparent;
	border-radius: 4px;
	color: var(--muted);
	font-size: 13px;
	font-weight: 700;
	text-decoration: none;
}

.admin-tab-link:hover {
	border-color: var(--line);
	background: var(--surface-muted);
	color: var(--text);
}

.admin-tab-link-active {
	border-color: var(--text);
	background: var(--text);
	color: var(--surface);
}

.admin-tab-count {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	min-width: 20px;
	height: 20px;
	padding: 0 6px;
	border-radius: 4px;
	background: var(--surface-muted);
	color: var(--muted);
	font-size: 11px;
	font-weight: 800;
	line-height: 1;
}

.admin-tab-link-active .admin-tab-count {
	background: var(--surface);
	color: var(--text);
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

.admin-status-grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
	gap: 10px;
	margin-top: 16px;
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
.admin-shortcut-card,
.admin-permission-row,
.admin-system-item,
.admin-status-card {
	display: block;
	padding: 14px 16px;
	border: 1px solid var(--line);
	border-radius: 5px;
	background: var(--surface-solid);
	color: inherit;
	text-decoration: none;
	box-sizing: border-box;
}

.admin-stat-card:hover,
.admin-link-card:hover,
.admin-shortcut-card:hover,
.admin-status-card:hover,
.admin-permission-row:hover {
	border-color: var(--line-strong);
	background: var(--surface-muted);
}

.admin-stat-value {
	display: block;
	color: var(--text);
	font-size: 26px;
	font-weight: 800;
	line-height: 1;
}

.admin-stat-label,
.admin-link-text,
.admin-action-text,
.admin-shortcut-text,
.admin-card-text,
.admin-settings-text,
.admin-permission-text,
.admin-settings-help {
	color: var(--muted);
	font-size: 13px;
	line-height: 1.5;
}

.admin-stat-label,
.admin-link-text,
.admin-action-text,
.admin-shortcut-text,
.admin-permission-text {
	display: block;
	margin-top: 7px;
}

.admin-card-title,
.admin-settings-title {
	margin: 0 0 7px;
	color: var(--text);
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
.admin-shortcut-title,
.admin-permission-name {
	color: var(--text);
	font-size: 15px;
	font-weight: 800;
	line-height: 1.3;
}

.admin-action-card {
	padding: 0;
	background: var(--surface-solid);
	overflow: hidden;
}

.admin-action-form {
	display: block;
	padding: 14px 16px;
}

.admin-action-button,
.admin-settings-submit {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	min-height: 36px;
	padding: 0 13px;
	border: 1px solid var(--admin-accent);
	border-radius: 4px;
	background: var(--admin-accent);
	color: #fff;
	font-size: 13px;
	font-weight: 800;
	line-height: 1;
	text-decoration: none;
	cursor: pointer;
}

.admin-action-button:hover,
.admin-settings-submit:hover {
	border-color: var(--admin-accent-hover);
	background: var(--admin-accent-hover);
	color: #fff;
}

.admin-secondary-link {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	min-height: 36px;
	padding: 0 12px;
	border: 1px solid var(--line);
	border-radius: 4px;
	background: var(--surface-solid);
	color: var(--text);
	font-size: 13px;
	font-weight: 800;
	line-height: 1;
	text-decoration: none;
}

.admin-secondary-link:hover {
	border-color: var(--line-strong);
	background: var(--surface-muted);
	color: var(--text);
}

.admin-shortcut-grid {
	margin-top: 14px;
}

.admin-shortcut-card {
	min-height: 90px;
}

.admin-status-card {
	padding: 12px 14px;
}

.admin-status-value {
	display: block;
	color: var(--text);
	font-size: 22px;
	font-weight: 800;
	line-height: 1;
}

.admin-status-label {
	display: block;
	margin-top: 6px;
	color: var(--muted);
	font-size: 12px;
	font-weight: 700;
	line-height: 1.35;
}

.admin-settings-field {
	display: grid;
	gap: 7px;
}

.admin-settings-label {
	color: var(--text);
	font-size: 13px;
	font-weight: 800;
}

.admin-settings-help {
	margin: -2px 0 0;
}

.admin-settings-input,
.admin-settings-checkbox-row {
	width: 100%;
	border: 1px solid var(--line-strong);
	border-radius: 4px;
	background: var(--surface-solid);
	box-sizing: border-box;
}

.admin-settings-input {
	height: 38px;
	padding: 0 10px;
	color: var(--text);
	font-size: 14px;
}

.admin-settings-checkbox-row {
	display: flex;
	align-items: center;
	gap: 9px;
	min-height: 38px;
	padding: 0 10px;
	color: var(--text);
	font-size: 14px;
}

.admin-settings-footer {
	display: flex;
	gap: 8px;
	justify-content: flex-end;
	margin-top: 16px;
}

.admin-empty {
	padding: 16px;
	border: 1px dashed var(--line-strong);
	border-radius: 5px;
	background: var(--surface-muted);
	color: var(--muted);
	font-size: 14px;
	line-height: 1.5;
}

.admin-system-label {
	color: var(--muted);
	font-size: 11px;
	font-weight: 800;
	text-transform: uppercase;
}

.admin-system-value {
	margin-top: 6px;
	color: var(--text);
	font-size: 13px;
	line-height: 1.45;
	word-break: break-word;
}

.admin-system-path {
	font-family: Consolas, Monaco, monospace;
	font-size: 12px;
}

.admin-dashboard .lt-admin-table {
	border-color: var(--line);
	background: var(--surface);
}

.admin-dashboard .lt-admin-table th,
.admin-dashboard .lt-admin-table td {
	border-bottom-color: var(--line);
}

.admin-dashboard .lt-admin-table th {
	background: var(--surface-muted);
	color: var(--muted);
}

.admin-dashboard .lt-admin-table td {
	color: var(--text);
}

.admin-dashboard .lt-admin-table a {
	color: var(--text);
	font-weight: 700;
	text-decoration: underline;
	text-decoration-color: var(--line-strong);
	text-underline-offset: 3px;
}

.admin-dashboard .lt-admin-table a:hover {
	color: var(--admin-accent-hover);
	text-decoration-color: var(--admin-accent-hover);
}

.admin-card .admin-settings-form {
	padding: 0;
	margin: 16px 0 0;
	border: 0;
	background: transparent;
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

	<?php if (lt_torrent_can_moderate($USER)) { ?>
	<section class='admin-card'>
		<h2 class='admin-card-title'>Раздачи на модерации</h2>
		<p class='admin-card-text'>Сводка по soft status. Клик по статусу открывает очередь с готовым фильтром.</p>
		<div class='admin-status-grid'>
			<?php foreach (array('pending', 'need_fix', 'hidden', 'rejected', 'approved', 'deleted') as $statusKey) { ?>
			<a class='admin-status-card' href='admin.php?tab=moderation&amp;section=torrents&amp;status=<?=$statusKey;?>'>
				<span class='admin-status-value'><?=number_format((int) ($torrentModerationCounts[$statusKey] ?? 0));?></span>
				<span class='admin-status-label'><?=htmlspecialchars(lt_torrent_status_label($statusKey), ENT_QUOTES, 'UTF-8');?></span>
			</a>
			<?php } ?>
		</div>
	</section>
	<?php } ?>

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
			<a class='admin-shortcut-card' href='<?=$shortcut['href'];?>'>
				<span class='admin-shortcut-title'><?=htmlspecialchars($shortcut['label'], ENT_QUOTES, 'UTF-8');?></span>
				<span class='admin-shortcut-text'><?=htmlspecialchars($shortcut['description'], ENT_QUOTES, 'UTF-8');?></span>
			</a>
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
	<?php } elseif ($activeTab === 'moderation' && $activeSection === 'log' && lt_torrent_can_moderate($USER)) { ?>
	<?php
	$logFilters = array(
		'action' => trim((string) ($_GET['action'] ?? '')),
		'moderator_id' => (int) ($_GET['moderator_id'] ?? 0),
		'target_type' => trim((string) ($_GET['target_type'] ?? '')),
		'target_id' => (int) ($_GET['target_id'] ?? 0),
		'date_from' => trim((string) ($_GET['date_from'] ?? '')),
		'date_to' => trim((string) ($_GET['date_to'] ?? '')),
		'limit' => 100,
	);
	$logRows = lt_moderation_log_fetch($logFilters);
	$logActions = admin_moderation_log_actions();
	$logModerators = admin_moderation_log_moderators();
	?>
	<section class='admin-card'>
		<h2 class='admin-card-title'>Журнал модерации</h2>
		<p class='admin-card-text'>Записываются действия модераторов по раздачам: старый и новый статус, причина, IP и время.</p>
		<form class='admin-settings-form' method='get' action='admin.php' style='margin-top:16px;'>
			<input type='hidden' name='tab' value='moderation'>
			<input type='hidden' name='section' value='log'>
			<div class='admin-settings-grid'>
				<div class='admin-settings-field'>
					<label class='admin-settings-label' for='log-action'>Действие</label>
					<select class='admin-settings-input' id='log-action' name='action'>
						<option value=''>Все действия</option>
						<?php foreach ($logActions as $actionOption) { ?>
						<option value='<?=htmlspecialchars($actionOption, ENT_QUOTES, 'UTF-8');?>'<?=($logFilters['action'] === $actionOption ? ' selected' : '');?>><?=htmlspecialchars(lt_moderation_log_action_label($actionOption), ENT_QUOTES, 'UTF-8');?></option>
						<?php } ?>
					</select>
				</div>
				<div class='admin-settings-field'>
					<label class='admin-settings-label' for='log-moderator'>Модератор</label>
					<select class='admin-settings-input' id='log-moderator' name='moderator_id'>
						<option value='0'>Все модераторы</option>
						<?php foreach ($logModerators as $moderatorRow) { ?>
						<option value='<?=(int) $moderatorRow['id'];?>'<?=((int) $logFilters['moderator_id'] === (int) $moderatorRow['id'] ? ' selected' : '');?>><?=htmlspecialchars((string) $moderatorRow['name'], ENT_QUOTES, 'UTF-8');?></option>
						<?php } ?>
					</select>
				</div>
				<div class='admin-settings-field'>
					<label class='admin-settings-label' for='log-target-type'>Тип объекта</label>
					<input class='admin-settings-input' id='log-target-type' type='text' name='target_type' value='<?=htmlspecialchars($logFilters['target_type'], ENT_QUOTES, 'UTF-8');?>' placeholder='torrent'>
				</div>
				<div class='admin-settings-field'>
					<label class='admin-settings-label' for='log-target-id'>ID объекта</label>
					<input class='admin-settings-input' id='log-target-id' type='number' min='0' name='target_id' value='<?=($logFilters['target_id'] > 0 ? (int) $logFilters['target_id'] : '');?>'>
				</div>
				<div class='admin-settings-field'>
					<label class='admin-settings-label' for='log-date-from'>Дата с</label>
					<input class='admin-settings-input' id='log-date-from' type='date' name='date_from' value='<?=htmlspecialchars($logFilters['date_from'], ENT_QUOTES, 'UTF-8');?>'>
				</div>
				<div class='admin-settings-field'>
					<label class='admin-settings-label' for='log-date-to'>Дата по</label>
					<input class='admin-settings-input' id='log-date-to' type='date' name='date_to' value='<?=htmlspecialchars($logFilters['date_to'], ENT_QUOTES, 'UTF-8');?>'>
				</div>
			</div>
			<div class='admin-settings-footer'>
				<a class='admin-secondary-link' href='admin.php?tab=moderation&amp;section=log'>Сбросить</a>
				<button class='admin-settings-submit' type='submit'>Фильтровать</button>
			</div>
		</form>
		<?php if (!$logRows) { ?>
		<div class='admin-empty' style='margin-top:16px;'>Записей журнала не найдено.</div>
		<?php } else { ?>
		<div class='lt-admin-table-wrap' style='margin-top:16px;'>
			<table class='lt-admin-table'>
				<thead>
					<tr>
						<th>Дата</th>
						<th>Модератор</th>
						<th>Действие</th>
						<th>Объект</th>
						<th>Старое значение</th>
						<th>Новое значение</th>
						<th>Причина</th>
						<th>IP</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($logRows as $logRow) { ?>
					<?php
					$targetUrl = lt_moderation_log_target_url($logRow['target_type'] ?? '', (int) ($logRow['target_id'] ?? 0));
					$targetText = trim((string) ($logRow['target_type'] ?? '')).' #'.(int) ($logRow['target_id'] ?? 0);
					?>
					<tr>
						<td><?=htmlspecialchars(convent_date((string) ($logRow['created_at'] ?? '')), ENT_QUOTES, 'UTF-8');?></td>
						<td><?=htmlspecialchars((string) ($logRow['moderator_name'] ?? ('#'.(int) ($logRow['moderator_id'] ?? 0))), ENT_QUOTES, 'UTF-8');?></td>
						<td><?=htmlspecialchars(lt_moderation_log_action_label($logRow['action'] ?? ''), ENT_QUOTES, 'UTF-8');?></td>
						<td>
							<?php if ($targetUrl !== '') { ?>
							<a href='<?=htmlspecialchars($targetUrl, ENT_QUOTES, 'UTF-8');?>'><?=htmlspecialchars($targetText, ENT_QUOTES, 'UTF-8');?></a>
							<?php } else { ?>
							<?=htmlspecialchars($targetText, ENT_QUOTES, 'UTF-8');?>
							<?php } ?>
						</td>
						<td><?=htmlspecialchars((string) ($logRow['old_value'] ?? ''), ENT_QUOTES, 'UTF-8');?></td>
						<td><?=htmlspecialchars((string) ($logRow['new_value'] ?? ''), ENT_QUOTES, 'UTF-8');?></td>
						<td><?=htmlspecialchars((string) ($logRow['reason'] ?? ''), ENT_QUOTES, 'UTF-8');?></td>
						<td><?=htmlspecialchars((string) ($logRow['ip'] ?? ''), ENT_QUOTES, 'UTF-8');?></td>
					</tr>
					<?php } ?>
				</tbody>
			</table>
		</div>
		<?php } ?>
	</section>
	<?php } elseif ($activeTab === 'moderation' && $activeSection === 'torrents' && lt_torrent_can_moderate($USER)) { ?>
	<?php
	$moderationStatus = trim((string) ($_GET['status'] ?? 'pending'));
	if (!in_array($moderationStatus, admin_torrent_moderation_statuses(), true)) {
		$moderationStatus = 'pending';
	}
	$moderationRows = admin_torrent_moderation_items($moderationStatus);
	$moderationActions = array(
		'approve' => 'Одобрить',
		'need_fix' => 'На доработку',
		'reject' => 'Отклонить',
		'hide' => 'Скрыть',
		'restore' => 'Восстановить',
		'soft_delete' => 'Удалить мягко',
	);
	?>
	<section class='admin-card'>
		<h2 class='admin-card-title'>Модерация раздач</h2>
		<p class='admin-card-text'>Очередь работает через soft status: torrent-файлы, infohash и связанные данные не меняются.</p>
		<div class='admin-tabs' style='position:static;margin-top:16px;'>
			<?php foreach (admin_torrent_moderation_statuses() as $statusOption) { ?>
			<?php $statusCount = ($statusOption === 'all' ? array_sum($torrentModerationCounts) : (int) ($torrentModerationCounts[$statusOption] ?? 0)); ?>
			<a class='admin-tab-link<?=($moderationStatus === $statusOption ? ' admin-tab-link-active' : '');?>' href='admin.php?tab=moderation&amp;section=torrents&amp;status=<?=$statusOption;?>'>
				<span><?=($statusOption === 'all' ? 'Все' : htmlspecialchars(lt_torrent_status_label($statusOption), ENT_QUOTES, 'UTF-8'));?></span>
				<span class='admin-tab-count'><?=number_format((int) $statusCount);?></span>
			</a>
			<?php } ?>
		</div>
		<?php if (!$moderationRows) { ?>
		<div class='admin-empty' style='margin-top:16px;'>В этом фильтре нет раздач.</div>
		<?php } else { ?>
		<div class='lt-admin-table-wrap' style='margin-top:16px;'>
			<table class='lt-admin-table'>
				<thead>
					<tr>
						<th>ID</th>
						<th>Название</th>
						<th>Автор</th>
						<th>Категория</th>
						<th>Размер</th>
						<th>Дата загрузки</th>
						<th>Статус</th>
						<th>Причина</th>
						<th>Проверил</th>
						<th>Дата проверки</th>
						<th>Действия</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($moderationRows as $row) { ?>
					<?php $rowStatus = lt_torrent_status_normalize($row['status'] ?? 'approved'); ?>
					<tr>
						<td><span class='lt-admin-code'>#<?=(int) $row['id'];?></span></td>
						<td><a href='details.php?id=<?=(int) $row['id'];?>'><?=htmlspecialchars((string) $row['name'], ENT_QUOTES, 'UTF-8');?></a></td>
						<td><?=htmlspecialchars((string) ($row['owner_name'] ?? 'Неизвестно'), ENT_QUOTES, 'UTF-8');?></td>
						<td><?=htmlspecialchars((string) ($row['category_name'] ?? 'Без категории'), ENT_QUOTES, 'UTF-8');?></td>
						<td><?=htmlspecialchars(mksize((float) ($row['size'] ?? 0)), ENT_QUOTES, 'UTF-8');?></td>
						<td><?=htmlspecialchars(convent_date((string) ($row['added'] ?? '')), ENT_QUOTES, 'UTF-8');?></td>
						<td><?=lt_torrent_status_badge($rowStatus);?></td>
						<td><?=htmlspecialchars((string) ($row['status_reason'] ?? ''), ENT_QUOTES, 'UTF-8');?></td>
						<td><?=htmlspecialchars((string) ($row['reviewer_name'] ?? ''), ENT_QUOTES, 'UTF-8');?></td>
						<td><?=(!empty($row['reviewed_at']) ? htmlspecialchars(convent_date((string) $row['reviewed_at']), ENT_QUOTES, 'UTF-8') : '');?></td>
						<td>
							<form method='post' action='admin.php?tab=moderation&amp;section=torrents' class='torrent-moderation-form'>
								<input type='hidden' name='tab' value='moderation'>
								<input type='hidden' name='admin_action' value='torrent_moderation'>
								<input type='hidden' name='torrent_id' value='<?=(int) $row['id'];?>'>
								<input type='hidden' name='filter_status' value='<?=htmlspecialchars($moderationStatus, ENT_QUOTES, 'UTF-8');?>'>
								<?=lt_csrf_input('admin_dashboard');?>
								<select name='moderation_action' class='admin-settings-input' style='min-width:150px;margin-bottom:6px;'>
									<?php foreach ($moderationActions as $actionKey => $actionLabel) { ?>
									<option value='<?=$actionKey;?>'><?=$actionLabel;?></option>
									<?php } ?>
								</select>
								<textarea name='status_reason' class='admin-settings-input' style='height:58px;min-width:220px;margin-bottom:6px;' placeholder='Причина или комментарий'></textarea>
								<button class='admin-action-button' type='submit'>Применить</button>
							</form>
						</td>
					</tr>
					<?php } ?>
				</tbody>
			</table>
		</div>
		<?php } ?>
	</section>
	<?php } elseif (in_array($activeTab, array('content', 'users', 'moderation', 'monitoring'), true)) { ?>
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
	<section class='admin-card'>
		<h2 class='admin-card-title'><?=$section['title'];?></h2>
		<p class='admin-card-text'><?=$section['description'];?></p>
		<?php if (!$visibleItems) { ?>
		<div class='admin-empty' style='margin-top:18px;'>Для вашей роли нет доступных разделов.</div>
		<?php } else { ?>
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
		<?php } ?>
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
	<?php } elseif ($activeTab === 'migrations') { ?>
	<?php
	lt_migrations_ensure_table();
	$allMigrations = lt_migrations_full_list();
	$dryRunResult = $_SESSION['admin_migrations_dryrun'] ?? null;
	$appliedCount = $_SESSION['admin_migrations_applied'] ?? null;
	$errors = $_SESSION['admin_migrations_errors'] ?? null;
	unset($_SESSION['admin_migrations_dryrun'], $_SESSION['admin_migrations_applied'], $_SESSION['admin_migrations_errors']);
	?>
	<section class='admin-card'>
		<h2 class='admin-card-title'>Управление миграциями БД</h2>
		<p class='admin-card-text'>Отслеживание, проверка и применение SQL-миграций. Все миграции находятся в папке database/migrations и сортируются по названию.</p>

		<div class='admin-inline-message admin-inline-message-error' style='margin-top:16px;'>
			<strong>⚠ Важно:</strong> перед применением миграций обязательно сделайте резервную копию базы данных!
		</div>

		<?php if ($dryRunResult) { ?>
		<div class='admin-inline-message admin-inline-message-success' style='margin-top:12px;'>
			<strong>Результат сухого прогона:</strong><br>
			Всего миграций: <?=$dryRunResult['total'];?><br>
			Pending: <?=$dryRunResult['pending'];?> | Changed: <?=$dryRunResult['changed'];?> | Failed: <?=$dryRunResult['failed'];?> | Applied: <?=$dryRunResult['applied'];?>
		</div>
		<?php } ?>

		<?php if ($appliedCount !== null) { ?>
		<div class='admin-inline-message admin-inline-message-success' style='margin-top:12px;'>
			<strong>Применено миграций:</strong> <?=$appliedCount;?>
			<?php if (!empty($errors)) { ?>
			<br><strong>Ошибки:</strong>
			<ul style='margin:8px 0 0; padding-left:20px;'>
				<?php foreach ($errors as $error) { ?>
				<li><?=htmlspecialchars($error, ENT_QUOTES, 'UTF-8');?></li>
				<?php } ?>
			</ul>
			<?php } ?>
		</div>
		<?php } ?>

		<h3 style='margin-top:20px; margin-bottom:10px; font-size:16px; font-weight:800;'>Список миграций</h3>

		<?php if (!$allMigrations) { ?>
		<div class='admin-empty'>В папке database/migrations нет файлов миграций.</div>
		<?php } else { ?>
		<form method='post' action='admin.php' style='margin-top:12px;'>
			<input type='hidden' name='tab' value='migrations'>
			<input type='hidden' name='admin_action' value='migrations'>
			<?=lt_csrf_input('admin_dashboard');?>

			<div class='lt-admin-table-wrap' style='margin-bottom:16px;'>
				<table class='lt-admin-table' style='font-size:12px;'>
					<thead>
						<tr>
							<th style='width:30px;'><input type='checkbox' id='select-all-migrations' style='cursor:pointer;'></th>
							<th>Файл</th>
							<th style='width:120px;'>Статус</th>
							<th style='width:100px;'>Checksum</th>
							<th style='width:70px;'>Batch</th>
							<th style='width:140px;'>Дата применения</th>
							<th style='width:80px;'>Время (мс)</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($allMigrations as $migration) { ?>
						<?php
						$statusBg = 'pending' === $migration['status'] ? '#e7f3e7' : ('applied' === $migration['status'] ? '#e7f0e7' : ('changed' === $migration['status'] ? '#fff3e7' : '#ffe7e7'));
						$statusLabel = array(
							'pending' => 'Ожидание',
							'applied' => 'Применена',
							'failed' => 'Ошибка',
							'changed' => 'Изменена',
						)[$migration['status']] ?? $migration['status'];
						?>
						<tr>
							<td>
								<?php if (in_array($migration['status'], array('pending', 'changed'), true)) { ?>
								<input type='checkbox' name='selected_migrations[]' value='<?=htmlspecialchars($migration['name'], ENT_QUOTES, 'UTF-8');?>' class='migration-checkbox'>
								<?php } ?>
							</td>
							<td>
								<code style='font-size:11px; word-break:break-all;'><?=htmlspecialchars($migration['name'], ENT_QUOTES, 'UTF-8');?></code>
							</td>
							<td style='background-color:<?=$statusBg;?>;'>
								<?=htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8');?>
							</td>
							<td>
								<code style='font-size:10px;'><?=htmlspecialchars(substr($migration['checksum'], 0, 8), ENT_QUOTES, 'UTF-8');?></code>
							</td>
							<td style='text-align:center;'>
								<?=($migration['batch'] > 0 ? $migration['batch'] : '—');?>
							</td>
							<td>
								<?=($migration['applied_at'] ? htmlspecialchars(convent_date($migration['applied_at']), ENT_QUOTES, 'UTF-8') : '—');?>
							</td>
							<td style='text-align:right;'>
								<?=($migration['execution_time_ms'] > 0 ? $migration['execution_time_ms'] : '—');?>
							</td>
						</tr>
						<?php if (!empty($migration['error_message']) && 'failed' === $migration['status']) { ?>
						<tr>
							<td colspan='7' style='padding:8px 12px; background:#fff5f5; color:#c33; font-size:11px; border-left:3px solid #c33;'>
								<strong>Ошибка:</strong> <?=htmlspecialchars($migration['error_message'], ENT_QUOTES, 'UTF-8');?>
							</td>
						</tr>
						<?php } ?>
						<?php } ?>
					</tbody>
				</table>
			</div>

			<div class='admin-settings-footer' style='justify-content:flex-start; gap:8px; margin-top:16px;'>
				<button class='admin-action-button' type='submit' name='migrations_action' value='dry_run' style='background:#666; border-color:#666;'>
					Dry Run
				</button>
				<button class='admin-action-button' type='submit' name='migrations_action' value='apply' style='background:#0a7; border-color:#0a7;' onclick='return confirm("Применить выбранные миграции?\n\nОбязательно сделайте backup перед этим!");'>
					Применить выбранные
				</button>
			</div>
		</form>
		<?php } ?>

		<h3 style='margin-top:20px; margin-bottom:10px; font-size:16px; font-weight:800;'>Информация о системе</h3>
		<div class='admin-system-grid'>
			<div class='admin-system-item'>
				<div class='admin-system-label'>Папка миграций</div>
				<div class='admin-system-value'><span class='admin-system-path'><?=htmlspecialchars(lt_migrations_path(), ENT_QUOTES, 'UTF-8');?></span></div>
			</div>
			<div class='admin-system-item'>
				<div class='admin-system-label'>Таблица schema_migrations</div>
				<div class='admin-system-value'><?=(lt_table_exists('schema_migrations') ? 'создана' : 'не найдена');?></div>
			</div>
		</div>
	</section>

	<script>
	document.addEventListener('DOMContentLoaded', function() {
		var selectAllCheckbox = document.getElementById('select-all-migrations');
		var migrationCheckboxes = document.querySelectorAll('.migration-checkbox');

		if (selectAllCheckbox) {
			selectAllCheckbox.addEventListener('change', function() {
				migrationCheckboxes.forEach(function(checkbox) {
					checkbox.checked = selectAllCheckbox.checked;
				});
			});
		}
	});
	</script>
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
