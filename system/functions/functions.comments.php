<?php
/*
===================================================================
LiteTracker Source
===================================================================
by jenaDI
-------------------------------------------------------------------
Назначение: Функции комментирования
===================================================================
*/


// Форма добавления комментария
function addComment($type = '', $object_id = '', $file = '')
{
    global $USER, $language;

    if (empty($USER) || !is_array($USER)) {
        return;
    }

    $type = (string) $type;
    $object_id = (int) $object_id;
    $file = (string) $file;

    // Поддерживаем оба варианта, чтобы не ломать старую логику:
    // некоторые части системы могли отправлять text, а некоторые descr.
    $postedText = '';
    if (isset($_POST['text'])) {
        $postedText = (string) $_POST['text'];
    } elseif (isset($_POST['descr'])) {
        $postedText = (string) $_POST['descr'];
    }

    if ($type === 'users') {
        $avatar = 'public/images/default_avatar.gif';
        if (!empty($USER['avatar']) && is_file('public/avatars/small/' . $USER['avatar'])) {
            $avatar = 'public/avatars/small/' . $USER['avatar'];
        }

        echo '<form class="wall-form" name="addComment" method="post" action="comments.take.php">';
        echo '<div class="wall-form-row">';
        echo '<div class="wall-form-avatar"><img src="' . $avatar . '" alt="' . htmlspecialchars((string) ($USER['name'] ?? ''), ENT_QUOTES, 'UTF-8') . '" width="28" height="28"></div>';
        echo '<div class="wall-form-body">';
        echo '<textarea class="wall-form-textarea" id="wall-comment-text" name="text">' . htmlspecialchars($postedText, ENT_QUOTES, 'UTF-8') . '</textarea>';
        echo '<div class="wall-form-controls"><input class="wall-form-submit" value="Отправить" type="submit"></div>';
        echo '</div>';
        echo '</div>';
        echo '<input type="hidden" name="object_id" value="' . $object_id . '">';
        echo '<input type="hidden" name="type" value="' . htmlspecialchars($type, ENT_QUOTES, 'UTF-8') . '">';
        echo '<input type="hidden" name="file" value="' . htmlspecialchars($file, ENT_QUOTES, 'UTF-8') . '">';
        echo '<input type="hidden" name="act" value="add">';
        echo '</form>';
        return;
    }

    echo '<form name="addComment" method="post" action="comments.take.php">';
    textbb('text', $postedText, '95%', '300px');
    echo '<br><input value="' . htmlspecialchars((string) ($language['comments_1'] ?? 'Отправить'), ENT_QUOTES, 'UTF-8') . '" type="submit">';
    echo '<input type="hidden" name="object_id" value="' . $object_id . '">';
    echo '<input type="hidden" name="type" value="' . htmlspecialchars($type, ENT_QUOTES, 'UTF-8') . '">';
    echo '<input type="hidden" name="file" value="' . htmlspecialchars($file, ENT_QUOTES, 'UTF-8') . '">';
    echo '<input type="hidden" name="act" value="add">';
    echo '</form>';
    echo '<br>';
}


// Список комментариев
function listComment($type = '', $object_id = '', $file = '', $desc = 0)
{
    global $USER, $PRIV, $config, $db, $language;

    $type = preg_replace('~[^a-z0-9_]~i', '', (string) $type);
    $object_id = (int) $object_id;
    $file = (string) $file;
    $desc = (int) $desc;

    if ($type === '' || $object_id <= 0) {
        return;
    }

    echo '<script src="/public/js/comments.js"></script>';

    $countRow = $db->super_query("SELECT COUNT(*) AS cnt FROM comments_{$type} WHERE id_{$type} = {$object_id}");
    $count = isset($countRow['cnt']) ? (int) $countRow['cnt'] : 0;

    list($pagertop, $pagerbottom, $limit) = pager('20', $count, $file . 'id=' . $object_id . '&', array('lastpagedefault' => 1));
    $showPager = ($count > 20);

    $query = queryComment($type, $object_id, $limit, $desc);
    $sql = $db->query($query);

    if (!$db->num_rows($sql)) {
        if ($type === 'users') {
            echo '<div class="wall-comment-empty">На стене пока нет комментариев.</div>';
        } else {
            msg($language['comments_2'] ?? 'Комментариев пока нет.', '', 'error');
        }
    } else {
        if ($showPager) {
            echo $pagertop;
        }

        if ($type === 'users') {
            echo '<div class="wall-comments-list">';
        }

        while ($arr = $db->get_row($sql)) {
            $id = isset($arr['comment_id']) ? (int) $arr['comment_id'] : 0;
            if ($id <= 0) {
                continue;
            }
            $text = cleanhtml((string) ($arr['text'] ?? ''));

            $user = get_user_info((int) ($arr['id_user'] ?? 0));
            $user_id = isset($user['id']) ? (int) $user['id'] : 0;
            $user_name = (string) ($user['name'] ?? 'Unknown');
            $user_class = isset($user['class']) ? $user['class'] : 0;

            if ($type === 'users') {
                $avatarPath = 'public/images/default_avatar.gif';
                if (!empty($user['avatar']) && is_file('public/avatars/small/' . $user['avatar'])) {
                    $avatarPath = 'public/avatars/small/' . $user['avatar'];
                }
                $avatar = '<img src="' . $avatarPath . '" border="0" width="28" height="28" alt="' . htmlspecialchars($user_name, ENT_QUOTES, 'UTF-8') . '">';
            } else {
                if (!empty($user['avatar']) && is_file('public/avatars/' . $user['avatar'])) {
                    $avatar = '<center><img src="public/avatars/' . htmlspecialchars($user['avatar'], ENT_QUOTES, 'UTF-8') . '" border="0" width="50"></center>';
                } else {
                    $avatar = '<center><img src="public/images/default_avatar.gif" border="0" width="50"></center>';
                }
            }

            $date = !empty($arr['date']) ? convent_date($arr['date']) : '';
            $append_edit = (!empty($arr['date_edit']) && $arr['date_edit'] !== '0000-00-00 00:00:00')
                ? (($language['comments_3'] ?? 'Изменено:') . ' ' . convent_date($arr['date_edit']))
                : '';

            if ($append_edit === '' && empty($arr['date'])) {
                $append_edit = '';
            }

            $templateFile = 'templates/' . $config['template'] . '/tpl.comments.php';
            if (is_file($templateFile)) {
                require $templateFile;
            } else {
                echo '<div class="wall-comment-fallback">';
                echo '<strong>' . htmlspecialchars($user_name, ENT_QUOTES, 'UTF-8') . '</strong><br>';
                echo $text;
                echo '</div>';
            }
        }

        if ($type === 'users') {
            echo '</div>';
        }

        if ($showPager) {
            echo $pagerbottom;
        }
    }

    addComment($type, $object_id, $file);
}


// Запрос списка комментариев
function queryComment($type, $object_id, $limit, $desc)
{
    $type = preg_replace('~[^a-z0-9_]~i', '', (string) $type);
    $object_id = (int) $object_id;
    $desc = (int) $desc;
    $limit = trim((string) $limit);

    if ($type === '' || $object_id <= 0) {
        return 'SELECT 1 WHERE 0';
    }

    $query = "SELECT comments_{$type}.*, comments_{$type}.id AS comment_id
              FROM comments_{$type}
              WHERE comments_{$type}.id_{$type} = {$object_id}
              ORDER BY comments_{$type}.date " . ($desc ? 'DESC' : 'ASC') . "
              {$limit}";

    return $query;
}


// Статусы
function comment_status()
{
    global $language;

    $status = isset($_GET['status']) ? (string) $_GET['status'] : '';

    if ($status === '1') {
        msg($language['comments_6'] ?? 'Комментарий успешно добавлен.');
    } elseif ($status === '2') {
        msg($language['comments_7'] ?? 'Комментарий успешно изменен.');
    } elseif ($status === '3') {
        msg($language['comments_8'] ?? 'Комментарий удален.');
    }
}
?>
