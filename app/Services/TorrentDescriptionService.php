<?php

if (!function_exists('lt_torrent_description_service_manual_fields')) {
    function lt_torrent_description_service_manual_fields($categoryNameOrKey, $values = array())
    {
        return lt_torrent_description_manual_fields($categoryNameOrKey, $values);
    }
}

if (!function_exists('lt_torrent_description_service_primary_label')) {
    function lt_torrent_description_service_primary_label($categoryNameOrKey)
    {
        return lt_torrent_description_primary_label($categoryNameOrKey);
    }
}

if (!function_exists('lt_torrent_description_service_build')) {
    function lt_torrent_description_service_build($categoryNameOrKey, $templateValues, $autoValues = array())
    {
        return lt_torrent_description_build_with_auto($categoryNameOrKey, $templateValues, $autoValues);
    }
}

if (!function_exists('lt_torrent_description_service_parse')) {
    function lt_torrent_description_service_parse($text)
    {
        return lt_torrent_description_parse_sections($text);
    }
}
