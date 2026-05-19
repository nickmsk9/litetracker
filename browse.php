<?php
/*
===================================================================
LiteTracker Source
===================================================================
by Nick
-------------------------------------------------------------------
Назначение: Страница торрентов
===================================================================
*/

require __DIR__ . '/app/system/init.php';
require_once __DIR__ . '/app/core/http.php';
require_once __DIR__ . '/app/core/browse.php';

$GLOBALS['LITETRACKER_HIDE_TOP_BLOCKS'] = true;
$GLOBALS['LITETRACKER_HIDE_BOTTOM_BLOCKS'] = true;
$GLOBALS['LITETRACKER_HIDE_STANDARD_SIDEBAR'] = true;

$request = LiteTracker\Http\Request::capture();
$response = browse_handle_request($request);
$response->send();
