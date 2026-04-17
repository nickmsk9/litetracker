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

function user_wall_supports_threads()
{
    return lt_column_exists('comments_users', 'parent_id');
}

function user_wall_fetch_rows($objectId)
{
    global $db;

    $objectId = (int) $objectId;
    if ($objectId <= 0) {
        return array();
    }

    $parentSelect = (user_wall_supports_threads() ? 'parent_id' : '0 AS parent_id');
    $sql = $db->query(
        "SELECT id, id_users, id_user, date, text, id_user_edit, date_edit, {$parentSelect}
         FROM comments_users
         WHERE id_users = {$objectId}
         ORDER BY date ASC, id ASC"
    );

    $rows = array();
    while ($row = $db->get_row($sql)) {
        $rows[] = $row;
    }

    return $rows;
}

function user_wall_build_tree($rows)
{
    $comments = array();
    foreach ($rows as $row) {
        $commentId = (int) ($row['id'] ?? 0);
        if ($commentId <= 0) {
            continue;
        }

        $row['id'] = $commentId;
        $row['parent_id'] = (int) ($row['parent_id'] ?? 0);
        $comments[$commentId] = $row;
    }

    $childrenMap = array();
    foreach ($comments as $commentId => $row) {
        $parentId = (int) ($row['parent_id'] ?? 0);
        if ($parentId > 0 && !isset($comments[$parentId])) {
            $parentId = 0;
        }

        if (!isset($childrenMap[$parentId])) {
            $childrenMap[$parentId] = array();
        }

        $childrenMap[$parentId][] = $commentId;
    }

    return user_wall_build_tree_branch(0, $comments, $childrenMap);
}

function user_wall_build_tree_branch($parentId, $comments, $childrenMap)
{
    $result = array();
    $childrenIds = $childrenMap[$parentId] ?? array();

    foreach ($childrenIds as $commentId) {
        if (empty($comments[$commentId])) {
            continue;
        }

        $node = $comments[$commentId];
        $node['children'] = user_wall_build_tree_branch($commentId, $comments, $childrenMap);
        $result[] = $node;
    }

    return $result;
}

function user_wall_render_list($objectId)
{
    $objectId = (int) $objectId;
    $tree = user_wall_build_tree(user_wall_fetch_rows($objectId));

    ob_start();
    echo '<div class="wall-comments-list">';

    if (!$tree) {
        echo '<div class="wall-comment-empty">На стене пока нет комментариев.</div>';
    } else {
        foreach ($tree as $node) {
            user_wall_render_node($node, $objectId, 0);
        }
    }

    echo '</div>';

    return ob_get_clean();
}

function user_wall_render_node($node, $objectId, $level = 0)
{
    global $USER, $PRIV, $language;

    $objectId = (int) $objectId;
    $level = max(0, (int) $level);
    $commentId = (int) ($node['id'] ?? 0);
    $commentUserId = (int) ($node['id_user'] ?? 0);
    $commentUser = get_user_info($commentUserId);
    $commentUserName = (string) ($commentUser['name'] ?? 'Unknown');
    $commentProfileHref = profile_href($commentUserId);
    $commentAvatarPath = 'public/images/default_avatar.gif';

    if (!empty($commentUser['avatar']) && is_file('public/avatars/small/' . $commentUser['avatar'])) {
        $commentAvatarPath = 'public/avatars/small/' . $commentUser['avatar'];
    }

    $commentDate = (!empty($node['date']) ? convent_date($node['date']) : '');
    $commentEditedLabel = (!empty($node['date_edit']) && $node['date_edit'] !== '0000-00-00 00:00:00')
        ? (($language['comments_3'] ?? 'Изменено:') . ' ' . convent_date($node['date_edit']))
        : '';
    $commentText = cleanhtml((string) ($node['text'] ?? ''));
    $commentTextRaw = (string) ($node['text'] ?? '');
    $children = (!empty($node['children']) && is_array($node['children']) ? $node['children'] : array());
    $canEdit = (!empty($USER['id']) && ((int) $USER['id'] === $commentUserId || !empty($PRIV['comments_edit'])));
    $canDelete = (!empty($USER['id']) && ((int) $USER['id'] === $commentUserId || !empty($PRIV['comments_delete'])));
    $canReport = (!empty($USER['id']) && (int) $USER['id'] !== $commentUserId);

    echo '<article class="wall-comment'.($children ? ' wall-comment-has-children' : '').'" data-comment-id="'.$commentId.'" data-wall-level="'.$level.'">';
    echo '<a class="wall-comment-avatar" href="'.$commentProfileHref.'">';
    echo '<img src="'.$commentAvatarPath.'" alt="'.htmlspecialchars($commentUserName, ENT_QUOTES, 'UTF-8').'" width="28" height="28">';
    echo '</a>';
    echo '<div class="wall-comment-body">';
    echo '<div class="wall-comment-meta">';
    echo '<a class="wall-comment-author" href="'.$commentProfileHref.'">'.htmlspecialchars($commentUserName, ENT_QUOTES, 'UTF-8').'</a>';
    echo '<span class="wall-comment-date">'.htmlspecialchars(($commentEditedLabel !== '' ? $commentEditedLabel : $commentDate), ENT_QUOTES, 'UTF-8').'</span>';
    echo '</div>';
    echo '<div class="wall-comment-text">'.$commentText.'</div>';
    echo '<textarea class="wall-comment-source" hidden>'.htmlspecialchars($commentTextRaw, ENT_QUOTES, 'UTF-8').'</textarea>';
    echo '<div class="wall-comment-editor-slot"></div>';
    echo '<div class="wall-comment-actions">';

    if (!empty($USER)) {
        echo '<button class="wall-comment-button" type="button" data-wall-reply="1" data-comment-id="'.$commentId.'" data-author-name="'.htmlspecialchars($commentUserName, ENT_QUOTES, 'UTF-8').'">Ответить</button>';
    }

    if ($canEdit) {
        echo '<button class="wall-comment-button" type="button" data-wall-edit="1" data-comment-id="'.$commentId.'">'.htmlspecialchars((string) ($language['comments_4'] ?? 'Редактировать'), ENT_QUOTES, 'UTF-8').'</button>';
    }

    if ($canDelete) {
        echo '<button class="wall-comment-button" type="button" data-wall-delete="1" data-comment-id="'.$commentId.'">'.htmlspecialchars((string) ($language['comments_5'] ?? 'Удалить'), ENT_QUOTES, 'UTF-8').'</button>';
    }

    echo '</div>';

    if ($canReport) {
        echo '<button class="wall-comment-report" type="button" title="Пожаловаться" aria-label="Пожаловаться">';
        echo '<span class="wall-comment-report-icon">&#9888;</span>';
        echo '<span class="wall-comment-report-label">Пожаловаться</span>';
        echo '</button>';
    }

    if ($children) {
        echo '<div class="wall-comment-children">';
        foreach ($children as $childNode) {
            user_wall_render_node($childNode, $objectId, $level + 1);
        }
        echo '</div>';
    }

    echo '</div>';
    echo '</article>';
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
