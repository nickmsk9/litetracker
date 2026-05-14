<?php

// ---------------------------------------------------------------------------
// Low-level compatibility helper: delete a key from both the canonical API
// and (if still accessible via global) the raw $memcached global.
// @deprecated Use lt_cache_delete() with the appropriate namespace instead.
// ---------------------------------------------------------------------------
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

// ---------------------------------------------------------------------------
// USER invalidation
// ---------------------------------------------------------------------------

if (!function_exists('lt_cache_invalidate_user')) {
    /** Invalidate cached profile for a single user. */
    function lt_cache_invalidate_user($userId)
    {
        $userId = (int) $userId;
        if ($userId <= 0) {
            return;
        }
        lt_cache_delete(lt_cache_key_user($userId), lt_cache_key_user_ns());
    }
}

// ---------------------------------------------------------------------------
// NEWS invalidation
// ---------------------------------------------------------------------------

if (!function_exists('lt_cache_invalidate_news')) {
    /** Invalidate all news-related caches (list + sidebar). */
    function lt_cache_invalidate_news()
    {
        lt_cache_delete(lt_cache_key_news_list(), lt_cache_key_news_ns());
        lt_cache_delete(lt_cache_key_sidebar_news_all(), lt_cache_key_sidebar_news_ns());
    }
}

// ---------------------------------------------------------------------------
// TAGS invalidation
// ---------------------------------------------------------------------------

if (!function_exists('lt_cache_invalidate_tags')) {
    /** Invalidate the full tags namespace (all, popular, genre lists). */
    function lt_cache_invalidate_tags()
    {
        lt_cache_invalidate_namespace(lt_cache_key_tags_ns());
    }
}

if (!function_exists('lt_cache_invalidate_tags_genre')) {
    /** Invalidate the per-category tag list. */
    function lt_cache_invalidate_tags_genre($cat)
    {
        lt_cache_delete(lt_cache_key_tags_genre($cat), lt_cache_key_tags_ns());
    }
}

// ---------------------------------------------------------------------------
// CATEGORIES invalidation
// ---------------------------------------------------------------------------

if (!function_exists('lt_cache_invalidate_cats')) {
    /** Invalidate the full categories namespace. */
    function lt_cache_invalidate_cats()
    {
        lt_cache_invalidate_namespace(lt_cache_key_cats_ns());
    }
}

// ---------------------------------------------------------------------------
// PRIVILEGES invalidation
// ---------------------------------------------------------------------------

if (!function_exists('lt_cache_invalidate_priv')) {
    /** Invalidate the full privileges namespace. */
    function lt_cache_invalidate_priv()
    {
        lt_cache_invalidate_namespace(lt_cache_key_priv_ns());
    }
}

// ---------------------------------------------------------------------------
// TORRENTS invalidation
// ---------------------------------------------------------------------------

if (!function_exists('lt_cache_invalidate_torrent')) {
    /** Invalidate cached data for a single torrent. */
    function lt_cache_invalidate_torrent($torrentId)
    {
        $torrentId = (int) $torrentId;
        if ($torrentId <= 0) {
            return;
        }
        lt_cache_delete(lt_cache_key_torrent($torrentId), lt_cache_key_torrents_ns());
    }
}

// ---------------------------------------------------------------------------
// SYSTEM invalidation (CRON, IP bans)
// ---------------------------------------------------------------------------

if (!function_exists('lt_cache_invalidate_cron')) {
    function lt_cache_invalidate_cron()
    {
        lt_cache_delete(lt_cache_key_cron(), lt_cache_key_sys_ns());
    }
}

if (!function_exists('lt_cache_invalidate_ip_ban')) {
    /** @param string|int $ip ip2long value */
    function lt_cache_invalidate_ip_ban($ip)
    {
        lt_cache_delete(lt_cache_key_ip_ban($ip), lt_cache_key_sys_ns());
    }
}
