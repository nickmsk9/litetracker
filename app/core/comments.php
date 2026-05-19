<?php

function comments_type_routes()
{
    return array(
        'torrents' => array('object_table' => 'torrents'),
        'users' => array('object_table' => 'users'),
        'news' => array('object_table' => 'news'),
        'faq' => array('object_table' => 'faq'),
    );
}

function comments_allowed_type($type)
{
    $type = preg_replace('~[^a-z0-9_]~i', '', (string) $type);
    $routes = comments_type_routes();

    return isset($routes[$type]) ? $type : '';
}

function comments_object_table($type)
{
    $type = comments_allowed_type($type);
    $routes = comments_type_routes();

    return ($type !== '' ? (string) $routes[$type]['object_table'] : '');
}

function comments_return_route_url($type, $objectId, $suffix = '')
{
    $type = comments_allowed_type($type);
    $objectId = (int) $objectId;
    $suffix = (string) $suffix;

    if ($type === '' || $objectId <= 0) {
        return '';
    }

    $url = comments_context_url($type, $objectId);
    if ($suffix === '') {
        return $url;
    }

    if ($suffix[0] === '#') {
        return $url.$suffix;
    }

    $needsGlue = (substr($url, -1) !== '&' && substr($url, -1) !== '?' && strpos($suffix, '&') !== 0);

    return $url.($needsGlue ? '&' : '').ltrim($suffix, '&');
}

function comments_take_require_csrf($scope)
{
    global $language;

    if (!lt_csrf_validate($scope)) {
        err($language['default_1'], 'Защитный токен устарел. Обновите страницу и попробуйте снова.', 1);
    }
}

function comments_take_rate_limit($scope, $identifier, $limit, $windowSeconds, $message)
{
    global $language;

    $rateLimit = lt_rate_limit_hit($scope, $identifier, $limit, $windowSeconds);
    if (!empty($rateLimit['blocked'])) {
        err($language['default_1'], $message, 1);
    }
}

function comments_take_redirect($type, $objectId, $suffix = '')
{
    (new LiteTracker\Http\RedirectResponse(comments_return_route_url($type, $objectId, $suffix)))->send();
    exit;
}
