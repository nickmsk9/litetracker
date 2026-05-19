<?php
require_once LT_SYSTEM_PATH . '/functions/functions.comments.php';
require_once LT_SYSTEM_PATH . '/functions/functions.notifications.php';
require_once LT_APP_PATH . '/core/comments.php';
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

function ajax_cm_response($ok, $message = '', $extra = array())
{
    lt_json_response(array_merge(
        array('ok' => (int) $ok, 'message' => (string)$message),
        $extra
    ));
}

$action   = preg_replace('~[^a-z_]~', '', trim((string)($_REQUEST['action'] ?? $_REQUEST['act'] ?? '')));
$type     = comments_allowed_type($_REQUEST['type'] ?? '');
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
$commentsSort = comments_sort_mode($_REQUEST['comments_sort'] ?? 'old');

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
comments_ensure_modern_schema($type);

// Helper: render fresh comment stream HTML
function ajax_cm_stream_html($type, $objectId, $file, $sort = '')
{
    return comments_render_list_html($type, $objectId, $file, 0, '', comments_sort_mode($sort));
}

// Helper: new CSRF input HTML (refreshed token for re-rendered form)
function ajax_cm_csrf_input($scope)
{
    return lt_csrf_input($scope);
}

/////////////////////////////
// REFRESH
/////////////////////////////
if ($action === 'refresh' || $action === 'list') {
    ajax_cm_response(1, '', array(
        'html' => ajax_cm_stream_html($type, $objectId, $file, $commentsSort),
        'comments_sort' => $commentsSort,
    ));
}

// Require login for mutating actions
if (empty($USER['id'])) {
    ajax_cm_response(0, 'Требуется авторизация.');
}

$mutatingActions = array('add', 'edit', 'delete', 'restore', 'report', 'react', 'pin', 'unpin');
if (in_array($action, $mutatingActions, true) && strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
    ajax_cm_response(0, 'Действие доступно только POST-запросом.');
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
    $validationError = comments_validate_text($text, $USER);
    if ($validationError !== '') {
        ajax_cm_response(0, $validationError);
    }

    $addLimit = ((strtotime((string)($USER['added'] ?? '')) ?: 0) > time() - 86400 ? 5 : 10);
    $rl = lt_rate_limit_hit('comments_add', $rateLimitId, $addLimit, 10 * 60);
    if (!empty($rl['blocked'])) {
        ajax_cm_response(0, 'Слишком много комментариев за короткое время. Повторите попытку позже.');
    }

    if (comments_is_duplicate_recent($type, $objectId, (int)$USER['id'], $text)) {
        ajax_cm_response(0, 'Нельзя отправлять одинаковые комментарии подряд.');
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
        $textSafe = lt_comment_prepare_storage_text($text);
        $insertValues[3] = "'" . $db->safesql($textSafe) . "'";
        $insertSql = "INSERT INTO `{$tableName}` (`" . implode('`,`', $insertFields) . "`) VALUES (" . implode(', ', $insertValues) . ")";
        $insertOk  = ($db->query($insertSql, 0) !== false);
    }

    if (!$insertOk) {
        ajax_cm_response(0, 'Не удалось сохранить комментарий.');
    }

    $newId = (int)$db->insert_id();
    lt_comment_notify_reply($type, $objectId, $newId, $parentId, (int)$USER['id']);
    lt_comment_notify_torrent_owner($type, $objectId, $newId, $parentId, (int)$USER['id']);

    // Notify wall owner if needed
    if ($type === 'users' && (int)$USER['id'] !== $objectId) {
        lt_comment_notify_wall_owner($objectId, $USER);
    }

    comments_invalidate_payload($type, $objectId);

    ajax_cm_response(1, 'Комментарий добавлен.', array(
        'html'       => ajax_cm_stream_html($type, $objectId, $file, $commentsSort),
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

    if (!empty(lt_comment_deleted_meta_from_row($comment)['is_deleted'])) {
        ajax_cm_response(0, 'Удалённый комментарий нельзя редактировать.');
    }

    if (!comments_user_can_edit((int)$comment['id_user'], (string)($comment['date'] ?? ''), $type)) {
        ajax_cm_response(0, 'У вас нет прав для редактирования этого комментария.');
    }

    $text = trim((string)($_POST['text'] ?? $_POST['descr'] ?? ''));
    $validationError = comments_validate_text($text, $USER);
    if ($validationError !== '') {
        ajax_cm_response(0, $validationError);
    }
    $editReason = trim((string)($_POST['edit_reason'] ?? ''));

    comments_history_add($type, $commentId, (int)$USER['id'], (string)($comment['text'] ?? ''), $text, $editReason);
    $updateSql = "UPDATE `{$tableName}` SET text = '" . $db->safesql($text) . "', id_user_edit = " . (int)$USER['id'] . ", date_edit = NOW() WHERE id = {$commentId}";
    $updated   = ($db->query($updateSql, 0) !== false);

    if (!$updated) {
        ajax_cm_response(0, 'Не удалось обновить комментарий.');
    }

    comments_invalidate_payload($type, $objectId);

    ajax_cm_response(1, 'Комментарий обновлён.', array(
        'html'       => ajax_cm_stream_html($type, $objectId, $file, $commentsSort),
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

    $comment = $db->super_query("SELECT * FROM `{$tableName}` WHERE id = {$commentId} AND `{$objectColumn}` = {$objectId} LIMIT 1");
    if (empty($comment['id'])) {
        ajax_cm_response(0, 'Комментарий не найден.');
    }

    $canDelete = (!empty($PRIV['comments_delete']) || ($type !== 'users' && (int)$USER['id'] === (int)$comment['id_user']));
    if (!$canDelete) {
        ajax_cm_response(0, 'У вас нет прав для удаления этого комментария.');
    }

    if (!empty(lt_comment_deleted_meta_from_row($comment)['is_deleted'])) {
        // Already deleted — just return fresh HTML
        ajax_cm_response(1, 'Комментарий уже удалён.', array(
            'html' => ajax_cm_stream_html($type, $objectId, $file, $commentsSort),
        ));
    }

    $deletedByAdmin = (!empty($PRIV['comments_delete']) && ((int)$USER['id'] !== (int)$comment['id_user'] || $type === 'users'));
    $deleteReason = trim((string)($_POST['delete_reason'] ?? ''));
    if ($deletedByAdmin && $deleteReason === '') {
        ajax_cm_response(0, 'Укажите причину удаления.');
    }
    $deletedText    = $db->safesql(lt_comment_deleted_placeholder($deletedByAdmin));
    comments_history_add($type, $commentId, (int)$USER['id'], (string)($comment['text'] ?? ''), lt_comment_deleted_placeholder($deletedByAdmin), $deleteReason);
    $db->query(
        "UPDATE `{$tableName}`
         SET text = '{$deletedText}',
             id_user_edit = " . (int)$USER['id'] . ",
             date_edit = NOW(),
             is_deleted = 1,
             deleted_by = " . (int)$USER['id'] . ",
             deleted_at = NOW(),
             delete_reason = " . ($deleteReason !== '' ? "'" . $db->safesql($deleteReason) . "'" : "NULL") . "
         WHERE id = {$commentId} AND `{$objectColumn}` = {$objectId}",
        0
    );
    $pin = comments_pinned_row($type, $objectId);
    if ((int)($pin['comment_id'] ?? 0) === $commentId) {
        comments_unpin($type, $objectId);
    }
    lt_comment_notify_deleted($type, $objectId, $commentId, (int)$comment['id_user'], (int)$USER['id'], $deletedByAdmin);
    comments_invalidate_payload($type, $objectId);

    ajax_cm_response(1, 'Комментарий удалён.', array(
        'html'       => ajax_cm_stream_html($type, $objectId, $file, $commentsSort),
        'comment_id' => $commentId,
    ));
}

/////////////////////////////
// RESTORE
/////////////////////////////
if ($action === 'restore') {
    $commentId = (int)($_POST['comment_id'] ?? 0);
    if ($commentId <= 0) {
        ajax_cm_response(0, 'Комментарий не найден.');
    }
    if (!comments_user_can_moderate()) {
        ajax_cm_response(0, 'У вас нет прав для восстановления комментариев.');
    }
    if (!lt_csrf_validate($csrfScope)) {
        ajax_cm_response(0, 'Защитный токен устарел. Обновите страницу и попробуйте снова.');
    }

    $comment = $db->super_query("SELECT * FROM `{$tableName}` WHERE id = {$commentId} AND `{$objectColumn}` = {$objectId} LIMIT 1");
    if (empty($comment['id'])) {
        ajax_cm_response(0, 'Комментарий не найден.');
    }

    $history = comments_history_fetch($type, $commentId);
    $restoreText = '';
    foreach ($history as $historyRow) {
        $oldText = (string)($historyRow['old_text'] ?? '');
        if (empty(lt_comment_deleted_meta($oldText)['is_deleted'])) {
            $restoreText = $oldText;
            break;
        }
    }
    if ($restoreText === '') {
        ajax_cm_response(0, 'Не удалось найти текст для восстановления.');
    }

    comments_history_add($type, $commentId, (int)$USER['id'], (string)($comment['text'] ?? ''), $restoreText, 'restore');
    $db->query(
        "UPDATE `{$tableName}`
         SET text = '".$db->safesql($restoreText)."',
             id_user_edit = ".(int)$USER['id'].",
             date_edit = NOW(),
             is_deleted = 0,
             deleted_by = NULL,
             deleted_at = NULL,
             delete_reason = NULL
         WHERE id = {$commentId} AND `{$objectColumn}` = {$objectId}",
        0
    );

    comments_invalidate_payload($type, $objectId);

    ajax_cm_response(1, 'Комментарий восстановлен.', array(
        'html' => ajax_cm_stream_html($type, $objectId, $file, $commentsSort),
        'comment_id' => $commentId,
    ));
}

/////////////////////////////
// REACT
/////////////////////////////
if ($action === 'react') {
    $commentId = (int)($_POST['comment_id'] ?? 0);
    $reaction = trim((string)($_POST['reaction'] ?? ''));
    if ($commentId <= 0 || !in_array($reaction, array('like', 'dislike'), true)) {
        ajax_cm_response(0, 'Некорректная реакция.');
    }
    if (!lt_csrf_validate($csrfScope)) {
        ajax_cm_response(0, 'Защитный токен устарел. Обновите страницу и попробуйте снова.');
    }

    $comment = $db->super_query("SELECT * FROM `{$tableName}` WHERE id = {$commentId} AND `{$objectColumn}` = {$objectId} LIMIT 1");
    if (empty($comment['id']) || !empty(lt_comment_deleted_meta_from_row($comment)['is_deleted'])) {
        ajax_cm_response(0, 'Комментарий не найден.');
    }
    if ((int)$comment['id_user'] === (int)$USER['id']) {
        ajax_cm_response(0, 'Нельзя оценивать свой комментарий.');
    }

    $existing = $db->super_query(
        "SELECT id, reaction FROM comment_reactions
         WHERE context_type = '".$db->safesql($type)."'
           AND comment_id = {$commentId}
           AND user_id = ".(int)$USER['id']."
         LIMIT 1"
    );
    if (!empty($existing['id']) && (string)$existing['reaction'] === $reaction) {
        $db->query("DELETE FROM comment_reactions WHERE id = ".(int)$existing['id'], 0);
        $currentReaction = '';
    } elseif (!empty($existing['id'])) {
        $db->query("UPDATE comment_reactions SET reaction = '".$db->safesql($reaction)."', updated_at = NOW() WHERE id = ".(int)$existing['id'], 0);
        $currentReaction = $reaction;
    } else {
        $db->query(
            "INSERT INTO comment_reactions (context_type, comment_id, user_id, reaction, created_at)
             VALUES ('".$db->safesql($type)."', {$commentId}, ".(int)$USER['id'].", '".$db->safesql($reaction)."', NOW())",
            0
        );
        $currentReaction = $reaction;
    }

    comments_invalidate_payload($type, $objectId);

    $counts = comments_reaction_counts($type, array($commentId), (int)$USER['id']);
    ajax_cm_response(1, 'Реакция сохранена.', array(
        'comment_id' => $commentId,
        'reaction' => $currentReaction,
        'likes' => (int)($counts[$commentId]['like'] ?? 0),
        'dislikes' => (int)($counts[$commentId]['dislike'] ?? 0),
        'html' => ajax_cm_stream_html($type, $objectId, $file, $commentsSort),
    ));
}

/////////////////////////////
// PIN / UNPIN
/////////////////////////////
if ($action === 'pin' || $action === 'unpin') {
    $commentId = (int)($_POST['comment_id'] ?? 0);
    if (!comments_user_can_moderate()) {
        ajax_cm_response(0, 'У вас нет прав для закрепления комментариев.');
    }
    if (!lt_csrf_validate($csrfScope)) {
        ajax_cm_response(0, 'Защитный токен устарел. Обновите страницу и попробуйте снова.');
    }

    if ($action === 'pin') {
        $comment = $db->super_query("SELECT * FROM `{$tableName}` WHERE id = {$commentId} AND `{$objectColumn}` = {$objectId} LIMIT 1");
        if (empty($comment['id']) || !empty(lt_comment_deleted_meta_from_row($comment)['is_deleted'])) {
            ajax_cm_response(0, 'Комментарий не найден или удалён.');
        }
        comments_set_pin($type, $objectId, $commentId, (int)$USER['id']);
        lt_comment_notify_pinned($type, $objectId, $commentId, (int)$comment['id_user'], (int)$USER['id']);
        $message = 'Комментарий закреплён.';
    } else {
        comments_unpin($type, $objectId);
        $message = 'Комментарий откреплён.';
    }

    ajax_cm_response(1, $message, array(
        'html' => ajax_cm_stream_html($type, $objectId, $file, $commentsSort),
        'comment_id' => $commentId,
    ));
}

/////////////////////////////
// HISTORY
/////////////////////////////
if ($action === 'history') {
    $commentId = (int)($_POST['comment_id'] ?? 0);
    if (!comments_user_can_moderate()) {
        ajax_cm_response(0, 'У вас нет прав для просмотра истории.');
    }
    if (!lt_csrf_validate($csrfScope)) {
        ajax_cm_response(0, 'Защитный токен устарел. Обновите страницу и попробуйте снова.');
    }

    $rows = comments_history_fetch($type, $commentId);
    $html = '';
    foreach ($rows as $row) {
        $html .= '<div class="comment-history-item">';
        $html .= '<div><b>'.htmlspecialchars((string)($row['editor_name'] ?? ('#'.(int)$row['editor_id'])), ENT_QUOTES, 'UTF-8').'</b> ';
        $html .= htmlspecialchars(convent_date((string)($row['edited_at'] ?? '')), ENT_QUOTES, 'UTF-8').'</div>';
        if (trim((string)($row['edit_reason'] ?? '')) !== '') {
            $html .= '<div>Причина: '.htmlspecialchars((string)$row['edit_reason'], ENT_QUOTES, 'UTF-8').'</div>';
        }
        $html .= '<pre>'.htmlspecialchars((string)($row['old_text'] ?? ''), ENT_QUOTES, 'UTF-8').'</pre>';
        $html .= '</div>';
    }
    if ($html === '') {
        $html = '<div class="comment-history-empty">Истории правок нет.</div>';
    }

    ajax_cm_response(1, '', array('html' => $html, 'comment_id' => $commentId));
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

    $comment = $db->super_query("SELECT * FROM `{$tableName}` WHERE id = {$commentId} AND `{$objectColumn}` = {$objectId} LIMIT 1");
    if (empty($comment['id'])) {
        ajax_cm_response(0, 'Комментарий не найден.');
    }
    if (!empty(lt_comment_deleted_meta_from_row($comment)['is_deleted'])) {
        ajax_cm_response(0, 'Нельзя пожаловаться на удалённый комментарий.');
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
        lt_cache_invalidate_admin_open_comment_reports_count();
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
        lt_cache_invalidate_admin_open_comment_reports_count();
    }

    ajax_cm_response(1, 'Жалоба отправлена администрации.');
}

ajax_cm_response(0, 'Неизвестное действие.');
