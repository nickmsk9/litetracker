<?php
/*
===================================================================
LiteTracker Source
===================================================================
by Nick
-------------------------------------------------------------------
Назначение: Admin helper functions (audit log, settings, permissions)
===================================================================
*/

/**
 * Read a single setting from the site_settings table (cached).
 */
function lt_setting($key, $default = null)
{
	$cached = lt_cache_get('all', 'site_settings');
	if ($cached === false) {
		$cached = lt_setting_preload();
	}
	return array_key_exists($key, $cached) ? $cached[$key] : $default;
}

/**
 * Update (or insert) a setting in site_settings and invalidate the cache.
 */
function lt_setting_set($key, $value, $admin_id = 0)
{
	global $db;

	$result = $db->pquery(
		"INSERT INTO site_settings (setting_key, setting_value, updated_by) VALUES (?, ?, ?)
		 ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_by = VALUES(updated_by), updated_at = NOW()",
		'ssi',
		[$key, $value, (int) $admin_id],
		false
	);

	if ($result === false) {
		return false;
	}

	lt_cache_invalidate_namespace('site_settings');
	return true;
}

/**
 * Load all site_settings rows into cache and return them as an array keyed by setting_key.
 */
function lt_setting_preload()
{
	global $db;

	$settings = [];
	try {
		$result = $db->query("SELECT setting_key, setting_value FROM site_settings");
		if ($result) {
			while ($row = $db->get_row($result)) {
				$settings[$row['setting_key']] = $row['setting_value'];
			}
			$db->free($result);
		}
	} catch (\Throwable $e) {
		// Table may not exist yet; return empty gracefully
		return [];
	}

	lt_cache_set('all', $settings, 300, 'site_settings');
	return $settings;
}

/**
 * Write a row to admin_audit_log. Silently fails if the table doesn't exist.
 */
function lt_admin_audit_log($action, $module, $target_type = '', $target_id = 0, $old_value = null, $new_value = null)
{
	global $db;

	$admin_id   = (int) ($GLOBALS['USER']['id'] ?? 0);
	$ip         = function_exists('getip') ? (string) getip() : (string) ($_SERVER['REMOTE_ADDR'] ?? '');
	$user_agent = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');

	if ($old_value !== null) {
		$old_value = mb_substr((string) $old_value, 0, 65000);
	}
	if ($new_value !== null) {
		$new_value = mb_substr((string) $new_value, 0, 65000);
	}

	$db->pquery(
		"INSERT INTO admin_audit_log
		 (admin_id, action, module, target_type, target_id, old_value, new_value, ip, user_agent, created_at)
		 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
		'isssiisss',
		[$admin_id, $action, $module, $target_type, (int) $target_id, $old_value, $new_value, $ip, $user_agent],
		false
	);
}

/**
 * Central permission check for admin actions.
 */
function admin_can($action)
{
	$PRIV = $GLOBALS['PRIV'] ?? [];
	$USER = $GLOBALS['USER'] ?? [];

	if (empty($USER)) {
		return false;
	}

	switch ($action) {
		case 'superadmin':
			return !empty($PRIV['EDIT_PRIV']);
		case 'users':
			return !empty($PRIV['EDIT_PRIV']) || !empty($PRIV['user_add']) || !empty($PRIV['setting_user']) || !empty($PRIV['users_view']);
		case 'torrents':
			return !empty($PRIV['EDIT_PRIV']) || !empty($PRIV['edit_release']);
		case 'categories':
			return !empty($PRIV['EDIT_PRIV']) || !empty($PRIV['cats']);
		case 'comments':
			return !empty($PRIV['EDIT_PRIV']) || !empty($PRIV['comments_edit']) || !empty($PRIV['comments_delete']);
		case 'news':
			return !empty($PRIV['EDIT_PRIV']) || !empty($PRIV['news_add']) || !empty($PRIV['edit_news']);
		case 'faq':
			return !empty($PRIV['EDIT_PRIV']) || !empty($PRIV['faq_moderate']);
		case 'cache':
			return !empty($PRIV['EDIT_PRIV']);
		case 'settings':
			return !empty($PRIV['EDIT_PRIV']);
		case 'ads':
			return !empty($PRIV['EDIT_PRIV']);
		case 'reports':
			return !empty($PRIV['EDIT_PRIV']) || !empty($PRIV['edit_release']) || !empty($PRIV['comments_edit']);
		case 'database':
			return !empty($PRIV['EDIT_PRIV']);
		case 'system':
			return !empty($PRIV['EDIT_PRIV']) || !empty($PRIV['sessions_view']);
		case 'maintenance':
			return !empty($PRIV['EDIT_PRIV']);
		case 'sessions':
			return !empty($PRIV['EDIT_PRIV']) || !empty($PRIV['sessions_view']);
		default:
			return false;
	}
}

/**
 * Require a permission or terminate with an error.
 */
function admin_require($action)
{
	if (empty($GLOBALS['USER'])) {
		err('Ошибка', 'У вас нет прав на это действие.');
	}
	if (!admin_can($action)) {
		err('Ошибка', 'У вас нет прав на это действие.');
	}
}

/**
 * Return a human-readable role label for an admin user.
 */
function lt_admin_role_label($user, $priv)
{
	if (!empty($priv['EDIT_PRIV'])) {
		return 'Суперадминистратор';
	}

	$labels = [
		'edit_release'       => 'Раздачи',
		'cats'               => 'Категории',
		'news_add'           => 'Новости',
		'edit_news'          => 'Редактор новостей',
		'faq_moderate'       => 'FAQ',
		'user_add'           => 'Добавление пользователей',
		'setting_user'       => 'Настройки пользователей',
		'messages'           => 'Сообщения',
		'ip_util'            => 'IP-утилиты',
		'sessions_view'      => 'Сессии',
		'sessions_clear'     => 'Очистка сессий',
		'search_query'       => 'Поисковые запросы',
		'multitracker_accounts' => 'Мультитрекер',
		'comments_edit'      => 'Редактирование комментариев',
		'comments_delete'    => 'Удаление комментариев',
		'users_view'         => 'Просмотр пользователей',
	];

	$roles = [];
	foreach ($labels as $flag => $label) {
		if (!empty($priv[$flag])) {
			$roles[] = $label;
		}
	}

	return $roles ? implode(', ', $roles) : 'Модератор';
}

/**
 * Return an HTML-safe, human-readable description of an audit log target.
 */
function lt_admin_format_target($type, $id)
{
	$id   = (int) $id;
	$type = htmlspecialchars((string) $type, ENT_QUOTES, 'UTF-8');

	$links = [
		'torrent' => '/details.php?id=%d',
		'user'    => '/userdetails.php?id=%d',
		'news'    => '/news.php?id=%d',
		'comment' => '/details.php#comment-%d',
	];

	if ($id > 0 && isset($links[$type])) {
		$url = sprintf($links[$type], $id);
		return '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">' . $type . ' #' . $id . '</a>';
	}

	return $id > 0 ? $type . ' #' . $id : $type;
}

/**
 * Returns true if the site is currently in maintenance mode.
 * Auto-disables if maintenance_ends_at is set and has passed.
 */
function lt_maintenance_mode_active()
{
	if ((int) lt_setting('maintenance_mode', 0) !== 1) {
		return false;
	}

	$ends_at = lt_setting('maintenance_ends_at', '');
	if (!empty($ends_at)) {
		$ends_ts = strtotime($ends_at);
		if ($ends_ts !== false && $ends_ts < time()) {
			lt_setting_set('maintenance_mode', '0');
			return false;
		}
	}

	return true;
}
