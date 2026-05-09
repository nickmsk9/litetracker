<?php
/*
===================================================================
LiteTracker Source
===================================================================
by Nick
-------------------------------------------------------------------
Назначение: AJAX-обработчик системы комментариев (добавление,
редактирование, удаление, жалобы)
===================================================================
*/

require '../system/init.php';

header('Content-Type: application/json; charset=UTF-8');

function ajax_cm_response($ok, $message = '', $extra = array())
{
    $payload = array_merge(
        array('ok' => (int)(bool)$ok, 'message' => (string)$message),
        $extra
    );
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    die();
}

$action   = preg_replace('~[^a-z_]~', '', trim((string)($_REQUEST['action'] ?? $_REQUEST['act'] ?? '')));
$type     = preg_replace('~[^a-z0-9_]~i', '', trim((string)($_REQUEST['type'] ?? '')));
$objectId = (int)($_REQUEST['object_id'] ?? 0);
$file     = trim((string)($_REQUEST['file'] ?? ''));

if ($type === '' || $objectId <= 0) {
    ajax_cm_response(0, 'Некорректный запрос.');
}

// Validate file parameter (same logic as comments.take.php)
$fileExplode = explode('?', $file, 2);
$fileName    = trim((string)($fileExplode[0] ?? ''));
if ($fileName !== '' && !is_file($fileName)) {
    $fileName = '';
    $file     = '';
}

$tableName    = comments_table_name($type);
$objectColumn = comments_object_column($type);
$csrfScope    = 'comments_' . $type . '_' . $objectId;

if (!lt_table_exists($tableName)) {
    ajax_cm_response(0, 'Тип комментариев не найден.');
}

if ($type === 'users') {
    $wallOwner = get_user_info($objectId);
    if (empty($wallOwner['id'])) {
        ajax_cm_response(0, 'Пользователь не найден.');
    }
}

comments_ensure_thread_support($type);

// Helper: render fresh comment stream HTML
function ajax_cm_stream_html($type, $objectId, $file)
{
    return comments_render_list_html($type, $objectId, $file);
}

// Helper: new CSRF input HTML (refreshed token for re-rendered form)
function ajax_cm_csrf_input($scope)
{
    return lt_csrf_input($scope);
}

/////////////////////////////
// REFRESH
/////////////////////////////
if ($action === 'refresh') {
    ajax_cm_response(1, '', array(
        'html' => ajax_cm_stream_html($type, $objectId, $file),
    ));
}

// Require login for mutating actions
if (empty($USER['id'])) {
    ajax_cm_response(0, 'Требуется авторизация.');
}

$rateLimitId = ((int)($USER['id'] ?? 0)) . ':' . ($_SERVER['REMOTE_ADDR'] ?? 'cli');

/////////////////////////////
// ADD
/////////////////////////////
if ($action === 'add') {
    if (!lt_csrf_validate($csrfScope)) {
        ajax_cm_response(0, 'Защитный токен устарел. Обновите страницу и попробуйте снова.');
    }

    $text = trim((string)($_POST['text'] ?? $_POST['descr'] ?? ''));
    if ($text === '') {
        ajax_cm_response(0, 'Введите текст комментария.');
    }

    $supportsThreads = comments_supports_threads($type);
    $parentId        = ($supportsThreads ? (int)($_POST['parent_id'] ?? 0) : 0);

    if ($supportsThreads && $parentId > 0) {
        $parentCheck = $db->super_query("SELECT id FROM `{$tableName}` WHERE id = {$parentId} AND `{$objectColumn}` = {$objectId} LIMIT 1");
        if (empty($parentCheck['id'])) {
            $parentId = 0;
        }
    }

    $insertFields = array('id_user', $objectColumn, 'date', 'text', 'id_user_edit', 'date_edit');
    $insertValues = array((int)$USER['id'], $objectId, 'NOW()', "'" . $db->safesql($text) . "'", 0, 'NOW()');

    if ($supportsThreads) {
        $insertFields[] = 'parent_id';
        $insertValues[] = $parentId;
    }

    $insertSql = "INSERT INTO `{$tableName}` (`" . implode('`,`', $insertFields) . "`) VALUES (" . implode(', ', $insertValues) . ")";
    $insertOk  = ($db->query($insertSql, 0) !== false);

    if (!$insertOk) {
        // Retry with emoji-to-entity fallback for wide-char characters
        $canFallback = function_exists('mb_ord') || function_exists('iconv');
        if ($canFallback) {
            $textSafe = preg_replace_callback(
                '/[\x{10000}-\x{10FFFF}]/u',
                function ($m) {
                    if (function_exists('mb_ord')) {
                        return '&#' . mb_ord($m[0], 'UTF-8') . ';';
                    }
                    $enc = iconv('UTF-8', 'UCS-4BE', $m[0]);
                    if ($enc === false || strlen($enc) !== 4) return '';
                    $cp = unpack('N', $enc);
                    return (!empty($cp[1]) ? '&#' . (int)$cp[1] . ';' : '');
                },
                $text
            );
            $insertValues[3] = "'" . $db->safesql($textSafe) . "'";
            $insertSql = "INSERT INTO `{$tableName}` (`" . implode('`,`', $insertFields) . "`) VALUES (" . implode(', ', $insertValues) . ")";
            $insertOk  = ($db->query($insertSql, 0) !== false);
        }
    }

    if (!$insertOk) {
        ajax_cm_response(0, 'Не удалось сохранить комментарий.');
    }

    $newId = (int)$db->insert_id();

    // Notify wall owner if needed
    if ($type === 'users' && (int)$USER['id'] !== $objectId) {
        $wallOwner = $db->super_query("SELECT id, name, notify_comments FROM users WHERE id = " . $objectId);
        if (!empty($wallOwner['id']) && !empty($wallOwner['notify_comments'])) {
            send_msg(
                'Новый комментарий на стене',
                'Пользователь [b]' . $USER['name'] . '[/b] оставил новый комментарий на вашей стене.' . "\n" . 'Ссылка: ' . profile_href($objectId),
                (int)$wallOwner['id'],
                0
            );
        }
    }

    ajax_cm_response(1, 'Комментарий добавлен.', array(
        'html'       => ajax_cm_stream_html($type, $objectId, $file),
        'comment_id' => $newId,
        'csrf_input' => ajax_cm_csrf_input($csrfScope),
    ));
}

/////////////////////////////
// EDIT
/////////////////////////////
if ($action === 'edit') {
    $commentId = (int)($_REQUEST['comment_id'] ?? 0);
    if ($commentId <= 0) {
        ajax_cm_response(0, 'Комментарий не найден.');
    }

    if (!lt_csrf_validate($csrfScope)) {
        ajax_cm_response(0, 'Защитный токен устарел. Обновите страницу и попробуйте снова.');
    }

    $rl = lt_rate_limit_hit('comments_edit', $rateLimitId, 15, 5 * 60);
    if (!empty($rl['blocked'])) {
        ajax_cm_response(0, 'Слишком много операций с комментариями. Повторите попытку позже.');
    }

    $comment = $db->super_query("SELECT * FROM `{$tableName}` WHERE id = {$commentId} AND `{$objectColumn}` = {$objectId} LIMIT 1");
    if (empty($comment['id'])) {
        ajax_cm_response(0, 'Комментарий не найден.');
    }

    if (!empty(lt_comment_deleted_meta((string)($comment['text'] ?? ''))['is_deleted'])) {
        ajax_cm_response(0, 'Удалённый комментарий нельзя редактировать.');
    }

    if (!comments_user_can_edit((int)$comment['id_user'], (string)($comment['date'] ?? ''), $type)) {
        ajax_cm_response(0, 'У вас нет прав для редактирования этого комментария.');
    }

    $text = trim((string)($_POST['text'] ?? $_POST['descr'] ?? ''));
    if ($text === '') {
        ajax_cm_response(0, 'Введите текст комментария.');
    }

    $updateSql = "UPDATE `{$tableName}` SET text = '" . $db->safesql($text) . "', id_user_edit = " . (int)$USER['id'] . ", date_edit = NOW() WHERE id = {$commentId}";
    $updated   = ($db->query($updateSql, 0) !== false);

    if (!$updated) {
        ajax_cm_response(0, 'Не удалось обновить комментарий.');
    }

    ajax_cm_response(1, 'Комментарий обновлён.', array(
        'html'       => ajax_cm_stream_html($type, $objectId, $file),
        'comment_id' => $commentId,
    ));
}

/////////////////////////////
// DELETE
/////////////////////////////
if ($action === 'delete') {
    $commentId = (int)($_REQUEST['comment_id'] ?? 0);
    if ($commentId <= 0) {
        ajax_cm_response(0, 'Комментарий не найден.');
    }

    if (!lt_csrf_validate($csrfScope)) {
        ajax_cm_response(0, 'Защитный токен устарел. Обновите страницу и попробуйте снова.');
    }

    $rl = lt_rate_limit_hit('comments_delete', $rateLimitId, 20, 5 * 60);
    if (!empty($rl['blocked'])) {
        ajax_cm_response(0, 'Слишком много операций с комментариями. Повторите попытку позже.');
    }

    $comment = $db->super_query("SELECT id, id_user, text FROM `{$tableName}` WHERE id = {$commentId} AND `{$objectColumn}` = {$objectId} LIMIT 1");
    if (empty($comment['id'])) {
        ajax_cm_response(0, 'Комментарий не найден.');
    }

    $canDelete = (!empty($PRIV['comments_delete']) || ($type !== 'users' && (int)$USER['id'] === (int)$comment['id_user']));
    if (!$canDelete) {
        ajax_cm_response(0, 'У вас нет прав для удаления этого комментария.');
    }

    if (!empty(lt_comment_deleted_meta((string)($comment['text'] ?? ''))['is_deleted'])) {
        // Already deleted — just return fresh HTML
        ajax_cm_response(1, 'Комментарий уже удалён.', array(
            'html' => ajax_cm_stream_html($type, $objectId, $file),
        ));
    }

    $deletedByAdmin = (!empty($PRIV['comments_delete']) && ((int)$USER['id'] !== (int)$comment['id_user'] || $type === 'users'));
    $deletedText    = $db->safesql(lt_comment_deleted_placeholder($deletedByAdmin));
    $db->query("UPDATE `{$tableName}` SET text = '{$deletedText}', id_user_edit = " . (int)$USER['id'] . ", date_edit = NOW() WHERE id = {$commentId} AND `{$objectColumn}` = {$objectId}");

    ajax_cm_response(1, 'Комментарий удалён.', array(
        'html'       => ajax_cm_stream_html($type, $objectId, $file),
        'comment_id' => $commentId,
    ));
}

/////////////////////////////
// REPORT
/////////////////////////////
if ($action === 'report') {
    $commentId = (int)($_REQUEST['comment_id'] ?? 0);
    if ($commentId <= 0) {
        ajax_cm_response(0, 'Комментарий не найден.');
    }

    if (!lt_csrf_validate($csrfScope)) {
        ajax_cm_response(0, 'Защитный токен устарел. Обновите страницу и попробуйте снова.');
    }

    $comment = $db->super_query("SELECT id, id_user, text FROM `{$tableName}` WHERE id = {$commentId} AND `{$objectColumn}` = {$objectId} LIMIT 1");
    if (empty($comment['id'])) {
        ajax_cm_response(0, 'Комментарий не найден.');
    }

    if ((int)$comment['id_user'] === (int)$USER['id']) {
        ajax_cm_response(0, 'Нельзя пожаловаться на свой комментарий.');
    }

    if ($type === 'users') {
        user_wall_reports_ensure_table();
        $reportsTable = user_wall_reports_table_name();
        $existing = $db->super_query(
            "SELECT id
             FROM `{$reportsTable}`
             WHERE comment_id = {$commentId}
               AND reporter_user_id = " . (int)$USER['id'] . "
               AND status = 'open'
             LIMIT 1"
        );
    } else {
        comments_reports_ensure_table();
        $reportsTable = comments_reports_table_name();
        $existing = $db->super_query(
            "SELECT id FROM `{$reportsTable}`
             WHERE comment_type = '" . $db->safesql($type) . "'
               AND comment_id = {$commentId}
               AND reporter_user_id = " . (int)$USER['id'] . "
               AND status = 'open'
             LIMIT 1"
        );
    }

    if (!empty($existing['id'])) {
        ajax_cm_response(0, 'Вы уже пожаловались на этот комментарий.');
    }

    $rl = lt_rate_limit_hit('comments_report', $rateLimitId, 20, 15 * 60);
    if (!empty($rl['blocked'])) {
        ajax_cm_response(0, 'Слишком много жалоб за короткое время. Повторите попытку позже.');
    }

    if ($type === 'users') {
        $db->query(
            "INSERT INTO `{$reportsTable}` (`comment_id`, `object_id`, `comment_user_id`, `reporter_user_id`, `comment_text_snapshot`, `status`, `created_at`)
             VALUES ({$commentId}, {$objectId}, " . (int)$comment['id_user'] . ", " . (int)$USER['id'] . ", '" . $db->safesql((string)($comment['text'] ?? '')) . "', 'open', NOW())"
        );
        user_wall_reports_notify_moderators((int)$db->insert_id(), $objectId, $commentId, (string)($USER['name'] ?? ''));
    } else {
        $db->query(
            "INSERT INTO `{$reportsTable}` (`comment_type`, `comment_id`, `object_id`, `comment_user_id`, `reporter_user_id`, `comment_text_snapshot`, `status`, `created_at`)
             VALUES (
                '" . $db->safesql($type) . "',
                {$commentId},
                {$objectId},
                " . (int)$comment['id_user'] . ",
                " . (int)$USER['id'] . ",
                '" . $db->safesql((string)($comment['text'] ?? '')) . "',
                'open',
                NOW()
             )"
        );
    }

    ajax_cm_response(1, 'Жалоба отправлена администрации.');
}

ajax_cm_response(0, 'Неизвестное действие.');
