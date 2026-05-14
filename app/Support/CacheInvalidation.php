<?php

if (!function_exists('lt_cache_forget_legacy')) {
    function lt_cache_forget_legacy($key)
    {
        $key = trim((string) $key);
        if ($key === '') {
            return;
        }

        if (function_exists('lt_cache_delete')) {
            lt_cache_delete($key);
        }

        $memcached = ($GLOBALS['memcached'] ?? null);
        if (is_object($memcached) && method_exists($memcached, 'delete')) {
            $memcached->delete($key);
        }
    }
}

if (!function_exists('lt_cache_invalidate_news')) {
    function lt_cache_invalidate_news()
    {
        lt_cache_forget_legacy(lt_cache_key_news_list());
        lt_cache_forget_legacy(lt_cache_key_sidebar_news_all());
    }
}
