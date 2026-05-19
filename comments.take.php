<?php
/*
===================================================================
LiteTracker Source
===================================================================
by nikita
-------------------------------------------------------------------
Назначение: Обработка комментариев
===================================================================
*/

require __DIR__ . '/app/system/init.php';
require_once __DIR__ . '/app/core/http.php';
require_once __DIR__ . '/app/core/comments.php';

// Проверяем пользователя
is_login();

$request = LiteTracker\Http\Request::capture();
$input = $request->request();

$act = isset($input['act']) ? trim((string) $input['act']) : '';
$type = comments_allowed_type($input['type'] ?? '');
$object_id = isset($input['object_id']) ? (int) $input['object_id'] : 0;
$returnUrl = comments_return_route_url($type, $object_id);

if ($type === '' || $object_id <= 0 || $returnUrl === '') {
    err($language['default_1'], $language['comments_14'], 1);
}

$table_name = 'comments_' . $type;
$object_name = 'id_' . $type;
comments_ensure_thread_support($type);
$commentCsrfScope = 'comments_' . $type . '_' . $object_id;
$commentRateLimitId = ((int) ($USER['id'] ?? 0)) . ':' . ($_SERVER['REMOTE_ADDR'] ?? 'cli');
$commentMutatingActs = array('add', 'report', 'delete');
if (in_array($act, $commentMutatingActs, true) && $request->method() !== 'POST') {
    err($language['default_1'], 'Действие доступно только POST-запросом.', 1);
}

// Проверяем объект
$objectTable = comments_object_table($type);
$object_exists = $db->super_query("SELECT id FROM `{$objectTable}` WHERE id = {$object_id} LIMIT 1");
if (empty($object_exists['id'])) {
    err($language['default_1'], $language['comments_8'], 1);
}

//////////////////////////////////////////////////////////////
// Добавление комментария
//////////////////////////////////////////////////////////////
if ($act === 'add') {
    comments_take_require_csrf($commentCsrfScope);
    comments_take_rate_limit('comments_add', $commentRateLimitId, 8, 5 * 60, 'Слишком много комментариев за короткое время. Повторите попытку позже.');

    $text = '';

    if (isset($input['text'])) {
        $text = trim((string) $input['text']);
    } elseif (isset($input['descr'])) {
        $text = trim((string) $input['descr']);
    }

    if ($text === '') {
        err($language['default_1'], $language['comments_9'], 1);
    }

    $user_id = (int) $USER['id'];
    $supportsThreads = comments_supports_threads($type);
    $parentId = ($supportsThreads ? (int) ($input['parent_id'] ?? 0) : 0);

    if ($supportsThreads && $parentId > 0) {
        $parentCheck = $db->super_query("SELECT id FROM `{$table_name}` WHERE id = {$parentId} AND `{$object_name}` = {$object_id} LIMIT 1");
        if (empty($parentCheck['id'])) {
            $parentId = 0;
        }
    }

    $insertFields = array('id_user', $object_name, 'date', 'text', 'id_user_edit', 'date_edit');
    $insertPlaceholders = array($user_id, $object_id, 'NOW()', '?', 0, 'NOW()');

    if ($supportsThreads) {
        $insertFields[] = 'parent_id';
        $insertPlaceholders[] = $parentId;
    }

    $insert_sql = "INSERT INTO `{$table_name}` (`".implode('`,`', $insertFields)."`)
                   VALUES (".implode(', ', $insertPlaceholders).")";

    $insert_ok = ($db->pquery($insert_sql, 's', [$text], 0) !== false);
    if (!$insert_ok) {
        $fallbackText = lt_comment_prepare_storage_text($text);
        if ($fallbackText !== $text) {
            $insert_ok = ($db->pquery($insert_sql, 's', [$fallbackText], 0) !== false);
        }
    }

    if (!$insert_ok) {
        err($language['default_1'], $language['comments_15'], 1);
    }

    $newCommentId = (int) $db->insert_id();
    lt_notifications_handle_comment_added($type, $object_id, $newCommentId, $parentId, (int) $USER['id']);

    if ($type === 'users' && $USER['id'] != $object_id) {
        lt_comment_notify_wall_owner((int) $object_id, $USER);
    }

    comments_invalidate_payload($type, $object_id);

    comments_take_redirect($type, $object_id);
}

//////////////////////////////////////////////////////////////
// Жалоба на комментарий
//////////////////////////////////////////////////////////////
if ($act === 'report' && !empty($input['id_comment'])) {
    comments_take_require_csrf($commentCsrfScope);

    $id_comment = (int) $input['id_comment'];

    $arr = $db->super_query("SELECT id, id_user, text FROM `{$table_name}` WHERE id = {$id_comment} AND `{$object_name}` = {$object_id} LIMIT 1");
    if (empty($arr['id'])) {
        err($language['default_1'], $language['comments_8'], 1);
    }

    if ((int) $arr['id_user'] === (int) $USER['id']) {
        comments_take_redirect($type, $object_id);
    }

    comments_reports_ensure_table();
    $reportsTable = comments_reports_table_name();
    $existingReport = $db->psuper_query(
        "SELECT id FROM `".$reportsTable."`
         WHERE comment_type = ?
           AND comment_id = {$id_comment}
           AND reporter_user_id = ".(int) $USER['id']."
           AND status = 'open'
         LIMIT 1",
        's', [$type]
    );

    if (empty($existingReport['id'])) {
        comments_take_rate_limit('comments_report', $commentRateLimitId, 20, 15 * 60, 'Слишком много жалоб за короткое время. Повторите попытку позже.');

        $db->pquery(
            "INSERT INTO `".$reportsTable."` (`comment_type`, `comment_id`, `object_id`, `comment_user_id`, `reporter_user_id`, `comment_text_snapshot`, `status`, `created_at`)
             VALUES (?, {$id_comment}, {$object_id}, ".(int) $arr['id_user'].", ".(int) $USER['id'].", ?, 'open', NOW())",
            'ss', [$type, (string) ($arr['text'] ?? '')]
        );
        lt_cache_invalidate_admin_open_comment_reports_count();
    }

    comments_take_redirect($type, $object_id, '#wall-comment-' . $id_comment);
}

//////////////////////////////////////////////////////////////
// Удаление комментария
//////////////////////////////////////////////////////////////
if ($act === 'delete' && !empty($input['id_comment'])) {
    comments_take_require_csrf($commentCsrfScope);
    comments_take_rate_limit('comments_delete', $commentRateLimitId, 20, 5 * 60, 'Слишком много операций с комментариями. Повторите попытку позже.');

    $id_comment = (int) $input['id_comment'];

    $arr = $db->super_query("SELECT id, id_user FROM `{$table_name}` WHERE id = {$id_comment} LIMIT 1");
    if (empty($arr['id'])) {
        err($language['default_1'], $language['comments_8'], 1);
    }

    $canDeleteComment = (!empty($PRIV['comments_delete']) || ($type !== 'users' && (int) $USER['id'] === (int) $arr['id_user']));
    if (!$canDeleteComment) {
        err($language['default_1'], $language['comments_10'], 1);
    }

    $deletedMeta = lt_comment_deleted_meta((string) ($arr['text'] ?? ''));
    if (!empty($deletedMeta['is_deleted'])) {
        comments_take_redirect($type, $object_id, 'status=3');
    }

    $deletedByAdmin = (!empty($PRIV['comments_delete']) && ((int) $USER['id'] !== (int) $arr['id_user'] || $type === 'users'));
    $deletedText = lt_comment_deleted_placeholder($deletedByAdmin);
    $db->pquery("UPDATE `{$table_name}` SET text = ?, id_user_edit = ".(int) $USER['id'].", date_edit = NOW() WHERE id = {$id_comment} AND `{$object_name}` = {$object_id}", 's', [$deletedText], 0);
    lt_notifications_handle_comment_deleted($type, $object_id, $id_comment, (int) $arr['id_user'], (int) $USER['id'], $deletedByAdmin);
    comments_invalidate_payload($type, $object_id);

    comments_take_redirect($type, $object_id, 'status=3');
}

//////////////////////////////////////////////////////////////
// Редактирование комментария
//////////////////////////////////////////////////////////////
if ($act === 'edit' && !empty($input['id_comment'])) {
    $id_comment = (int) $input['id_comment'];

    $arr = $db->super_query("SELECT * FROM `{$table_name}` WHERE id = {$id_comment} LIMIT 1");
    if (empty($arr['id'])) {
        err($language['default_1'], $language['comments_8'], 1);
    }

    if (!empty(lt_comment_deleted_meta((string) ($arr['text'] ?? ''))['is_deleted'])) {
        err($language['default_1'], 'Удалённый комментарий нельзя редактировать.', 1);
    }

    $canEditComment = comments_user_can_edit((int) $arr['id_user'], (string) ($arr['date'] ?? ''), $type);
    if (!$canEditComment) {
        err($language['default_1'], $language['comments_11'], 1);
    }

    if ($request->method() === 'POST') {
        comments_take_require_csrf($commentCsrfScope);
        comments_take_rate_limit('comments_edit', $commentRateLimitId, 15, 5 * 60, 'Слишком много операций с комментариями. Повторите попытку позже.');

        $update = array();

        $text = '';
        if (isset($input['text'])) {
            $text = trim((string) $input['text']);
        } elseif (isset($input['descr'])) {
            $text = trim((string) $input['descr']);
        }

        if ((string) $arr['text'] !== $text) {
            if ($text === '') {
                err($language['default_1'], $language['comments_9'], 1);
            }

            $update[] = 'text=?';
            $update[] = 'id_user_edit=' . (int) $USER['id'];
            $update[] = 'date_edit=NOW()';
        }

        if (count($update)) {
            $update_sql = "UPDATE `{$table_name}` SET " . implode(',', $update) . " WHERE id = {$id_comment}";
            $updated = ($db->pquery($update_sql, 's', [$text], 0) !== false);
            if (!$updated) {
                $fallbackText = lt_comment_prepare_storage_text($text);
                if ($fallbackText !== $text) {
                    $updated = ($db->pquery($update_sql, 's', [$fallbackText], 0) !== false);
                }
            }

            if (!$updated) {
                err($language['default_1'], $language['comments_16'], 1);
            }

            comments_invalidate_payload($type, $object_id);
        }

        comments_take_redirect($type, $object_id, 'status=2');
    }

    head($language['comments_12']);
    begin_frame($language['comments_12']);
    echo '<form name="addComment" method="POST" action="comments.take.php">';
    textbb('text', $arr['text'], '90%', '300');
    echo '<br>';
    echo '<input value="' . $language['comments_4'] . '" type="submit">&nbsp';
    echo '<input value="' . $language['default_5'] . '" type="button" onClick="history.go(-1);">';
    echo '<input type="hidden" value="' . $object_id . '" name="object_id">';
    echo '<input type="hidden" value="' . htmlspecialchars($type, ENT_QUOTES, 'UTF-8') . '" name="type">';
    echo '<input type="hidden" value="' . $id_comment . '" name="id_comment">';
    echo '<input type="hidden" value="edit" name="act">';
    echo lt_csrf_input($commentCsrfScope);
    echo '</form>';
    end_frame();
    foot();
    die();
}
?>
