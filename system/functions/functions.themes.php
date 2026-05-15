<?php
if (!defined('LITETRACKER')) {
	die('Direct access denied.');
}

/**
 * Hardcoded fallback theme list used when the themes DB table is not yet available.
 */
function lt_themes_fallback_list()
{
	return array(
		'default' => array(
			'slug'       => 'default',
			'title'      => 'LiteTracker Default',
			'css_path'   => 'templates/default/css/my.css',
			'is_default' => true,
		),
		'litetracker_2026_minimal' => array(
			'slug'       => 'litetracker_2026_minimal',
			'title'      => 'LiteTracker 2026 Minimal',
			'css_path'   => 'templates/litetracker_2026_minimal/css/theme.css',
			'is_default' => false,
		),
	);
}

/**
 * Returns cached list of active themes from the DB.
 *
 * @return array  Keyed by slug: ['slug' => string, 'title' => string, 'css_path' => string, 'is_default' => bool]
 */
function lt_themes_get_available()
{
	global $db;

	$cacheKey = lt_cache_key_themes_list();
	$cacheNs  = lt_cache_key_themes_ns();

	$themes = lt_cache_get($cacheKey, $cacheNs);
	if (is_array($themes) && !empty($themes)) {
		return $themes;
	}

	if (!function_exists('lt_table_exists') || !lt_table_exists('themes')) {
		return lt_themes_fallback_list();
	}

	$themes = array();
	$result = $db->query("SELECT `slug`, `title`, `css_path`, `is_default` FROM `themes` WHERE `is_active` = 1 ORDER BY `id` ASC", 0);
	if ($result) {
		while ($row = $db->get_row($result)) {
			$slug = trim((string) ($row['slug'] ?? ''));
			if ($slug !== '' && preg_match('/^[a-zA-Z0-9_-]+$/', $slug)) {
				$themes[$slug] = array(
					'slug'       => $slug,
					'title'      => (string) ($row['title'] ?? $slug),
					'css_path'   => (string) ($row['css_path'] ?? ''),
					'is_default' => !empty($row['is_default']),
				);
			}
		}
		$db->free($result);
	}

	if (empty($themes)) {
		$themes = lt_themes_fallback_list();
	}

	lt_cache_set($cacheKey, $themes, 10 * 60, $cacheNs);

	return $themes;
}

/**
 * Returns key => label pairs suitable for <select> option lists in admin.
 */
function lt_themes_get_admin_options()
{
	$options = array();
	foreach (lt_themes_get_available() as $slug => $theme) {
		$options[$slug] = $theme['title'];
	}
	return $options;
}

/**
 * Returns the site-wide default theme slug.
 * Checks config['default_theme'] first, then the DB is_default flag, then config['template'].
 */
function lt_themes_get_default_slug()
{
	global $config;

	$fromConfig = trim((string) ($config['default_theme'] ?? ''));
	if ($fromConfig !== '' && lt_themes_validate_slug($fromConfig) !== '') {
		return $fromConfig;
	}

	foreach (lt_themes_get_available() as $theme) {
		if ($theme['is_default']) {
			$validated = lt_themes_validate_slug($theme['slug']);
			if ($validated !== '') {
				return $validated;
			}
		}
	}

	return trim((string) ($config['template'] ?? 'default')) ?: 'default';
}

/**
 * Validates a theme slug: format check + whitelist check + CSS file existence.
 *
 * @param  string $slug
 * @return string  The slug if valid, or '' if invalid.
 */
function lt_themes_validate_slug($slug)
{
	$slug = trim((string) $slug);
	if ($slug === '') {
		return '';
	}

	// Prevent path traversal: only alphanumerics, hyphens, underscores
	if (!preg_match('/^[a-zA-Z0-9_-]+$/', $slug)) {
		return '';
	}

	// Must be in the DB/fallback whitelist
	$themes = lt_themes_get_available();
	if (!isset($themes[$slug])) {
		return '';
	}

	// CSS file must physically exist
	$cssPath = $themes[$slug]['css_path'];
	// css_path stored in DB is relative to site root; also accept theme.css convention
	if (!is_file($cssPath) && !is_file('templates/'.$slug.'/css/theme.css') && !is_file('templates/'.$slug.'/css/my.css')) {
		return '';
	}

	return $slug;
}

/**
 * Resolves the active theme slug for the current request.
 * Priority: user preference → site default (config/DB) → config['template'] → 'default'
 *
 * @param  array|null $user  The current $USER array (may be null for guests).
 * @return string  A validated, safe theme slug.
 */
function lt_resolve_theme($user)
{
	global $config;

	// 1. User-specific theme preference
	if (is_array($user) && !empty($user['theme_slug'])) {
		$validated = lt_themes_validate_slug($user['theme_slug']);
		if ($validated !== '') {
			return $validated;
		}
	}

	// 2. Site default (from config or DB)
	$siteDefault = lt_themes_get_default_slug();
	if ($siteDefault !== '') {
		$validated = lt_themes_validate_slug($siteDefault);
		if ($validated !== '') {
			return $validated;
		}
	}

	// 3. Config template (for compatibility; must have a CSS file)
	$configTemplate = trim((string) ($config['template'] ?? 'default'));
	if ($configTemplate !== '' && preg_match('/^[a-zA-Z0-9_-]+$/', $configTemplate)) {
		if (is_file('templates/'.$configTemplate.'/css/my.css') || is_file('templates/'.$configTemplate.'/css/theme.css')) {
			return $configTemplate;
		}
	}

	// 4. Final fallback
	return 'default';
}

/**
 * Returns the override CSS URL for the given theme slug, or '' if no override exists.
 * The "override" CSS is at templates/{slug}/css/theme.css (in addition to the base my.css).
 *
 * @param  string $slug
 * @param  string $baseTpl  The base template slug (e.g. 'default').
 * @return string  Relative URL to include, or '' if no override needed.
 */
function lt_themes_override_css_url($slug, $baseTpl)
{
	$slug = trim((string) $slug);
	$baseTpl = trim((string) $baseTpl);

	if ($slug === '' || $slug === $baseTpl) {
		return '';
	}

	if (!preg_match('/^[a-zA-Z0-9_-]+$/', $slug)) {
		return '';
	}

	// Only serve from a known, validated theme
	$themes = lt_themes_get_available();
	if (!isset($themes[$slug])) {
		return '';
	}

	// Check for theme.css (additive override) first
	if (is_file('templates/'.$slug.'/css/theme.css')) {
		return 'templates/'.$slug.'/css/theme.css';
	}

	// Fall back to my.css (full replacement) — no override needed; calling code handles this
	return '';
}

/**
 * Invalidates the themes list cache.
 */
function lt_themes_invalidate_cache()
{
	lt_cache_invalidate_namespace(lt_cache_key_themes_ns());
}
