<?php

if (!function_exists('lt_torrent_category_name_from_list')) {
    function lt_torrent_category_name_from_list($categories, $categoryId)
    {
        foreach ((array) $categories as $category) {
            if ((int) ($category['id'] ?? 0) !== (int) $categoryId) {
                continue;
            }

            return trim((string) ($category['name'] ?? ''));
        }

        return '';
    }
}

if (!function_exists('lt_torrent_description_template_textarea_labels')) {
    function lt_torrent_description_template_textarea_labels()
    {
        return array('Описание', 'В ролях', 'Треклист', 'Системные требования');
    }
}

if (!function_exists('lt_torrent_description_template_field_type')) {
    function lt_torrent_description_template_field_type($label)
    {
        return (in_array(trim((string) $label), lt_torrent_description_template_textarea_labels(), true) ? 'textarea' : 'text');
    }
}

if (!function_exists('lt_torrent_description_manual_fields')) {
    function lt_torrent_description_manual_fields($categoryNameOrKey, $values = array())
    {
        $template = lt_torrent_description_template($categoryNameOrKey);
        $items = (array) ($template['items'] ?? array());
        $result = array();
        $values = (is_array($values) ? $values : array());

        foreach ($items as $item) {
            $type = trim((string) ($item['type'] ?? 'field'));
            $label = trim((string) ($item['label'] ?? ''));
            $auto = trim((string) ($item['auto'] ?? ''));

            if ($label === '' || $type === 'section' || $auto !== '') {
                continue;
            }

            $result[] = array(
                'label' => $label,
                'field_type' => lt_torrent_description_template_field_type($label),
                'value' => (string) ($values[$label] ?? ''),
            );
        }

        return $result;
    }
}

if (!function_exists('lt_torrent_description_primary_label')) {
    function lt_torrent_description_primary_label($categoryNameOrKey)
    {
        $template = lt_torrent_description_template($categoryNameOrKey);
        $items = (array) ($template['items'] ?? array());

        foreach ($items as $item) {
            $type = trim((string) ($item['type'] ?? 'field'));
            $label = trim((string) ($item['label'] ?? ''));
            if ($type === 'field' && $label === 'Описание') {
                return $label;
            }
        }

        return '';
    }
}

if (!function_exists('lt_torrent_description_build_with_auto')) {
    function lt_torrent_description_build_with_auto($categoryNameOrKey, $templateValues, $autoValues = array())
    {
        $template = lt_torrent_description_template($categoryNameOrKey);
        $items = (array) ($template['items'] ?? array());
        $templateValues = (is_array($templateValues) ? $templateValues : array());
        $autoValues = (is_array($autoValues) ? $autoValues : array());
        $lines = array();

        foreach ($items as $item) {
            $type = trim((string) ($item['type'] ?? 'field'));
            $label = trim((string) ($item['label'] ?? ''));
            $auto = trim((string) ($item['auto'] ?? ''));
            if ($label === '') {
                continue;
            }

            if ($type === 'section') {
                if ($lines && end($lines) !== '') {
                    $lines[] = '';
                }

                $lines[] = '[u]'.$label.'[/u]';
                continue;
            }

            $value = '';
            if ($auto !== '' && isset($autoValues[$auto])) {
                $value = trim((string) $autoValues[$auto]);
            } else {
                $value = trim((string) ($templateValues[$label] ?? ''));
            }

            if (strpos($value, "\n") !== false) {
                $lines[] = '[b]'.$label.':[/b]'.($value !== '' ? "\n".$value : '');
                continue;
            }

            $lines[] = '[b]'.$label.':[/b]'.($value !== '' ? ' '.$value : '');
        }

        return trim(implode("\n", $lines));
    }
}

if (!function_exists('lt_torrent_description_parse_sections')) {
    function lt_torrent_description_parse_sections($text)
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
}

if (!function_exists('lt_torrent_metadata_values_from_row')) {
    function lt_torrent_metadata_values_from_row($torrent, $schema)
    {
        $result = array();

        foreach ((array) $schema as $group => $definition) {
            $column = trim((string) ($definition['column'] ?? ''));
            if ($column === '') {
                $result[$group] = array();
                continue;
            }

            if (($definition['input'] ?? '') === 'radio') {
                $result[$group] = trim((string) ($torrent[$column] ?? ''));
                continue;
            }

            $result[$group] = lt_torrent_metadata_parse($group, $torrent[$column] ?? '');
        }

        return $result;
    }
}
