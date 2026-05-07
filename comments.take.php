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

require 'system/init.php';

// Проверяем пользователя
is_login();

$act = isset($_REQUEST['act']) ? trim((string) $_REQUEST['act']) : '';
$type = isset($_REQUEST['type']) ? preg_replace('~[^a-z0-9_]~i', '', (string) $_REQUEST['type']) : '';
$object_id = isset($_REQUEST['object_id']) ? (int) $_REQUEST['object_id'] : 0;
$file = isset($_REQUEST['file']) ? trim((string) $_REQUEST['file']) : '';

$file_explode = explode('?', $file, 2);
$file_name = isset($file_explode[0]) ? trim((string) $file_explode[0]) : '';

function lt_comment_prepare_storage_text($text)
{
    $text = (string) $text;

    return preg_replace_callback(
        '/[\x{10000}-\x{10FFFF}]/u',
        function ($matches) {
            if (!isset($matches[0]) || $matches[0] === '') {
                return '';
            }

            if (function_exists('mb_ord')) {
                return '&#' . mb_ord($matches[0], 'UTF-8') . ';';
            }

            $encoded = @iconv('UTF-8', 'UCS-4BE', $matches[0]);
            if ($encoded === false || strlen($encoded) !== 4) {
                return '';
            }

            $codepoint = unpack('N', $encoded);
            return '&#' . (int) ($codepoint[1] ?? 0) . ';';
        },
        $text
    );
}

function comment_return_url($file, $objectId, $suffix = '')
{
    $file = trim((string) $file);
    $objectId = (int) $objectId;
    $suffix = (string) $suffix;

    if ($file === '') {
        return '';
    }

    $url = $file;
    if (strpos($url, 'id=') === false) {
        $url .= 'id=' . $objectId;
    }

    if ($suffix !== '') {
        if ($suffix[0] === '#') {
            $url .= $suffix;
        } else {
            $needsGlue = (substr($url, -1) !== '&' && substr($url, -1) !== '?' && strpos($suffix, '&') !== 0);
            $url .= ($needsGlue ? '&' : '') . ltrim($suffix, '&');
        }
    }

    return $url;
}

if ($type === '' || $object_id <= 0 || $file_name === '') {
    err($language['default_1'], $language['comments_14'], 1);
}

if (!is_file($file_name)) {
    err($language['default_1'], $language['comments_14'], 1);
}

$table_name = 'comments_' . $type;
$object_name = 'id_' . $type;
comments_ensure_thread_support($type);
$commentCsrfScope = 'comments_' . $type . '_' . $object_id;
$commentRateLimitId = ((int) ($USER['id'] ?? 0)) . ':' . ($_SERVER['REMOTE_ADDR'] ?? 'cli');

// Проверяем объект
$object_exists = $db->super_query("SELECT id FROM `{$type}` WHERE id = {$object_id} LIMIT 1");
if (empty($object_exists['id'])) {
    err($language['default_1'], $language['comments_8'], 1);
}

//////////////////////////////////////////////////////////////
// Добавление комментария
//////////////////////////////////////////////////////////////
if ($act === 'add') {
    if (!lt_csrf_validate($commentCsrfScope)) {
        err($language['default_1'], 'Защитный токен устарел. Обновите страницу и попробуйте снова.', 1);
    }

    $commentRateLimit = lt_rate_limit_hit('comments_add', $commentRateLimitId, 8, 5 * 60);
    if (!empty($commentRateLimit['blocked'])) {
        err($language['default_1'], 'Слишком много комментариев за короткое время. Повторите попытку позже.', 1);
    }

    $text = '';

    if (isset($_REQUEST['text'])) {
        $text = trim((string) $_REQUEST['text']);
    } elseif (isset($_REQUEST['descr'])) {
        $text = trim((string) $_REQUEST['descr']);
    }

    if ($text === '') {
        err($language['default_1'], $language['comments_9'], 1);
    }

    $user_id = (int) $USER['id'];
    $supportsThreads = comments_supports_threads($type);
    $parentId = ($supportsThreads ? (int) ($_REQUEST['parent_id'] ?? 0) : 0);

    if ($supportsThreads && $parentId > 0) {
        $parentCheck = $db->super_query("SELECT id FROM `{$table_name}` WHERE id = {$parentId} AND `{$object_name}` = {$object_id} LIMIT 1");
        if (empty($parentCheck['id'])) {
            $parentId = 0;
        }
    }

    $insertFields = array('id_user', $object_name, 'date', 'text', 'id_user_edit', 'date_edit');
    $insertText = $text;
    $insertTextSql = $db->safesql($insertText);
    $insertValues = array($user_id, $object_id, 'NOW()', "'{$insertTextSql}'", $user_id, 'NOW()');

    if ($supportsThreads) {
        $insertFields[] = 'parent_id';
        $insertValues[] = $parentId;
    }

    $insert_sql = "INSERT INTO `{$table_name}` (`".implode('`,`', $insertFields)."`)
                   VALUES (".implode(', ', $insertValues).")";

    $insert_ok = ($db->query($insert_sql, 0) !== false);
    if (!$insert_ok) {
        $fallbackText = lt_comment_prepare_storage_text($text);
        if ($fallbackText !== $insertText) {
            $insertText = $fallbackText;
            $insertValues[3] = "'" . $db->safesql($insertText) . "'";
            $insert_sql = "INSERT INTO `{$table_name}` (`".implode('`,`', $insertFields)."`)
                   VALUES (".implode(', ', $insertValues).")";
            $insert_ok = ($db->query($insert_sql, 0) !== false);
        }
    }

    if (!$insert_ok) {
        err($language['default_1'], 'Не удалось добавить комментарий. Попробуйте еще раз позже.', 1);
    }

    if ($type === 'users' && $USER['id'] != $object_id) {
        $wallOwner = $db->super_query("SELECT id, name, notify_comments FROM users WHERE id=" . (int) $object_id);
        if (!empty($wallOwner['id']) && !empty($wallOwner['notify_comments'])) {
            send_msg(
                'Новый комментарий на стене',
                'Пользователь [b]' . $USER['name'] . '[/b] оставил новый комментарий на вашей стене.' . "\n" . 'Ссылка: ' . profile_href((int) $object_id),
                (int) $wallOwner['id'],
                0
            );
        }
    }

header('Location:' . comment_return_url($file, $object_id));
    die();
}

//////////////////////////////////////////////////////////////
// Реакции на комментарий Plus
//////////////////////////////////////////////////////////////
if (($act === 'react' || $act === 'reaction_list') && !empty($_REQUEST['id_comment'])) {
    $id_comment = (int) $_REQUEST['id_comment'];
    $comment = $db->super_query("SELECT id, id_user FROM `{$table_name}` WHERE id = {$id_comment} AND `{$object_name}` = {$object_id} LIMIT 1");
    if (empty($comment['id'])) {
        err($language['default_1'], $language['comments_8'], 1);
    }

    $reactionObjectType = 'comment_'.$type;

    if ($act === 'reaction_list') {
        head('Оценившие комментарий');
        begin_frame('Оценившие комментарий');
        $reactionUsers = lt_reaction_users($reactionObjectType, $id_comment);
        if ($reactionUsers) {
            echo '<div class="plus-reaction-users">';
            foreach ($reactionUsers as $reactionUser) {
                $reactionLabel = ($reactionUser['reaction'] === 'dislike' ? 'дизлайк' : 'лайк');
                echo '<div class="plus-reaction-user-row">';
                echo '<a href="'.profile_href($reactionUser).'">'.get_user_color((int) $reactionUser['class'], htmlspecialchars((string) $reactionUser['name'], ENT_QUOTES, 'UTF-8'), $reactionUser).'</a>';
                echo '<span>'.htmlspecialchars($reactionLabel, ENT_QUOTES, 'UTF-8').'</span>';
                echo '</div>';
            }
            echo '</div>';
        } else {
            echo '<div class="profile-empty-state">Оценок пока нет.</div>';
        }
        echo '<br><a href="'.htmlspecialchars(comment_return_url($file, $object_id, '#wall-comment-'.$id_comment), ENT_QUOTES, 'UTF-8').'">Вернуться</a>';
        end_frame();
        foot();
        die();
    }

    if (!lt_csrf_validate($commentCsrfScope)) {
        err($language['default_1'], 'Защитный токен устарел. Обновите страницу и попробуйте снова.', 1);
    }

    if (!lt_user_has_plus($USER)) {
        err($language['default_1'], 'Реакции доступны пользователям Plus.', 1);
    }

    lt_reaction_set($reactionObjectType, $id_comment, $_REQUEST['reaction'] ?? 'like', (int) $USER['id']);
    header('Location:' . comment_return_url($file, $object_id, '#wall-comment-' . $id_comment));
    die();
}

//////////////////////////////////////////////////////////////
// Жалоба на комментарий
//////////////////////////////////////////////////////////////
if ($act === 'report' && !empty($_REQUEST['id_comment'])) {
    if (!lt_csrf_validate($commentCsrfScope)) {
        err($language['default_1'], 'Защитный токен устарел. Обновите страницу и попробуйте снова.', 1);
    }

    $id_comment = (int) $_REQUEST['id_comment'];

    $arr = $db->super_query("SELECT id, id_user, text FROM `{$table_name}` WHERE id = {$id_comment} AND `{$object_name}` = {$object_id} LIMIT 1");
    if (empty($arr['id'])) {
        err($language['default_1'], $language['comments_8'], 1);
    }

    if ((int) $arr['id_user'] === (int) $USER['id']) {
        header('Location:' . comment_return_url($file, $object_id));
        die();
    }

    comments_reports_ensure_table();
    $reportsTable = comments_reports_table_name();
    $existingReport = $db->super_query(
        "SELECT id
         FROM `".$reportsTable."`
         WHERE comment_type = '".$db->safesql($type)."'
           AND comment_id = {$id_comment}
           AND reporter_user_id = ".(int) $USER['id']."
           AND status = 'open'
         LIMIT 1"
    );

    if (empty($existingReport['id'])) {
        $commentRateLimit = lt_rate_limit_hit('comments_report', $commentRateLimitId, 20, 15 * 60);
        if (!empty($commentRateLimit['blocked'])) {
            err($language['default_1'], 'Слишком много жалоб за короткое время. Повторите попытку позже.', 1);
        }

        $db->query(
            "INSERT INTO `".$reportsTable."` (`comment_type`, `comment_id`, `object_id`, `comment_user_id`, `reporter_user_id`, `comment_text_snapshot`, `status`, `created_at`)
             VALUES (
                '".$db->safesql($type)."',
                {$id_comment},
                {$object_id},
                ".(int) $arr['id_user'].",
                ".(int) $USER['id'].",
                '".$db->safesql((string) ($arr['text'] ?? ''))."',
                'open',
                NOW()
             )"
        );
    }

    header('Location:' . comment_return_url($file, $object_id, '#wall-comment-' . $id_comment));
    die();
}

//////////////////////////////////////////////////////////////
// Удаление комментария
//////////////////////////////////////////////////////////////
if ($act === 'delete' && !empty($_REQUEST['id_comment'])) {
    if (!lt_csrf_validate($commentCsrfScope)) {
        err($language['default_1'], 'Защитный токен устарел. Обновите страницу и попробуйте снова.', 1);
    }

    $commentRateLimit = lt_rate_limit_hit('comments_delete', $commentRateLimitId, 20, 5 * 60);
    if (!empty($commentRateLimit['blocked'])) {
        err($language['default_1'], 'Слишком много операций с комментариями. Повторите попытку позже.', 1);
    }

    $id_comment = (int) $_REQUEST['id_comment'];

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
        header('Location:' . comment_return_url($file, $object_id, 'status=3'));
        die();
    }

    $deletedByAdmin = (!empty($PRIV['comments_delete']) && ((int) $USER['id'] !== (int) $arr['id_user'] || $type === 'users'));
    $deletedText = $db->safesql(lt_comment_deleted_placeholder($deletedByAdmin));
    $delete_sql = "UPDATE `{$table_name}` SET text = '{$deletedText}', id_user_edit = ".(int) $USER['id'].", date_edit = NOW() WHERE id = {$id_comment} AND `{$object_name}` = {$object_id}";
    $db->query($delete_sql, 0);

    header('Location:' . comment_return_url($file, $object_id, 'status=3'));
    die();
}

//////////////////////////////////////////////////////////////
// Редактирование комментария
//////////////////////////////////////////////////////////////
if ($act === 'edit' && !empty($_REQUEST['id_comment'])) {
    $id_comment = (int) $_REQUEST['id_comment'];

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

    if ($_POST) {
        if (!lt_csrf_validate($commentCsrfScope)) {
            err($language['default_1'], 'Защитный токен устарел. Обновите страницу и попробуйте снова.', 1);
        }

        $commentRateLimit = lt_rate_limit_hit('comments_edit', $commentRateLimitId, 15, 5 * 60);
        if (!empty($commentRateLimit['blocked'])) {
            err($language['default_1'], 'Слишком много операций с комментариями. Повторите попытку позже.', 1);
        }

        $update = array();

        $text = '';
        if (isset($_REQUEST['text'])) {
            $text = trim((string) $_REQUEST['text']);
        } elseif (isset($_REQUEST['descr'])) {
            $text = trim((string) $_REQUEST['descr']);
        }

        if ((string) $arr['text'] !== $text) {
            if ($text === '') {
                err($language['default_1'], $language['comments_9'], 1);
            }

            $updateText = $text;
            $update[] = 'text="' . $db->safesql($updateText) . '"';
            $update[] = 'id_user_edit=' . (int) $USER['id'];
            $update[] = 'date_edit=NOW()';
        }
 
        if (count($update)) {
            $update_sql = "UPDATE `{$table_name}` SET " . implode(',', $update) . " WHERE id = {$id_comment}";
            $updated = ($db->query($update_sql, 0) !== false);
            if (!$updated && isset($updateText)) {
                $fallbackText = lt_comment_prepare_storage_text($updateText);
                if ($fallbackText !== $updateText) {
                    $update[0] = 'text="' . $db->safesql($fallbackText) . '"';
                    $update_sql = "UPDATE `{$table_name}` SET " . implode(',', $update) . " WHERE id = {$id_comment}";
                    $updated = ($db->query($update_sql, 0) !== false);
                }
            }

            if (!$updated) {
                err($language['default_1'], 'Не удалось обновить комментарий. Попробуйте еще раз позже.', 1);
            }
        }

        header('Location:' . comment_return_url($file, $object_id, 'status=2'));
        die();
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
    echo '<input type="hidden" value="' . htmlspecialchars($file, ENT_QUOTES, 'UTF-8') . '" name="file">';
    echo '<input type="hidden" value="edit" name="act">';
    echo lt_csrf_input($commentCsrfScope);
    echo '</form>';
    end_frame();
    foot();
    die();
}
?>
