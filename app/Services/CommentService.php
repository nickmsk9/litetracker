<?php

if (!function_exists('lt_comment_service_prepare_storage_text')) {
    function lt_comment_service_prepare_storage_text($text)
    {
        return lt_comment_prepare_storage_text($text);
    }
}

if (!function_exists('lt_comment_service_return_url')) {
    function lt_comment_service_return_url($file, $objectId, $suffix = '')
    {
        return lt_comment_return_url($file, $objectId, $suffix);
    }
}

if (!function_exists('lt_comment_service_notify_wall_owner')) {
    function lt_comment_service_notify_wall_owner($wallOwnerId, $actor)
    {
        lt_comment_notify_wall_owner($wallOwnerId, $actor);
    }
}
