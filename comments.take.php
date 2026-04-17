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

function comment_debug_log($message)
{
    $logFile = __DIR__ . '/comments_debug.log';
    @file_put_contents($logFile, '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL, FILE_APPEND);
}

comment_debug_log('REQUEST=' . print_r($_REQUEST, true));

if ($type === '' || $object_id <= 0 || $file_name === '') {
    comment_debug_log('Ошибка: пустой type/object_id/file');
    err($language['default_1'], $language['comments_14'], 1);
}

if (!is_file($file_name)) {
    comment_debug_log('Ошибка: файл не найден: ' . $file_name);
    err($language['default_1'], $language['comments_14'], 1);
}

$table_name = 'comments_' . $type;
$object_name = 'id_' . $type;

// Проверяем объект
$object_exists = $db->super_query("SELECT id FROM `{$type}` WHERE id = {$object_id} LIMIT 1");
if (empty($object_exists['id'])) {
    comment_debug_log('Ошибка: объект не найден. type=' . $type . ', object_id=' . $object_id);
    err($language['default_1'], $language['comments_8'], 1);
}

//////////////////////////////////////////////////////////////
// Добавление комментария
//////////////////////////////////////////////////////////////
if ($act === 'add') {
    $text = '';

    if (isset($_REQUEST['text'])) {
        $text = trim((string) $_REQUEST['text']);
    } elseif (isset($_REQUEST['descr'])) {
        $text = trim((string) $_REQUEST['descr']);
    }

    if ($text === '') {
        err($language['default_1'], $language['comments_9'], 1);
    }

    $text_sql = $db->safesql($text);
    $user_id = (int) $USER['id'];

    $insert_sql = "INSERT INTO `{$table_name}` (`id_user`, `{$object_name}`, `date`, `text`)
                   VALUES ({$user_id}, {$object_id}, NOW(), '{$text_sql}')";

    comment_debug_log('INSERT SQL: ' . $insert_sql);
    $db->query($insert_sql, 0);

    if (method_exists($db, 'insert_id')) {
        comment_debug_log('INSERT ID: ' . (int) $db->insert_id());
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

header('Location:' . $file . 'id=' . $object_id);
    die();
}

//////////////////////////////////////////////////////////////
// Удаление комментария
//////////////////////////////////////////////////////////////
if ($act === 'delete' && !empty($_REQUEST['id_comment'])) {
    $id_comment = (int) $_REQUEST['id_comment'];

    $arr = $db->super_query("SELECT id, id_user FROM `{$table_name}` WHERE id = {$id_comment} LIMIT 1");
    if (empty($arr['id'])) {
        comment_debug_log('DELETE: комментарий не найден: id=' . $id_comment);
        err($language['default_1'], $language['comments_8'], 1);
    }

    if ($USER['id'] != $arr['id_user'] && empty($PRIV['comments_delete'])) {
        comment_debug_log('DELETE: нет прав. user=' . $USER['id'] . ', owner=' . $arr['id_user']);
        err($language['default_1'], $language['comments_10'], 1);
    }

    $delete_sql = "DELETE FROM `{$table_name}` WHERE id = {$id_comment} AND `{$object_name}` = {$object_id}";
    comment_debug_log('DELETE SQL: ' . $delete_sql);
    $db->query($delete_sql, 0);

    header('Location:' . $file . 'id=' . $object_id . '&status=3');
    die();
}

//////////////////////////////////////////////////////////////
// Редактирование комментария
//////////////////////////////////////////////////////////////
if ($act === 'edit' && !empty($_REQUEST['id_comment'])) {
    $id_comment = (int) $_REQUEST['id_comment'];

    $arr = $db->super_query("SELECT * FROM `{$table_name}` WHERE id = {$id_comment} LIMIT 1");
    if (empty($arr['id'])) {
        comment_debug_log('EDIT: комментарий не найден: id=' . $id_comment);
        err($language['default_1'], $language['comments_8'], 1);
    }

    if ($USER['id'] != $arr['id_user'] && empty($PRIV['comments_edit'])) {
        comment_debug_log('EDIT: нет прав. user=' . $USER['id'] . ', owner=' . $arr['id_user']);
        err($language['default_1'], $language['comments_11'], 1);
    }

    if ($_POST) {
        $update = array();

        $text = '';
        if (isset($_REQUEST['text'])) {
            $text = trim((string) $_REQUEST['text']);
        } elseif (isset($_REQUEST['descr'])) {
            $text = trim((string) $_REQUEST['descr']);
        }

        if ((string) $arr['text'] !== $text) {
            if ($text === '') {
                comment_debug_log('EDIT: пустой текст');
                err($language['default_1'], $language['comments_9'], 1);
            }

            $update[] = 'text="' . $db->safesql($text) . '"';
            $update[] = 'id_user_edit=' . (int) $USER['id'];
            $update[] = 'date_edit=NOW()';
        }

        if (count($update)) {
            $update_sql = "UPDATE `{$table_name}` SET " . implode(',', $update) . " WHERE id = {$id_comment}";
            comment_debug_log('UPDATE SQL: ' . $update_sql);
            $db->query($update_sql, 0);
        }

        header('Location:' . $file . 'id=' . $object_id . '&status=2');
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
    echo '</form>';
    end_frame();
    foot();
    die();
}
?>
