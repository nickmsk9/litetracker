<?php

// ---------------------------------------------------------------------------
// NEWS / SIDEBAR
// namespace: 'news'
// ---------------------------------------------------------------------------

if (!function_exists('lt_cache_key_sidebar_news_all')) {
    function lt_cache_key_sidebar_news_all()
    {
        return 'sidebar_news_all';
    }
}

if (!function_exists('lt_cache_key_sidebar_news_ns')) {
    function lt_cache_key_sidebar_news_ns()
    {
        return 'news';
    }
}

if (!function_exists('lt_cache_key_news_list')) {
    function lt_cache_key_news_list()
    {
        return 'news_list';
    }
}

if (!function_exists('lt_cache_key_news_ns')) {
    function lt_cache_key_news_ns()
    {
        return 'news';
    }
}

// ---------------------------------------------------------------------------
// USERS
// namespace: 'users'
// ---------------------------------------------------------------------------

if (!function_exists('lt_cache_key_user')) {
    /** @param int $id user ID */
    function lt_cache_key_user($id)
    {
        return 'user:'.(int) $id;
    }
}

if (!function_exists('lt_cache_key_user_ns')) {
    function lt_cache_key_user_ns()
    {
        return 'users';
    }
}

// ---------------------------------------------------------------------------
// SYSTEM (CRON, IP bans)
// namespace: 'sys'
// ---------------------------------------------------------------------------

if (!function_exists('lt_cache_key_cron')) {
    function lt_cache_key_cron()
    {
        return 'cron';
    }
}

if (!function_exists('lt_cache_key_ip_ban')) {
    /** @param string|int $ip ip2long value */
    function lt_cache_key_ip_ban($ip)
    {
        return 'ip_ban:'.(string) $ip;
    }
}

if (!function_exists('lt_cache_key_sys_ns')) {
    function lt_cache_key_sys_ns()
    {
        return 'sys';
    }
}

// ---------------------------------------------------------------------------
// TAGS
// namespace: 'tags'
// ---------------------------------------------------------------------------

if (!function_exists('lt_cache_key_tags_all')) {
    function lt_cache_key_tags_all()
    {
        return 'tags_all';
    }
}

if (!function_exists('lt_cache_key_tags_popular')) {
    /** @param int $limit */
    function lt_cache_key_tags_popular($limit)
    {
        return 'tags_popular:'.(int) $limit;
    }
}

if (!function_exists('lt_cache_key_tags_genre')) {
    /** @param int $cat category ID */
    function lt_cache_key_tags_genre($cat)
    {
        return 'tags_genre:'.(int) $cat;
    }
}

if (!function_exists('lt_cache_key_tags_ns')) {
    function lt_cache_key_tags_ns()
    {
        return 'tags';
    }
}

// ---------------------------------------------------------------------------
// CATEGORIES
// namespace: 'cats'
// ---------------------------------------------------------------------------

if (!function_exists('lt_cache_key_cats')) {
    /**
     * @param int $id 0 = all categories, >0 = single category
     */
    function lt_cache_key_cats($id = 0)
    {
        return 'cats:'.(int) $id;
    }
}

if (!function_exists('lt_cache_key_cats_ns')) {
    function lt_cache_key_cats_ns()
    {
        return 'cats';
    }
}

// ---------------------------------------------------------------------------
// PRIVILEGES
// namespace: 'priv'
// ---------------------------------------------------------------------------

if (!function_exists('lt_cache_key_priv_all')) {
    function lt_cache_key_priv_all()
    {
        return 'priv_all';
    }
}

if (!function_exists('lt_cache_key_priv_class')) {
    /** @param int $class class/privilege ID */
    function lt_cache_key_priv_class($class)
    {
        return 'priv_class:'.(int) $class;
    }
}

if (!function_exists('lt_cache_key_priv_guest')) {
    function lt_cache_key_priv_guest()
    {
        return 'priv_guest_defaults';
    }
}

if (!function_exists('lt_cache_key_priv_ns')) {
    function lt_cache_key_priv_ns()
    {
        return 'priv';
    }
}

// ---------------------------------------------------------------------------
// TORRENTS
// namespace: 'torrents'
// ---------------------------------------------------------------------------

if (!function_exists('lt_cache_key_torrent')) {
    /** @param int $id torrent ID */
    function lt_cache_key_torrent($id)
    {
        return 'torrent:'.(int) $id;
    }
}

if (!function_exists('lt_cache_key_torrents_ns')) {
    function lt_cache_key_torrents_ns()
    {
        return 'torrents';
    }
}

// ---------------------------------------------------------------------------
// EXTERNAL METADATA (movie/series lookups)
// namespace: 'meta'
// ---------------------------------------------------------------------------

if (!function_exists('lt_cache_key_meta_ns')) {
    function lt_cache_key_meta_ns()
    {
        return 'meta';
    }
}

// ---------------------------------------------------------------------------
// SESSION WRITE THROTTLE
// namespace: 'sessions'
// ---------------------------------------------------------------------------

if (!function_exists('lt_cache_key_session_touch')) {
    /** @param string $hash md5 of session_id|user_id */
    function lt_cache_key_session_touch($hash)
    {
        return 'sess_touch:'.(string) $hash;
    }
}

if (!function_exists('lt_cache_key_sessions_ns')) {
    function lt_cache_key_sessions_ns()
    {
        return 'sessions';
    }
}
