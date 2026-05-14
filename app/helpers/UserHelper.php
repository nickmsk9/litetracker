<?php

if (!function_exists('lt_user_can_manage_news')) {
    function lt_user_can_manage_news($priv = null)
    {
        if ($priv === null) {
            $priv = ($GLOBALS['PRIV'] ?? array());
        }

        $priv = (is_array($priv) ? $priv : array());

        return !empty($priv['news_add']);
    }
}

if (!function_exists('lt_user_bonus_column')) {
    function lt_user_bonus_column()
    {
        return (lt_column_exists('users', 'bonus') ? 'bonus' : 'voice');
    }
}

if (!function_exists('lt_user_color_html_bridge')) {
    function lt_user_color_html_bridge($class, $username, $user = null)
    {
        return get_user_color((int) $class, (string) $username, $user);
    }
}
