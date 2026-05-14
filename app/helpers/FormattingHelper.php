<?php

if (!function_exists('lt_format_date_label')) {
    function lt_format_date_label($date)
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
}

if (!function_exists('lt_format_comment_html')) {
    function lt_format_comment_html($text)
    {
        $html = trim((string) format_comment((string) $text));
        $html = preg_replace('~^(?:<br\s*/?>\s*)+|(?:\s*<br\s*/?>)+$~i', '', $html);

        return $html;
    }
}

if (!function_exists('lt_format_label_key')) {
    function lt_format_label_key($label)
    {
        $label = strip_tags((string) $label);
        $label = str_replace(':', '', $label);
        $label = preg_replace('/\s+/u', ' ', trim($label));

        return (function_exists('mb_strtolower') ? mb_strtolower($label, 'UTF-8') : strtolower($label));
    }
}
