<?php

function browse_parse_tags($value)
{
	$result = array();
	$parts = explode(',', (string) $value);

	foreach ($parts as $part) {
		$part = trim((string) $part);
		if ($part !== '') {
			$result[] = $part;
		}
	}

	return array_values(array_unique($result));
}

function browse_bool_param($name, $source = null)
{
	$source = ($source === null ? $_GET : $source);
	$value = $source[$name] ?? '';
	if (is_array($value)) {
		return false;
	}

	$value = trim((string) $value);

	return ($value === '1' || $value === 'true' || $value === 'yes' || $value === 'on');
}

function browse_collect_selected_filters($schema, $source = null)
{
	$source = ($source === null ? $_GET : $source);
	$result = array();

	foreach ($schema as $group => $definition) {
		$key = 'filter_'.$group;
		$values = (isset($source[$key]) && is_array($source[$key]) ? $source[$key] : array());
		$result[$group] = lt_torrent_metadata_normalize_values($group, $values);
	}

	return $result;
}

function browse_apply_filter_conditions(&$where, $schema, $selectedFilters)
{
	global $db;

	foreach ($schema as $group => $definition) {
		$values = (!empty($selectedFilters[$group]) ? $selectedFilters[$group] : array());
		if (!$values) {
			continue;
		}

		$column = 't.'.$definition['column'];
		$parts = array();

		foreach ($values as $value) {
			$safeValue = $db->safesql($value);
			if ($group === 'type') {
				$parts[] = $column." = '".$safeValue."'";
				continue;
			}

			$parts[] = "FIND_IN_SET('".$safeValue."', ".$column.") > 0";
		}

		if ($parts) {
			$where[] = '('.implode(' OR ', $parts).')';
		}
	}
}

function browse_filter_options_split($options, $selectedValues, $limit = 4)
{
	$visible = array();
	$hidden = array();
	$index = 0;

	foreach ((array) $options as $value => $label) {
		$isSelected = in_array($value, (array) $selectedValues, true);

		if ($index < $limit || $isSelected) {
			$visible[$value] = $label;
		} else {
			$hidden[$value] = $label;
		}

		$index++;
	}

	return array($visible, $hidden);
}

function browse_apply_quick_filters(&$where, &$having, &$joins, $quick, $userId)
{
	global $db;

	if (!empty($quick['status'])) {
		$where[] = "t.status = '".$db->safesql($quick['status'])."'";
	}

	if (!empty($quick['with_screens'])) {
		$where[] = "(COALESCE(t.screen_1,'') <> '' OR COALESCE(t.screen_2,'') <> '' OR COALESCE(t.screen_3,'') <> '' OR COALESCE(t.screen_4,'') <> '')";
	}

	if (!empty($quick['completed'])) {
		$where[] = 't.completed > 0';
	}

	if (!empty($quick['freeleech'])) {
		$where[] = "(t.meta_info LIKE '%freeleech%' OR t.tags LIKE '%freeleech%')";
	}

	if (!empty($quick['bookmarked']) && $userId > 0) {
		$joins[] = 'LEFT JOIN books AS bkm ON bkm.id_torrent = t.id AND bkm.id_user = '.(int) $userId;
		$where[] = 'bkm.id IS NOT NULL';
	}

	if (!empty($quick['alive'])) {
		$having[] = 'seeders > 0';
	} elseif (!empty($quick['dead'])) {
		$having[] = 'seeders = 0';
	}
}

function browse_detect_ajax_request($server = null, $query = null)
{
	$server = ($server === null ? $_SERVER : $server);
	$query = ($query === null ? $_GET : $query);

	if (isset($query['ajax']) && (string) $query['ajax'] === '1') {
		return true;
	}

	$requestedWith = strtolower((string) ($server['HTTP_X_REQUESTED_WITH'] ?? ''));

	return ($requestedWith === 'xmlhttprequest');
}

function browse_build_url($overrides = array(), $drop = array(), $source = null)
{
	$params = ($source === null ? $_GET : $source);

	foreach ($drop as $key) {
		unset($params[$key]);
	}

	foreach ($overrides as $key => $value) {
		if ($value === null || $value === '' || $value === array()) {
			unset($params[$key]);
			continue;
		}

		$params[$key] = $value;
	}

	$query = http_build_query($params);

	return 'browse.php'.($query !== '' ? '?'.$query : '');
}
