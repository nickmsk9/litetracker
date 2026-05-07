<?php
/*
===================================================================
LiteTracker Source
===================================================================
by Nick
-------------------------------------------------------------------
Назначение: Функции комментирования
===================================================================
*/

function comments_table_name($type)
{
    $type = preg_replace('~[^a-z0-9_]~i', '', (string) $type);

    return ($type !== '' ? 'comments_'.$type : '');
}

function comments_object_column($type)
{
    $type = preg_replace('~[^a-z0-9_]~i', '', (string) $type);

    return ($type !== '' ? 'id_'.$type : '');
}

function comments_supports_threads($type)
{
    $tableName = comments_table_name($type);

    return ($tableName !== '' && lt_column_exists($tableName, 'parent_id'));
}

function comments_ensure_thread_support($type)
{
    global $db;
    static $ready = array();

    $type = preg_replace('~[^a-z0-9_]~i', '', (string) $type);
    if ($type === '') {
        return false;
    }

    if (array_key_exists($type, $ready)) {
        return $ready[$type];
    }

    $tableName = comments_table_name($type);
    $objectColumn = comments_object_column($type);

    if (!lt_table_exists($tableName)) {
        $ready[$type] = false;
        return false;
    }

    if (!lt_column_exists($tableName, 'parent_id')) {
        $db->query("ALTER TABLE `".$tableName."` ADD COLUMN `parent_id` int NOT NULL DEFAULT '0' AFTER `text`");
    }

    $ready[$type] = lt_column_exists($tableName, 'parent_id');

    return $ready[$type];
}

function comments_reports_table_name()
{
    return 'comments_reports';
}

function comments_reports_ensure_table()
{
    global $db;
    static $ready = null;

    if ($ready !== null) {
        return $ready;
    }

    $tableName = comments_reports_table_name();
    $tableExists = $db->super_query("SHOW TABLES LIKE '".$db->safesql($tableName)."'");

    if (empty($tableExists)) {
        $db->query(
            "CREATE TABLE IF NOT EXISTS `".$tableName."` (
                `id` int NOT NULL AUTO_INCREMENT,
                `comment_type` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
                `comment_id` int NOT NULL,
                `object_id` int NOT NULL,
                `comment_user_id` int NOT NULL DEFAULT '0',
                `reporter_user_id` int NOT NULL DEFAULT '0',
                `comment_text_snapshot` text CHARACTER SET cp1251 COLLATE cp1251_bin NOT NULL,
                `status` varchar(16) CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT 'open',
                `created_at` datetime NOT NULL,
                `resolved_at` datetime DEFAULT NULL,
                `resolved_by_user_id` int NOT NULL DEFAULT '0',
                PRIMARY KEY (`id`),
                KEY `type_status_created` (`comment_type`, `status`, `created_at`),
                KEY `comment_reporter` (`comment_type`, `comment_id`, `reporter_user_id`),
                KEY `object_comment` (`comment_type`, `object_id`, `comment_id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin"
        );
    }

    $ready = true;

    return true;
}

// Форма добавления комментария
function addComment($type = '', $object_id = '', $file = '')
{
    global $USER, $language;

    if (empty($USER) || !is_array($USER)) {
        return;
    }

    $type = preg_replace('~[^a-z0-9_]~i', '', (string) $type);
    $object_id = (int) $object_id;
    $file = (string) $file;

    if ($type === '' || $object_id <= 0 || $file === '') {
        return;
    }

    $csrfScope = 'comments_'.$type.'_'.$object_id;

    $postedText = '';
    if (isset($_POST['text'])) {
        $postedText = (string) $_POST['text'];
    } elseif (isset($_POST['descr'])) {
        $postedText = (string) $_POST['descr'];
    }

    $rootDir = dirname(__DIR__, 2);

    $avatar = 'public/images/default_avatar.gif';
    if (!empty($USER['avatar']) && is_file($rootDir . '/public/avatars/small/' . $USER['avatar'])) {
        $avatar = 'public/avatars/small/' . $USER['avatar'];
    }

    echo '<form class="wall-form comment-thread-form" data-comment-form="1" method="post" action="comments.take.php">';
    echo '<div class="wall-reply-banner" data-comment-reply-banner="1" hidden>';
    echo '<span data-comment-reply-label="1"></span>';
    echo '<button class="wall-comment-button" data-comment-reply-cancel="1" type="button">Отмена</button>';
    echo '</div>';
    echo '<div class="wall-form-row">';
    echo '<div class="wall-form-avatar"><img src="' . $avatar . '" alt="' . htmlspecialchars((string) ($USER['name'] ?? ''), ENT_QUOTES, 'UTF-8') . '" width="28" height="28"></div>';
    echo '<div class="wall-form-body">';
    echo '<textarea class="wall-form-textarea" data-comment-textarea="1" name="text">' . htmlspecialchars($postedText, ENT_QUOTES, 'UTF-8') . '</textarea>';
    echo '<div class="wall-form-controls"><input class="wall-form-submit" value="Отправить" type="submit"></div>';
    echo '</div>';
    echo '</div>';
    echo '<input type="hidden" name="object_id" value="' . $object_id . '">';
    echo '<input type="hidden" name="type" value="' . htmlspecialchars($type, ENT_QUOTES, 'UTF-8') . '">';
    echo '<input type="hidden" name="file" value="' . htmlspecialchars($file, ENT_QUOTES, 'UTF-8') . '">';
    echo '<input type="hidden" name="parent_id" value="0" data-comment-parent="1">';
    echo '<input type="hidden" name="act" value="add">';
    echo lt_csrf_input($csrfScope);
    echo '</form>';
}

function lt_comment_deleted_placeholder($deletedByAdmin = true)
{
    return ($deletedByAdmin ? 'Комментарий удалён администрацией сайта' : 'Комментарий удалён пользователем сайта');
}

function lt_comment_deleted_meta($text)
{
    $text = trim((string) $text);
    $adminPlaceholder = lt_comment_deleted_placeholder(true);
    $userPlaceholder = lt_comment_deleted_placeholder(false);

    if ($text === $adminPlaceholder) {
        return array(
            'is_deleted' => true,
            'message' => $adminPlaceholder,
            'is_admin' => true,
        );
    }

    if ($text === $userPlaceholder) {
        return array(
            'is_deleted' => true,
            'message' => $userPlaceholder,
            'is_admin' => false,
        );
    }

    return array(
        'is_deleted' => false,
        'message' => '',
        'is_admin' => false,
    );
}

function comments_fetch_rows($type, $objectId, $limit = '', $desc = 0)
{
    global $db;

    $type = preg_replace('~[^a-z0-9_]~i', '', (string) $type);
    $objectId = (int) $objectId;
    $desc = (int) $desc;
    $limit = trim((string) $limit);

    if ($type === '' || $objectId <= 0) {
        return array();
    }

    comments_ensure_thread_support($type);

    $tableName = comments_table_name($type);
    $objectColumn = comments_object_column($type);
    $parentSelect = (comments_supports_threads($type) ? 'parent_id' : '0 AS parent_id');

    $sql = $db->query(
        "SELECT id, `{$objectColumn}` AS object_id, id_user, date, text, id_user_edit, date_edit, {$parentSelect}
         FROM `{$tableName}`
         WHERE `{$objectColumn}` = {$objectId}
         ORDER BY date " . ($desc ? 'DESC' : 'ASC') . ", id " . ($desc ? 'DESC' : 'ASC') . "
         {$limit}"
    );

    $rows = array();
    while ($row = $db->get_row($sql)) {
        $rows[] = $row;
    }

    return $rows;
}

function comments_build_tree($rows)
{
    $comments = array();
    foreach ((array) $rows as $row) {
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

    return comments_build_tree_branch(0, $comments, $childrenMap);
}

function comments_build_tree_branch($parentId, $comments, $childrenMap)
{
    $result = array();
    $childrenIds = $childrenMap[$parentId] ?? array();

    foreach ($childrenIds as $commentId) {
        if (empty($comments[$commentId])) {
            continue;
        }

        $node = $comments[$commentId];
        $node['children'] = comments_build_tree_branch($commentId, $comments, $childrenMap);
        $result[] = $node;
    }

    return $result;
}

function comments_user_can_edit($commentUserId, $commentDate, $type = '')
{
    global $USER, $PRIV;

    if (empty($USER['id'])) {
        return false;
    }

    if (!empty($PRIV['comments_edit'])) {
        return true;
    }

    if ((int) $USER['id'] !== (int) $commentUserId) {
        return false;
    }

    $commentTs = strtotime((string) $commentDate);
    return ($commentTs && $commentTs >= (time() - 3600));
}

function comments_render_node($node, $type, $objectId, $file, $level = 0)
{
    global $USER, $PRIV, $language;

    $type = preg_replace('~[^a-z0-9_]~i', '', (string) $type);
    $objectId = (int) $objectId;
    $level = max(0, (int) $level);
    $commentId = (int) ($node['id'] ?? 0);
    $commentUserId = (int) ($node['id_user'] ?? 0);
    $commentUser = get_user_info($commentUserId);
    $commentUserName = (string) ($commentUser['name'] ?? 'Unknown');
    $commentUserNameSafe = htmlspecialchars($commentUserName, ENT_QUOTES, 'UTF-8');
    $commentAuthorHtml = get_user_color((int) ($commentUser['class'] ?? 0), $commentUserNameSafe);
    $commentProfileHref = profile_href($commentUserId);
    $rootDir = dirname(__DIR__, 2);
    $commentAvatarPath = 'public/images/default_avatar.gif';

    if (!empty($commentUser['avatar']) && is_file($rootDir . '/public/avatars/small/' . $commentUser['avatar'])) {
        $commentAvatarPath = 'public/avatars/small/' . $commentUser['avatar'];
    } elseif (!empty($commentUser['avatar']) && is_file($rootDir . '/public/avatars/' . $commentUser['avatar'])) {
        $commentAvatarPath = 'public/avatars/' . $commentUser['avatar'];
    }

    $commentDate = (!empty($node['date']) ? convent_date($node['date']) : '');
    $commentEditedLabel = (!empty($node['date_edit']) && $node['date_edit'] !== '0000-00-00 00:00:00')
        ? (($language['comments_3'] ?? 'Изменено:') . ' ' . convent_date($node['date_edit']))
        : '';
    $commentTextRaw = (string) ($node['text'] ?? '');
    $commentDeletedMeta = lt_comment_deleted_meta($commentTextRaw);
    $commentDeleted = !empty($commentDeletedMeta['is_deleted']);
    $commentTextHtml = ($commentDeleted
        ? '<span class="comment-entry-deleted-label">'.htmlspecialchars($commentDeletedMeta['message'], ENT_QUOTES, 'UTF-8').'</span>'
        : cleanhtml($commentTextRaw));
    $children = (!empty($node['children']) && is_array($node['children']) ? $node['children'] : array());
    $commentCanReply = (!empty($USER['id']) && !$commentDeleted);
    $commentCanEdit = (!empty($USER['id']) && !$commentDeleted && comments_user_can_edit($commentUserId, (string) ($node['date'] ?? ''), $type));
    $commentCanDelete = (!empty($USER['id']) && !$commentDeleted && (!empty($PRIV['comments_delete']) || ($type !== 'users' && (int) $USER['id'] === $commentUserId)));
    $commentCanReport = (!empty($USER['id']) && (int) $USER['id'] !== $commentUserId && !$commentDeleted);
    $commentHasSideActions = ($commentCanEdit || $commentCanDelete || $commentCanReport);
    $csrfTokenRaw = lt_csrf_token('comments_'.$type.'_'.$objectId);
    $csrfToken = rawurlencode($csrfTokenRaw);
    $csrfTokenSafe = htmlspecialchars($csrfTokenRaw, ENT_QUOTES, 'UTF-8');
    $reactionObjectType = 'comment_'.$type;
    $reactionStats = lt_reaction_stats($reactionObjectType, $commentId, (int) ($USER['id'] ?? 0));
    $commentCanReact = (!empty($USER['id']) && !$commentDeleted && lt_user_has_plus($USER));

    echo '<article class="wall-comment comment-entry'.($children ? ' wall-comment-has-children' : '').($commentDeleted ? ' comment-entry-deleted' : '').'" id="wall-comment-'.$commentId.'" data-comment-id="'.$commentId.'" data-comment-type="'.htmlspecialchars($type, ENT_QUOTES, 'UTF-8').'" data-comment-object-id="'.$objectId.'" data-wall-level="'.$level.'">';
    echo '<a class="wall-comment-avatar comment-entry-avatar" href="'.$commentProfileHref.'">';
    echo '<img src="'.$commentAvatarPath.'" alt="'.$commentUserNameSafe.'" width="28" height="28">';
    echo '</a>';
    echo '<div class="wall-comment-body comment-entry-body'.($commentHasSideActions ? ' comment-entry-body-has-side-actions' : '').'">';
    echo '<div class="wall-comment-meta comment-entry-meta">';
    echo '<a class="wall-comment-author comment-entry-author" href="'.$commentProfileHref.'">'.$commentAuthorHtml.'</a>';
    echo '<span class="wall-comment-date comment-entry-date">'.htmlspecialchars(($commentEditedLabel !== '' ? $commentEditedLabel : $commentDate), ENT_QUOTES, 'UTF-8').'</span>';
    echo '</div>';

    if ($commentHasSideActions) {
        echo '<div class="comment-side-actions">';

        if ($commentCanReport) {
            echo '<a class="comment-side-button comment-side-button-report wall-comment-report" href="comments.take.php?type='.urlencode($type).'&amp;object_id='.$objectId.'&amp;id_comment='.$commentId.'&amp;act=report&amp;file='.htmlspecialchars($file, ENT_QUOTES, 'UTF-8').'&amp;csrf_token='.$csrfToken.'" title="Пожаловаться" aria-label="Пожаловаться" data-wall-report="1" data-comment-id="'.$commentId.'" data-csrf-token="'.$csrfTokenSafe.'">';
            echo '<span class="wall-comment-report-icon">&#9888;</span>';
            echo '<span class="wall-comment-report-label">Пожаловаться</span>';
            echo '</a>';
        }

        if ($commentCanEdit) {
            echo '<a class="comment-side-button comment-side-button-edit" href="comments.take.php?type='.urlencode($type).'&amp;object_id='.$objectId.'&amp;id_comment='.$commentId.'&amp;act=edit&amp;file='.htmlspecialchars($file, ENT_QUOTES, 'UTF-8').'&amp;csrf_token='.$csrfToken.'" data-wall-edit="1" data-comment-id="'.$commentId.'" data-csrf-token="'.$csrfTokenSafe.'">'.htmlspecialchars((string) ($language['comments_4'] ?? 'Редактировать'), ENT_QUOTES, 'UTF-8').'</a>';
        }

        if ($commentCanDelete) {
            echo '<a class="comment-side-button comment-side-button-delete" href="comments.take.php?type='.urlencode($type).'&amp;object_id='.$objectId.'&amp;id_comment='.$commentId.'&amp;act=delete&amp;file='.htmlspecialchars($file, ENT_QUOTES, 'UTF-8').'&amp;csrf_token='.$csrfToken.'" data-wall-delete="1" data-comment-id="'.$commentId.'" data-csrf-token="'.$csrfTokenSafe.'">'.htmlspecialchars((string) ($language['comments_5'] ?? 'Удалить'), ENT_QUOTES, 'UTF-8').'</a>';
        }

        echo '</div>';
    }

    echo '<div class="wall-comment-text comment-entry-text'.($commentDeleted ? ' comment-entry-text-deleted' : '').'">'.$commentTextHtml.'</div>';
    echo '<textarea class="wall-comment-source" hidden>'.htmlspecialchars($commentTextRaw, ENT_QUOTES, 'UTF-8').'</textarea>';
    echo '<div class="wall-comment-editor-slot"></div>';

    if ($commentCanReply) {
        echo '<div class="wall-comment-actions comment-entry-actions">';
        echo '<button class="wall-comment-button comment-reply-button" type="button" data-comment-reply="1" data-wall-reply="1" data-comment-id="'.$commentId.'" data-author-name="'.$commentUserNameSafe.'">Ответить</button>';
        echo '<span class="plus-reactions">';
        if ($commentCanReact) {
            echo '<a class="plus-reaction-button'.($reactionStats['user'] === 'like' ? ' plus-reaction-button-active' : '').'" href="comments.take.php?type='.urlencode($type).'&amp;object_id='.$objectId.'&amp;id_comment='.$commentId.'&amp;act=react&amp;reaction=like&amp;file='.htmlspecialchars($file, ENT_QUOTES, 'UTF-8').'&amp;csrf_token='.$csrfToken.'" data-wall-react="like" data-comment-id="'.$commentId.'" data-csrf-token="'.$csrfTokenSafe.'">Нравится <span data-reaction-count="like">'.$reactionStats['like'].'</span></a>';
            echo '<a class="plus-reaction-button'.($reactionStats['user'] === 'dislike' ? ' plus-reaction-button-active' : '').'" href="comments.take.php?type='.urlencode($type).'&amp;object_id='.$objectId.'&amp;id_comment='.$commentId.'&amp;act=react&amp;reaction=dislike&amp;file='.htmlspecialchars($file, ENT_QUOTES, 'UTF-8').'&amp;csrf_token='.$csrfToken.'" data-wall-react="dislike" data-comment-id="'.$commentId.'" data-csrf-token="'.$csrfTokenSafe.'">Не нравится <span data-reaction-count="dislike">'.$reactionStats['dislike'].'</span></a>';
        } else {
            echo '<span class="plus-reaction-count">Нравится <span data-reaction-count="like">'.$reactionStats['like'].'</span></span>';
            echo '<span class="plus-reaction-count">Не нравится <span data-reaction-count="dislike">'.$reactionStats['dislike'].'</span></span>';
        }
        $reactionListHref = 'comments.take.php?type='.urlencode($type).'&amp;object_id='.$objectId.'&amp;id_comment='.$commentId.'&amp;act=reaction_list&amp;file='.htmlspecialchars($file, ENT_QUOTES, 'UTF-8');
        echo '<a class="plus-reaction-list-link" href="'.$reactionListHref.'" data-plus-reaction-list="1" data-reaction-object-type="'.htmlspecialchars($reactionObjectType, ENT_QUOTES, 'UTF-8').'" data-reaction-object-id="'.$commentId.'">Кто оценил</a>';
        echo '</span>';
        echo '</div>';
    }

    if ($children) {
        echo '<div class="wall-comment-children">';
        foreach ($children as $childNode) {
            comments_render_node($childNode, $type, $objectId, $file, $level + 1);
        }
        echo '</div>';
    }

    echo '</div>';
    echo '</article>';
}

function comments_render_list_html($type, $objectId, $file = '', $desc = 0, $limit = '')
{
    $type = preg_replace('~[^a-z0-9_]~i', '', (string) $type);
    $objectId = (int) $objectId;
    $rows = comments_fetch_rows($type, $objectId, $limit, $desc);
    $tree = comments_build_tree($rows);

    ob_start();
    echo '<div class="comment-stream'.($type === 'users' ? ' wall-comments-list' : ' torrent-comments-list').'" data-comment-stream="1">';

    if (!$tree) {
        echo '<div class="'.($type === 'users' ? 'wall-comment-empty' : 'torrent-comment-empty').'">'.($type === 'users' ? 'На стене пока нет комментариев.' : 'Комментариев пока нет.').'</div>';
    } else {
        foreach ($tree as $node) {
            comments_render_node($node, $type, $objectId, $file, 0);
        }
    }

    echo '</div>';

    return ob_get_clean();
}

// Список комментариев
function listComment($type = '', $object_id = '', $file = '', $desc = 0)
{
    global $db;

    $type = preg_replace('~[^a-z0-9_]~i', '', (string) $type);
    $object_id = (int) $object_id;
    $file = (string) $file;
    $desc = (int) $desc;

    if ($type === '' || $object_id <= 0) {
        return;
    }

    comments_ensure_thread_support($type);

    echo '<script src="/public/js/comments.js"></script>';

    $tableName = comments_table_name($type);
    $objectColumn = comments_object_column($type);
    $countRow = $db->super_query("SELECT COUNT(*) AS cnt FROM `{$tableName}` WHERE `{$objectColumn}` = {$object_id}");
    $count = isset($countRow['cnt']) ? (int) $countRow['cnt'] : 0;
    $threaded = comments_supports_threads($type);
    $pagertop = '';
    $pagerbottom = '';
    $limit = '';
    $showPager = false;

    if (!$threaded) {
        list($pagertop, $pagerbottom, $limit) = pager('20', $count, $file . 'id=' . $object_id . '&', array('lastpagedefault' => 1));
        $showPager = ($count > 20);
    }

    $fileSafe = htmlspecialchars($file, ENT_QUOTES, 'UTF-8');
    echo '<div class="comment-thread-root" data-comment-thread="1" data-comment-type="'.htmlspecialchars($type, ENT_QUOTES, 'UTF-8').'" data-object-id="'.$object_id.'" data-file="'.$fileSafe.'">';
    echo '<div class="comment-ajax-notice" data-comment-notice="1" hidden></div>';

    if ($showPager) {
        echo $pagertop;
    }

    echo comments_render_list_html($type, $object_id, $file, $desc, $limit);

    if ($showPager) {
        echo $pagerbottom;
    }

    addComment($type, $object_id, $file);
    echo '</div>';
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

    $tableName = comments_table_name($type);
    $objectColumn = comments_object_column($type);
    $parentSelect = (comments_supports_threads($type) ? 'parent_id' : '0 AS parent_id');

    return "SELECT `{$tableName}`.*, `{$tableName}`.id AS comment_id, {$parentSelect}
            FROM `{$tableName}`
            WHERE `{$tableName}`.`{$objectColumn}` = {$object_id}
            ORDER BY `{$tableName}`.date " . ($desc ? 'DESC' : 'ASC') . "
            {$limit}";
}

function user_wall_supports_threads()
{
    return comments_supports_threads('users');
}

function user_wall_reports_can_moderate()
{
    global $PRIV;

    return (!empty($PRIV['comments_edit']) || !empty($PRIV['comments_delete']) || !empty($PRIV['setting_user']));
}

function user_wall_reports_notify_moderators($reportId, $objectId, $commentId, $reporterName = '')
{
    global $db;

    $reportId = (int) $reportId;
    $objectId = (int) $objectId;
    $commentId = (int) $commentId;
    $reporterName = trim((string) $reporterName);

    if ($reportId <= 0 || $objectId <= 0 || $commentId <= 0) {
        return 0;
    }

    $sql = $db->query(
        "SELECT DISTINCT u.id
         FROM users AS u
         INNER JOIN priv AS p ON p.id = u.class
         WHERE p.comments_edit = 1
            OR p.comments_delete = 1
            OR p.setting_user = 1"
    );

    $sent = 0;
    $subject = 'Новая жалоба на комментарий';
    $text = 'Поступила новая жалоба на комментарий стены профиля.'."\n";
    if ($reporterName !== '') {
        $text .= 'Отправитель: [b]'.$reporterName.'[/b]'."\n";
    }
    $text .= '[url=wall_reports.php?id='.$reportId.']Открыть жалобу[/url]'."\n";
    $text .= '[url='.profile_href($objectId).'#wall-comment-'.$commentId.']Открыть комментарий[/url]';

    while ($row = $db->get_row($sql)) {
        if (send_msg($subject, $text, (int) $row['id'], 0)) {
            $sent++;
        }
    }
    $db->free($sql);

    return $sent;
}

function user_wall_reports_table_name()
{
    return 'comments_users_reports';
}

function user_wall_reports_ensure_table()
{
    global $db;
    static $ready = null;

    if ($ready !== null) {
        return $ready;
    }

    $tableName = user_wall_reports_table_name();
    $tableExists = $db->super_query("SHOW TABLES LIKE '".$db->safesql($tableName)."'");

    if (empty($tableExists)) {
        $db->query(
            "CREATE TABLE IF NOT EXISTS `".$tableName."` (
                `id` int NOT NULL AUTO_INCREMENT,
                `comment_id` int NOT NULL,
                `object_id` int NOT NULL,
                `comment_user_id` int NOT NULL DEFAULT '0',
                `reporter_user_id` int NOT NULL DEFAULT '0',
                `comment_text_snapshot` text CHARACTER SET cp1251 COLLATE cp1251_bin NOT NULL,
                `status` varchar(16) CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT 'open',
                `created_at` datetime NOT NULL,
                `resolved_at` datetime DEFAULT NULL,
                `resolved_by_user_id` int NOT NULL DEFAULT '0',
                PRIMARY KEY (`id`),
                KEY `status_created` (`status`, `created_at`),
                KEY `comment_reporter` (`comment_id`, `reporter_user_id`),
                KEY `object_comment` (`object_id`, `comment_id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin"
        );
    }

    $ready = true;

    return true;
}

function user_wall_reports_href($status = 'open')
{
    $params = array();
    $status = trim((string) $status);

    if ($status !== '' && $status !== 'open') {
        $params['status'] = $status;
    }

    return 'wall_reports.php'.($params ? '?'.http_build_query($params) : '');
}

function user_wall_fetch_rows($objectId)
{
    return comments_fetch_rows('users', (int) $objectId);
}

function user_wall_build_tree($rows)
{
    return comments_build_tree($rows);
}

function user_wall_build_tree_branch($parentId, $comments, $childrenMap)
{
    return comments_build_tree_branch($parentId, $comments, $childrenMap);
}

function user_wall_render_list($objectId)
{
    return comments_render_list_html('users', (int) $objectId, 'profile.php?id='.(int) $objectId.'&');
}

function user_wall_render_node($node, $objectId, $level = 0)
{
    comments_render_node($node, 'users', (int) $objectId, 'profile.php?id='.(int) $objectId.'&', $level);
}

// Статусы
function comment_status()
{
    return;
}
?>
