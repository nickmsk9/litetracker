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

    $hasParentColumn = lt_column_exists($tableName, 'parent_id');
    if (!$hasParentColumn) {
        $db->query("ALTER TABLE `".$tableName."` ADD COLUMN `parent_id` int NOT NULL DEFAULT '0' AFTER `text`");
        lt_schema_cache_delete(lt_schema_column_cache_key($tableName, 'parent_id'));
        $hasParentColumn = lt_column_exists($tableName, 'parent_id', true);
    }

    $ready[$type] = $hasParentColumn;

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
    if (!lt_table_exists($tableName)) {
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
        lt_schema_cache_delete(lt_schema_table_cache_key($tableName));
    }

    $ready = true;

    return true;
}

function comments_index_exists($tableName, $indexName)
{
    static $cache = array();

    $tableName = preg_replace('~[^a-z0-9_]~i', '', (string) $tableName);
    $indexName = preg_replace('~[^a-z0-9_]~i', '', (string) $indexName);
    if ($tableName === '' || $indexName === '') {
        return false;
    }

    $key = $tableName.'.'.$indexName;
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    $cache[$key] = lt_schema_has_index($tableName, $indexName);

    return $cache[$key];
}

function comments_ensure_modern_tables()
{
    global $db;
    static $ready = null;

    if ($ready !== null) {
        return $ready;
    }

    // Memcached fast-path: skip all SHOW TABLES checks when tables were verified recently
    $schemaCacheKey = 'schema:comments_modern_tables:ready_v1';
    if (lt_schema_cache_get($schemaCacheKey) === true) {
        $ready = true;
        return true;
    }

    if (!lt_table_exists('comment_pins')) {
        $db->query(
            "CREATE TABLE IF NOT EXISTS `comment_pins` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `context_type` VARCHAR(32) NOT NULL,
                `context_id` INT UNSIGNED NOT NULL,
                `comment_id` INT UNSIGNED NOT NULL,
                `pinned_by` INT UNSIGNED NOT NULL,
                `pinned_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `context_pin` (`context_type`, `context_id`),
                KEY `comment_pin` (`context_type`, `comment_id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin",
            0
        );
        lt_schema_cache_delete(lt_schema_table_cache_key('comment_pins'));
    }

    if (!lt_table_exists('comment_reactions')) {
        $db->query(
            "CREATE TABLE IF NOT EXISTS `comment_reactions` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `context_type` VARCHAR(32) NOT NULL,
                `comment_id` INT UNSIGNED NOT NULL,
                `user_id` INT UNSIGNED NOT NULL,
                `reaction` VARCHAR(16) NOT NULL,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `user_comment_reaction` (`context_type`, `comment_id`, `user_id`),
                KEY `comment_reaction` (`context_type`, `comment_id`, `reaction`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin",
            0
        );
        lt_schema_cache_delete(lt_schema_table_cache_key('comment_reactions'));
    }

    if (!lt_table_exists('comment_edit_history')) {
        $db->query(
            "CREATE TABLE IF NOT EXISTS `comment_edit_history` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `context_type` VARCHAR(32) NOT NULL,
                `comment_id` INT UNSIGNED NOT NULL,
                `editor_id` INT UNSIGNED NOT NULL,
                `old_text` TEXT NULL,
                `new_text` TEXT NULL,
                `edited_at` DATETIME NOT NULL,
                `edit_reason` TEXT NULL,
                PRIMARY KEY (`id`),
                KEY `comment_history` (`context_type`, `comment_id`, `edited_at`),
                KEY `editor_history` (`editor_id`, `edited_at`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin",
            0
        );
        lt_schema_cache_delete(lt_schema_table_cache_key('comment_edit_history'));
    }

    $ready = (lt_table_exists('comment_pins', true) && lt_table_exists('comment_reactions', true) && lt_table_exists('comment_edit_history', true));
    if ($ready) {
        lt_schema_cache_set($schemaCacheKey, true);
    }
    return $ready;
}

function comments_ensure_moderation_columns($type)
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

    // Memcached fast-path: skip SHOW COLUMNS/INDEX checks when verified recently
    $schemaCacheKey = 'schema:comments_moderation:'.$type.':ready_v1';
    if (lt_schema_cache_get($schemaCacheKey) === true) {
        $ready[$type] = true;
        return true;
    }

    $tableName = comments_table_name($type);
    if (!lt_table_exists($tableName)) {
        $ready[$type] = false;
        return false;
    }

    $columns = array(
        'is_deleted' => "ALTER TABLE `".$tableName."` ADD COLUMN `is_deleted` TINYINT(1) NOT NULL DEFAULT 0",
        'deleted_by' => "ALTER TABLE `".$tableName."` ADD COLUMN `deleted_by` INT UNSIGNED NULL",
        'deleted_at' => "ALTER TABLE `".$tableName."` ADD COLUMN `deleted_at` DATETIME NULL",
        'delete_reason' => "ALTER TABLE `".$tableName."` ADD COLUMN `delete_reason` TEXT NULL",
    );

    foreach ($columns as $column => $sql) {
        if (!lt_column_exists($tableName, $column)) {
            $db->query($sql, 0);
            lt_schema_cache_delete(lt_schema_column_cache_key($tableName, $column));
        }
    }

    $indexName = 'idx_'.$tableName.'_deleted';
    if (!comments_index_exists($tableName, $indexName)) {
        $db->query("ALTER TABLE `".$tableName."` ADD KEY `".$indexName."` (`is_deleted`, `date`)", 0);
        lt_schema_cache_delete('schema:index:'.$tableName.':'.$indexName.':exists');
    }

    $ready[$type] = lt_column_exists($tableName, 'is_deleted', true);
    if ($ready[$type]) {
        lt_schema_cache_set($schemaCacheKey, true);
    }
    return $ready[$type];
}

function comments_ensure_modern_schema($type = '')
{
    comments_ensure_modern_tables();
    if ($type !== '') {
        comments_ensure_moderation_columns($type);
    }
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

    $rootDir = LT_ROOT_PATH;

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

function lt_comment_deleted_meta_from_row($comment)
{
    $comment = (array) $comment;
    $legacy = lt_comment_deleted_meta((string) ($comment['text'] ?? ''));
    $isDeleted = (!empty($comment['is_deleted']) || !empty($legacy['is_deleted']));
    $deletedByAdmin = (!empty($legacy['is_admin']) || (int) ($comment['deleted_by'] ?? 0) > 0);
    $message = ($isDeleted ? lt_comment_deleted_placeholder($deletedByAdmin) : '');

    return array(
        'is_deleted' => $isDeleted,
        'message' => $message,
        'is_admin' => $deletedByAdmin,
        'reason' => trim((string) ($comment['delete_reason'] ?? '')),
        'deleted_by' => (int) ($comment['deleted_by'] ?? 0),
        'deleted_at' => (string) ($comment['deleted_at'] ?? ''),
    );
}

function comments_sort_mode($sort = '')
{
    $sort = trim((string) $sort);
    return (in_array($sort, array('new', 'old', 'popular'), true) ? $sort : 'old');
}

function comments_current_sort()
{
    return comments_sort_mode($_REQUEST['comments_sort'] ?? $_GET['comments_sort'] ?? 'old');
}

function comments_user_can_moderate()
{
    global $PRIV;

    return (!empty($PRIV['comments_edit']) || !empty($PRIV['comments_delete']) || !empty($PRIV['EDIT_PRIV']) || !empty($PRIV['setting_user']));
}

function lt_comment_notify_reply($type, $objectId, $commentId, $parentId, $actorId)
{
    if (function_exists('lt_notifications_handle_comment_added')) {
        lt_notifications_handle_comment_added($type, $objectId, $commentId, $parentId, $actorId);
    }
}

function lt_comment_notify_torrent_owner($type, $objectId, $commentId, $parentId, $actorId)
{
    // Covered by lt_notifications_handle_comment_added() when notifications are enabled.
}

function lt_comment_notify_pinned($type, $objectId, $commentId, $commentUserId, $actorId)
{
    if (function_exists('lt_notifications_handle_comment_pinned')) {
        lt_notifications_handle_comment_pinned($type, $objectId, $commentId, $commentUserId, $actorId);
    }
}

function lt_comment_notify_deleted($type, $objectId, $commentId, $commentUserId, $actorId, $deletedByAdmin)
{
    if (function_exists('lt_notifications_handle_comment_deleted')) {
        lt_notifications_handle_comment_deleted($type, $objectId, $commentId, $commentUserId, $actorId, $deletedByAdmin);
    }
}

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
                return '&#'.mb_ord($matches[0], 'UTF-8').';';
            }

            if (!function_exists('iconv')) {
                return '';
            }

            $encoded = iconv('UTF-8', 'UCS-4BE', $matches[0]);
            if ($encoded === false || strlen($encoded) !== 4) {
                return '';
            }

            $codepoint = unpack('N', $encoded);
            if (empty($codepoint[1])) {
                return '';
            }

            return '&#'.(int) $codepoint[1].';';
        },
        $text
    );
}

function lt_comment_return_url($file, $objectId, $suffix = '')
{
    $file = trim((string) $file);
    $objectId = (int) $objectId;
    $suffix = (string) $suffix;

    if ($file === '') {
        return '';
    }

    $url = $file;
    if (strpos($url, 'id=') === false) {
        $url .= 'id='.$objectId;
    }

    if ($suffix !== '') {
        if ($suffix[0] === '#') {
            $url .= $suffix;
        } else {
            $needsGlue = (substr($url, -1) !== '&' && substr($url, -1) !== '?' && strpos($suffix, '&') !== 0);
            $url .= ($needsGlue ? '&' : '').ltrim($suffix, '&');
        }
    }

    return $url;
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

function lt_comment_notify_wall_owner($wallOwnerId, $actor = array())
{
    global $db;

    $wallOwnerId = (int) $wallOwnerId;
    $actor = (is_array($actor) ? $actor : array());
    if ($wallOwnerId <= 0 || $wallOwnerId === (int) ($actor['id'] ?? 0)) {
        return;
    }

    $wallOwner = $db->super_query("SELECT id, name, notify_comments FROM users WHERE id = ".$wallOwnerId);
    if (empty($wallOwner['id']) || empty($wallOwner['notify_comments'])) {
        return;
    }

    send_msg(
        'Новый комментарий на стене',
        'Пользователь [b]'.(string) ($actor['name'] ?? '').'[/b] оставил новый комментарий на вашей стене.'."\n".'Ссылка: '.profile_href($wallOwnerId),
        (int) $wallOwner['id'],
        0
    );
}

function comments_context_url($type, $objectId, $commentId = 0)
{
    $type = preg_replace('~[^a-z0-9_]~i', '', (string) $type);
    $objectId = (int) $objectId;
    $commentId = (int) $commentId;

    if ($type === 'torrents') {
        return 'details.php?id='.$objectId.($commentId > 0 ? '#wall-comment-'.$commentId : '');
    }

    if ($type === 'users') {
        return profile_href($objectId).($commentId > 0 ? '#wall-comment-'.$commentId : '');
    }

    if ($type === 'news') {
        return 'news.php?id='.$objectId.($commentId > 0 ? '#wall-comment-'.$commentId : '');
    }

    if ($type === 'faq') {
        return 'faq.php?id='.$objectId.($commentId > 0 ? '#wall-comment-'.$commentId : '');
    }

    return ($commentId > 0 ? '#wall-comment-'.$commentId : '');
}

function lt_comment_has_real_edit($comment)
{
    $comment = (array) $comment;
    $editUserId = (int) ($comment['id_user_edit'] ?? 0);
    $editDate = trim((string) ($comment['date_edit'] ?? ''));
    $createdDate = trim((string) ($comment['date'] ?? ''));

    if ($editUserId <= 0) {
        return false;
    }

    if ($editDate === '' || $editDate === '0000-00-00 00:00:00') {
        return false;
    }

    return ($editDate !== $createdDate);
}

function comments_validate_text($text, $user = array())
{
    $text = trim((string) $text);
    $plain = trim(preg_replace('/\s+/u', ' ', strip_tags($text)));
    $length = function_exists('mb_strlen') ? mb_strlen($plain, 'UTF-8') : strlen($plain);

    if ($length < 2) {
        return 'Комментарий слишком короткий.';
    }

    if ($length > 5000) {
        return 'Комментарий слишком длинный. Максимум 5000 символов.';
    }

    if (preg_match_all('~https?://|www\.~i', $text, $m) > 5) {
        return 'Слишком много ссылок в одном комментарии.';
    }

    return '';
}

function comments_is_duplicate_recent($type, $objectId, $userId, $text)
{
    global $db;

    $type = preg_replace('~[^a-z0-9_]~i', '', (string) $type);
    $objectId = (int) $objectId;
    $userId = (int) $userId;
    $text = trim((string) $text);
    if ($type === '' || $objectId <= 0 || $userId <= 0 || $text === '') {
        return false;
    }

    $tableName = comments_table_name($type);
    $objectColumn = comments_object_column($type);
    $row = $db->super_query(
        "SELECT text
         FROM `{$tableName}`
         WHERE `{$objectColumn}` = {$objectId}
           AND id_user = {$userId}
         ORDER BY date DESC, id DESC
         LIMIT 1"
    );

    return (trim((string) ($row['text'] ?? '')) === $text);
}

function comments_history_add($type, $commentId, $editorId, $oldText, $newText, $reason = '')
{
    global $db;

    comments_ensure_modern_tables();
    $type = preg_replace('~[^a-z0-9_]~i', '', (string) $type);
    $commentId = (int) $commentId;
    $editorId = (int) $editorId;
    if ($type === '' || $commentId <= 0 || $editorId <= 0) {
        return false;
    }

    $db->query(
        "INSERT INTO comment_edit_history
            (context_type, comment_id, editor_id, old_text, new_text, edited_at, edit_reason)
         VALUES (
            '".$db->safesql($type)."',
            {$commentId},
            {$editorId},
            '".$db->safesql((string) $oldText)."',
            '".$db->safesql((string) $newText)."',
            NOW(),
            ".(trim((string) $reason) !== '' ? "'".$db->safesql((string) $reason)."'" : 'NULL')."
         )",
        0
    );

    return true;
}

function comments_history_fetch($type, $commentId)
{
    global $db;

    comments_ensure_modern_tables();
    $type = preg_replace('~[^a-z0-9_]~i', '', (string) $type);
    $commentId = (int) $commentId;
    if ($type === '' || $commentId <= 0) {
        return array();
    }

    $sql = $db->query(
        "SELECT h.*, u.name AS editor_name
         FROM comment_edit_history AS h
         LEFT JOIN users AS u ON u.id = h.editor_id
         WHERE h.context_type = '".$db->safesql($type)."'
           AND h.comment_id = {$commentId}
         ORDER BY h.edited_at DESC, h.id DESC
         LIMIT 50",
        0
    );

    $rows = array();
    if ($sql) {
        while ($row = $db->get_row($sql)) {
            $rows[] = $row;
        }
        $db->free($sql);
    }

    return $rows;
}

function comments_pinned_row($type, $objectId)
{
    global $db;

    comments_ensure_modern_tables();
    $type = preg_replace('~[^a-z0-9_]~i', '', (string) $type);
    $objectId = (int) $objectId;
    if ($type === '' || $objectId <= 0) {
        return array();
    }

    $cacheKey = lt_cache_key_comments_pin($type, $objectId);
    $cached = lt_cache_get($cacheKey, lt_cache_key_comments_ns());
    if ($cached !== false && is_array($cached)) {
        return $cached;
    }

    $row = $db->super_query(
        "SELECT *
         FROM comment_pins
         WHERE context_type = '".$db->safesql($type)."'
           AND context_id = {$objectId}
         LIMIT 1"
    );
    if (!is_array($row)) {
        $row = array();
    }

    lt_cache_set($cacheKey, $row, 300, lt_cache_key_comments_ns());

    return $row;
}

function comments_set_pin($type, $objectId, $commentId, $moderatorId)
{
    global $db;

    comments_ensure_modern_schema($type);
    $type = preg_replace('~[^a-z0-9_]~i', '', (string) $type);
    $objectId = (int) $objectId;
    $commentId = (int) $commentId;
    $moderatorId = (int) $moderatorId;
    if ($type === '' || $objectId <= 0 || $commentId <= 0 || $moderatorId <= 0) {
        return false;
    }

    $db->query(
        "REPLACE INTO comment_pins
            (context_type, context_id, comment_id, pinned_by, pinned_at)
         VALUES (
            '".$db->safesql($type)."',
            {$objectId},
            {$commentId},
            {$moderatorId},
            NOW()
         )",
        0
    );

    comments_invalidate_payload($type, $objectId);

    return true;
}

function comments_unpin($type, $objectId)
{
    global $db;

    comments_ensure_modern_tables();
    $type = preg_replace('~[^a-z0-9_]~i', '', (string) $type);
    $objectId = (int) $objectId;
    if ($type === '' || $objectId <= 0) {
        return false;
    }

    $db->query("DELETE FROM comment_pins WHERE context_type = '".$db->safesql($type)."' AND context_id = {$objectId}", 0);
    comments_invalidate_payload($type, $objectId);

    return true;
}

function comments_reaction_counts($type, $commentIds, $userId = 0)
{
    $type = preg_replace('~[^a-z0-9_]~i', '', (string) $type);
    $ids = array();
    foreach ((array) $commentIds as $id) {
        $id = (int) $id;
        if ($id > 0) {
            $ids[$id] = $id;
        }
    }

    if ($type === '' || !$ids) {
        return array();
    }

    $result = comments_reaction_summary_for_ids($type, $ids);
    foreach (comments_current_user_reactions($type, $ids, (int) $userId) as $commentId => $reaction) {
        if (isset($result[$commentId])) {
            $result[$commentId]['user'] = $reaction;
        }
    }

    return $result;
}

function comments_reaction_summary_for_ids($type, array $ids, $objectId = 0)
{
    global $db;

    comments_ensure_modern_tables();
    $type = preg_replace('~[^a-z0-9_]~i', '', (string) $type);
    $objectId = (int) $objectId;
    $result = array();
    foreach ($ids as $id) {
        $result[$id] = array('like' => 0, 'dislike' => 0, 'user' => '');
    }
    if ($type === '' || !$ids) {
        return $result;
    }

    if ($objectId > 0) {
        $cached = lt_cache_get(lt_cache_key_comments_reactions_summary($type, $objectId), lt_cache_key_comments_ns());
        if ($cached !== false && is_array($cached)) {
            foreach ($cached as $commentId => $counts) {
                $commentId = (int) $commentId;
                if (isset($result[$commentId])) {
                    $result[$commentId]['like'] = (int) ($counts['like'] ?? 0);
                    $result[$commentId]['dislike'] = (int) ($counts['dislike'] ?? 0);
                }
            }
            return $result;
        }
    }

    $sql = $db->query(
        "SELECT comment_id, reaction, COUNT(*) AS c
         FROM comment_reactions
         WHERE context_type = '".$db->safesql($type)."'
           AND comment_id IN (".implode(',', $ids).")
           AND reaction IN ('like', 'dislike')
         GROUP BY comment_id, reaction",
        0
    );
    if ($sql) {
        while ($row = $db->get_row($sql)) {
            $commentId = (int) ($row['comment_id'] ?? 0);
            $reaction = (string) ($row['reaction'] ?? '');
            if (isset($result[$commentId][$reaction])) {
                $result[$commentId][$reaction] = (int) ($row['c'] ?? 0);
            }
        }
        $db->free($sql);
    }

    if ($objectId > 0) {
        lt_cache_set(lt_cache_key_comments_reactions_summary($type, $objectId), $result, 60, lt_cache_key_comments_ns());
    }

    return $result;
}

function comments_current_user_reactions($type, array $ids, $userId)
{
    global $db;

    static $requestCache = array();

    comments_ensure_modern_tables();
    $type = preg_replace('~[^a-z0-9_]~i', '', (string) $type);
    $userId = (int) $userId;
    if ($type === '' || !$ids || $userId <= 0) {
        return array();
    }

    $safeIds = array();
    foreach ($ids as $id) {
        $id = (int) $id;
        if ($id > 0) {
            $safeIds[$id] = $id;
        }
    }
    if (!$safeIds) {
        return array();
    }

    $cacheKey = $type.':'.$userId.':'.md5(implode(',', $safeIds));
    if (isset($requestCache[$cacheKey])) {
        return $requestCache[$cacheKey];
    }

    $result = array();
    $sql = $db->query(
        "SELECT comment_id, reaction
         FROM comment_reactions
         WHERE context_type = '".$db->safesql($type)."'
           AND comment_id IN (".implode(',', $safeIds).")
           AND user_id = {$userId}",
        0
    );
    if ($sql) {
        while ($row = $db->get_row($sql)) {
            $commentId = (int) ($row['comment_id'] ?? 0);
            if (isset($safeIds[$commentId])) {
                $result[$commentId] = (string) ($row['reaction'] ?? '');
            }
        }
        $db->free($sql);
    }

    $requestCache[$cacheKey] = $result;

    return $result;
}

function comments_invalidate_payload($type, $objectId)
{
    $type = preg_replace('~[^a-z0-9_]~i', '', (string) $type);
    $objectId = (int) $objectId;
    if ($type === '' || $objectId <= 0) {
        return;
    }

    if (function_exists('lt_cache_invalidate_comments')) {
        lt_cache_invalidate_comments($type, $objectId);
    }
}

function comments_payload_defaults()
{
    return array(
        'rows' => array(),
        'ids' => array(),
        'user_ids' => array(),
        'pin' => array(),
    );
}

function comments_payload_apply_reaction_summary(array $rows, array $reactionCounts)
{
    foreach ($rows as $idx => $row) {
        $commentId = (int) ($row['id'] ?? 0);
        if (!empty($reactionCounts[$commentId])) {
            $rows[$idx]['like_count'] = (int) ($reactionCounts[$commentId]['like'] ?? 0);
            $rows[$idx]['dislike_count'] = (int) ($reactionCounts[$commentId]['dislike'] ?? 0);
        } else {
            $rows[$idx]['like_count'] = 0;
            $rows[$idx]['dislike_count'] = 0;
        }
        $rows[$idx]['user_reaction'] = '';
        $rows[$idx]['comment_score'] = (int) $rows[$idx]['like_count'] - (int) $rows[$idx]['dislike_count'];
    }

    return $rows;
}

function comments_apply_user_reactions(array $rows, $type, array $ids, $userId)
{
    $userReactions = comments_current_user_reactions($type, $ids, (int) $userId);
    if (!$userReactions) {
        return $rows;
    }

    foreach ($rows as $idx => $row) {
        $commentId = (int) ($row['id'] ?? 0);
        if (isset($userReactions[$commentId])) {
            $rows[$idx]['user_reaction'] = (string) $userReactions[$commentId];
        }
    }

    return $rows;
}

function lt_comments_payload($contextType, $contextId, $currentUserId = 0)
{
    global $db;

    static $requestCache = array();

    $type = preg_replace('~[^a-z0-9_]~i', '', (string) $contextType);
    $objectId = (int) $contextId;
    if ($type === '' || $objectId <= 0) {
        return comments_payload_defaults();
    }

    $requestKey = $type.':'.$objectId;
    if (isset($requestCache[$requestKey])) {
        return $requestCache[$requestKey];
    }

    comments_ensure_thread_support($type);
    comments_ensure_modern_schema($type);

    $cacheKey = lt_cache_key_comments_payload($type, $objectId);
    $cached = lt_cache_get($cacheKey, lt_cache_key_comments_ns());
    if ($cached !== false && is_array($cached)) {
        $payload = array_merge(comments_payload_defaults(), $cached);
        $requestCache[$requestKey] = $payload;
        return $payload;
    }

    $tableName = comments_table_name($type);
    $objectColumn = comments_object_column($type);
    $parentSelect = (comments_supports_threads($type) ? 'parent_id' : '0 AS parent_id');

    $sql = $db->query(
        "SELECT id, `{$objectColumn}` AS object_id, id_user, date, text, id_user_edit, date_edit, {$parentSelect},
                is_deleted, deleted_by, deleted_at, delete_reason
         FROM `{$tableName}`
         WHERE `{$objectColumn}` = {$objectId}
         ORDER BY date ASC, id ASC"
    );

    $rows = array();
    $ids = array();
    $userIds = array();
    while ($row = $db->get_row($sql)) {
        $commentId = (int) ($row['id'] ?? 0);
        $userId = (int) ($row['id_user'] ?? 0);
        if ($commentId > 0) {
            $ids[$commentId] = $commentId;
        }
        if ($userId > 0) {
            $userIds[$userId] = $userId;
        }
        $rows[] = $row;
    }
    $db->free($sql);

    $rows = comments_payload_apply_reaction_summary($rows, comments_reaction_summary_for_ids($type, $ids, $objectId));
    $payload = array(
        'rows' => $rows,
        'ids' => array_values($ids),
        'user_ids' => array_values($userIds),
        'pin' => comments_pinned_row($type, $objectId),
    );

    lt_cache_set($cacheKey, $payload, 120, lt_cache_key_comments_ns());
    $requestCache[$requestKey] = $payload;

    return $payload;
}

function comments_fetch_rows($type, $objectId, $limit = '', $desc = 0, $sort = '')
{
    global $db;

    $type = preg_replace('~[^a-z0-9_]~i', '', (string) $type);
    $objectId = (int) $objectId;
    $desc = (int) $desc;
    $limit = trim((string) $limit);
    $sort = comments_sort_mode($sort !== '' ? $sort : ($desc ? 'new' : 'old'));

    if ($type === '' || $objectId <= 0) {
        return array();
    }

    if ($limit === '') {
        $payload = lt_comments_payload($type, $objectId, (int) ($GLOBALS['USER']['id'] ?? 0));
        return comments_apply_user_reactions(
            (array) ($payload['rows'] ?? array()),
            $type,
            (array) ($payload['ids'] ?? array()),
            (int) ($GLOBALS['USER']['id'] ?? 0)
        );
    }

    comments_ensure_thread_support($type);
    comments_ensure_modern_schema($type);

    $tableName = comments_table_name($type);
    $objectColumn = comments_object_column($type);
    $parentSelect = (comments_supports_threads($type) ? 'parent_id' : '0 AS parent_id');

    $sql = $db->query(
        "SELECT id, `{$objectColumn}` AS object_id, id_user, date, text, id_user_edit, date_edit, {$parentSelect},
                is_deleted, deleted_by, deleted_at, delete_reason
         FROM `{$tableName}`
         WHERE `{$objectColumn}` = {$objectId}
         ORDER BY date ASC, id ASC
         {$limit}"
    );

    $rows = array();
    $ids = array();
    while ($row = $db->get_row($sql)) {
        $row['like_count'] = 0;
        $row['dislike_count'] = 0;
        $row['user_reaction'] = '';
        $ids[] = (int) ($row['id'] ?? 0);
        $rows[] = $row;
    }

    $reactionCounts = comments_reaction_counts($type, $ids, (int) ($GLOBALS['USER']['id'] ?? 0));
    foreach ($rows as $idx => $row) {
        $commentId = (int) ($row['id'] ?? 0);
        if (!empty($reactionCounts[$commentId])) {
            $rows[$idx]['like_count'] = (int) ($reactionCounts[$commentId]['like'] ?? 0);
            $rows[$idx]['dislike_count'] = (int) ($reactionCounts[$commentId]['dislike'] ?? 0);
            $rows[$idx]['user_reaction'] = (string) ($reactionCounts[$commentId]['user'] ?? '');
        }
        $rows[$idx]['comment_score'] = (int) $rows[$idx]['like_count'] - (int) $rows[$idx]['dislike_count'];
    }

    return $rows;
}

function comments_build_tree($rows, $sort = 'old')
{
    $comments = array();
    $sort = comments_sort_mode($sort);
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

    return comments_build_tree_branch(0, $comments, $childrenMap, $sort);
}

function comments_build_tree_branch($parentId, $comments, $childrenMap, $sort = 'old')
{
    $result = array();
    $childrenIds = $childrenMap[$parentId] ?? array();
    $sort = comments_sort_mode($sort);

    usort($childrenIds, function ($a, $b) use ($comments, $parentId, $sort) {
        $left = (array) ($comments[$a] ?? array());
        $right = (array) ($comments[$b] ?? array());
        $leftTime = strtotime((string) ($left['date'] ?? '')) ?: 0;
        $rightTime = strtotime((string) ($right['date'] ?? '')) ?: 0;

        if ((int) $parentId === 0 && $sort === 'new') {
            return ($rightTime <=> $leftTime) ?: ((int) $b <=> (int) $a);
        }

        if ((int) $parentId === 0 && $sort === 'popular') {
            $leftScore = (int) ($left['comment_score'] ?? 0);
            $rightScore = (int) ($right['comment_score'] ?? 0);
            return ($rightScore <=> $leftScore) ?: ($rightTime <=> $leftTime) ?: ((int) $b <=> (int) $a);
        }

        return ($leftTime <=> $rightTime) ?: ((int) $a <=> (int) $b);
    });

    foreach ($childrenIds as $commentId) {
        if (empty($comments[$commentId])) {
            continue;
        }

        $node = $comments[$commentId];
        $node['children'] = comments_build_tree_branch($commentId, $comments, $childrenMap, $sort);
        $result[] = $node;
    }

    return $result;
}

function comments_tree_find_node($tree, $commentId)
{
    foreach ((array) $tree as $node) {
        if ((int) ($node['id'] ?? 0) === (int) $commentId) {
            return $node;
        }

        $found = comments_tree_find_node((array) ($node['children'] ?? array()), $commentId);
        if ($found) {
            return $found;
        }
    }

    return null;
}

function comments_tree_without_node($tree, $commentId)
{
    $result = array();

    foreach ((array) $tree as $node) {
        if ((int) ($node['id'] ?? 0) === (int) $commentId) {
            continue;
        }

        $node['children'] = comments_tree_without_node((array) ($node['children'] ?? array()), $commentId);
        $result[] = $node;
    }

    return $result;
}

function comments_preload_users($rows)
{
    global $db, $memcached;

    $ids = array();
    foreach ((array) $rows as $row) {
        $userId = (int) ($row['id_user'] ?? 0);
        if ($userId > 0) {
            $ids[$userId] = $userId;
        }
    }

    if (!$ids) {
        return array();
    }

    $users = array();
    $missingIds = array();
    $cacheNs = lt_cache_key_user_ns();
    foreach ($ids as $userId) {
        $cachedUser = lt_cache_get(lt_cache_key_user($userId), $cacheNs);
        if (is_array($cachedUser) && !empty($cachedUser['id'])) {
            $users[(int) $cachedUser['id']] = $cachedUser;
            continue;
        }

        $missingIds[$userId] = $userId;
    }

    if (!$missingIds) {
        return $users;
    }

    $sql = $db->query("SELECT * FROM users WHERE id IN (".implode(',', $missingIds).")");
    while ($user = $db->get_row($sql)) {
        $users[(int) $user['id']] = $user;
        lt_cache_set(lt_cache_key_user((int) $user['id']), $user, rand(1500, 3000), $cacheNs);
    }
    $db->free($sql);

    return $users;
}

function comments_preload_privileges($usersById)
{
    $classes = array(0 => 0);
    foreach ((array) $usersById as $user) {
        $class = (int) ($user['class'] ?? 0);
        $classes[$class] = $class;
    }

    $privileges = array();
    foreach ($classes as $class) {
        if ($class <= 0) {
            $privileges[$class] = array(
                'NAME' => 'Гость',
                'COLOR' => '000000',
                'EDIT_PRIV' => 0,
            );
            continue;
        }

        $privileges[$class] = get_priv_info($class);
    }

    return $privileges;
}

function comments_user_color_html($class, $username, $privilegesByClass)
{
    $class = (int) $class;
    $priv = (array) ($privilegesByClass[$class] ?? array());
    if (!$priv) {
        $priv = array(
            'NAME' => 'Гость',
            'COLOR' => '000000',
            'EDIT_PRIV' => 0,
        );
    }

    $nameHtml = (!empty($priv['EDIT_PRIV']) ? '<span class="lt-emoji-font">'.$username.'</span>' : $username);

    return '<font title="'.htmlspecialchars((string) ($priv['NAME'] ?? ''), ENT_QUOTES, 'UTF-8').'" style="color:#'.htmlspecialchars((string) ($priv['COLOR'] ?? '000000'), ENT_QUOTES, 'UTF-8').'">'.$nameHtml.'</font>';
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

function comments_render_node($node, $type, $objectId, $file, $level = 0, $context = array())
{
    global $USER, $PRIV, $language;

    $type = preg_replace('~[^a-z0-9_]~i', '', (string) $type);
    $objectId = (int) $objectId;
    $level = max(0, (int) $level);
    $commentId = (int) ($node['id'] ?? 0);
    $commentUserId = (int) ($node['id_user'] ?? 0);
    $usersById = (array) ($context['users_by_id'] ?? array());
    $privilegesByClass = (array) ($context['privileges_by_class'] ?? array());
    $pinnedCommentId = (int) ($context['pinned_comment_id'] ?? 0);
    $isPinned = ($pinnedCommentId > 0 && $pinnedCommentId === $commentId);
    $isPinnedClone = !empty($context['pinned_clone']);
    $commentUser = (array) ($usersById[$commentUserId] ?? array());
    $commentUserName = (string) ($commentUser['name'] ?? 'Unknown');
    $commentUserNameSafe = htmlspecialchars($commentUserName, ENT_QUOTES, 'UTF-8');
    $commentAuthorHtml = comments_user_color_html((int) ($commentUser['class'] ?? 0), $commentUserNameSafe, $privilegesByClass);
    $commentProfileHref = ($commentUser ? profile_href($commentUser) : ($commentUserId > 0 ? 'profile.php?id='.$commentUserId : 'profile.php'));
    $rootDir = LT_ROOT_PATH;
    $commentAvatarPath = 'public/images/default_avatar.gif';

    if (!empty($commentUser['avatar']) && is_file($rootDir . '/public/avatars/small/' . $commentUser['avatar'])) {
        $commentAvatarPath = 'public/avatars/small/' . $commentUser['avatar'];
    } elseif (!empty($commentUser['avatar']) && is_file($rootDir . '/public/avatars/' . $commentUser['avatar'])) {
        $commentAvatarPath = 'public/avatars/' . $commentUser['avatar'];
    }

    $commentDate = (!empty($node['date']) ? convent_date($node['date']) : '');
    $commentEditedLabel = lt_comment_has_real_edit($node)
        ? (($language['comments_3'] ?? 'Изменено:') . ' ' . convent_date($node['date_edit']))
        : '';
    $commentTextRaw = (string) ($node['text'] ?? '');
    $commentDeletedMeta = lt_comment_deleted_meta_from_row($node);
    $commentDeleted = !empty($commentDeletedMeta['is_deleted']);
    $commentTextHtml = ($commentDeleted
        ? '<span class="comment-entry-deleted-label">'.htmlspecialchars($commentDeletedMeta['message'], ENT_QUOTES, 'UTF-8').'</span>'
        : cleanhtml($commentTextRaw));
    if ($commentDeleted && trim((string) ($commentDeletedMeta['reason'] ?? '')) !== '' && (comments_user_can_moderate() || (int) ($USER['id'] ?? 0) === $commentUserId)) {
        $commentTextHtml .= '<div class="comment-delete-reason">Причина: '.htmlspecialchars((string) $commentDeletedMeta['reason'], ENT_QUOTES, 'UTF-8').'</div>';
    }
    $children = (!empty($node['children']) && is_array($node['children']) ? $node['children'] : array());
    $commentCanReply = (!empty($USER['id']) && !$commentDeleted);
    $commentCanEdit = (!empty($USER['id']) && !$commentDeleted && comments_user_can_edit($commentUserId, (string) ($node['date'] ?? ''), $type));
    $commentCanDelete = (!empty($USER['id']) && !$commentDeleted && (!empty($PRIV['comments_delete']) || ($type !== 'users' && (int) $USER['id'] === $commentUserId)));
    $commentCanReport = (!empty($USER['id']) && (int) $USER['id'] !== $commentUserId && !$commentDeleted);
    $commentCanModerate = comments_user_can_moderate();
    $commentCanPin = ($commentCanModerate && !$commentDeleted);
    $commentCanRestore = ($commentCanModerate && $commentDeleted);
    $commentCanReact = (!empty($USER['id']) && !$commentDeleted && (int) $USER['id'] !== $commentUserId);
    $commentHasHistory = ($commentCanModerate && lt_comment_has_real_edit($node));
    $commentHasSideActions = ($commentCanEdit || $commentCanDelete || $commentCanReport || $commentCanPin || $commentCanRestore || $commentHasHistory);
    $csrfTokenRaw = lt_csrf_token('comments_'.$type.'_'.$objectId);
    $csrfToken = rawurlencode($csrfTokenRaw);
    $csrfTokenSafe = htmlspecialchars($csrfTokenRaw, ENT_QUOTES, 'UTF-8');
    $avatarSize = ($level > 0 ? 40 : 48);
    $showReactionFooter = $commentCanReply;
    $showFooter = ($commentHasSideActions || $showReactionFooter);
    echo '<article class="wall-comment comment-entry'.($children ? ' wall-comment-has-children' : '').($commentDeleted ? ' comment-entry-deleted' : '').($isPinned ? ' comment-entry-pinned' : '').($isPinnedClone ? ' comment-entry-pinned-clone' : '').'" id="'.($isPinnedClone ? 'wall-comment-pinned-'.$commentId : 'wall-comment-'.$commentId).'" data-comment-id="'.$commentId.'" data-comment-type="'.htmlspecialchars($type, ENT_QUOTES, 'UTF-8').'" data-comment-object-id="'.$objectId.'" data-wall-level="'.$level.'">';
    echo '<a class="wall-comment-avatar comment-entry-avatar" href="'.$commentProfileHref.'">';
    echo '<img src="'.$commentAvatarPath.'" alt="'.$commentUserNameSafe.'" width="'.$avatarSize.'" height="'.$avatarSize.'">';
    echo '</a>';
    echo '<div class="wall-comment-body comment-entry-body'.($commentHasSideActions ? ' comment-entry-body-has-side-actions' : '').'">';
    echo '<div class="wall-comment-meta comment-entry-meta">';
    echo '<a class="wall-comment-author comment-entry-author" href="'.$commentProfileHref.'">'.$commentAuthorHtml.'</a>';
    if ($isPinned || $isPinnedClone) {
        echo '<span class="comment-pinned-badge">Закреплено</span>';
    }
    echo '<span class="wall-comment-date comment-entry-date">'.htmlspecialchars(($commentEditedLabel !== '' ? $commentEditedLabel : $commentDate), ENT_QUOTES, 'UTF-8').'</span>';
    echo '</div>';

    echo '<div class="wall-comment-text comment-entry-text'.($commentDeleted ? ' comment-entry-text-deleted' : '').'">'.$commentTextHtml.'</div>';
    echo '<textarea class="wall-comment-source" hidden>'.htmlspecialchars($commentTextRaw, ENT_QUOTES, 'UTF-8').'</textarea>';
    echo '<div class="wall-comment-editor-slot"></div>';

    if ($showFooter) {
        echo '<div class="wall-comment-footer">';
        echo '<div class="wall-comment-actions comment-entry-actions">';
        if ($commentCanReply) {
            echo '<button class="wall-comment-button comment-reply-button" type="button" data-comment-reply="1" data-wall-reply="1" data-comment-id="'.$commentId.'" data-author-name="'.$commentUserNameSafe.'">Ответить</button>';
        }
        if ($commentHasSideActions) {
            echo '<div class="comment-side-actions'.(!$commentCanReply ? ' comment-side-actions-visible' : '').'">';

            if ($commentCanReport) {
                echo '<a class="comment-side-button comment-side-button-report wall-comment-report" href="comments.take.php?type='.urlencode($type).'&amp;object_id='.$objectId.'&amp;id_comment='.$commentId.'&amp;act=report&amp;file='.htmlspecialchars($file, ENT_QUOTES, 'UTF-8').'&amp;csrf_token='.$csrfToken.'" title="Пожаловаться" aria-label="Пожаловаться" data-wall-report="1" data-comment-id="'.$commentId.'" data-csrf-token="'.$csrfTokenSafe.'">';
                echo '<span class="wall-comment-report-icon">&#9888;</span>';
                echo '</a>';
            }

            if ($commentCanEdit) {
                echo '<a class="comment-side-button comment-side-button-edit" href="comments.take.php?type='.urlencode($type).'&amp;object_id='.$objectId.'&amp;id_comment='.$commentId.'&amp;act=edit&amp;file='.htmlspecialchars($file, ENT_QUOTES, 'UTF-8').'&amp;csrf_token='.$csrfToken.'" data-wall-edit="1" data-comment-id="'.$commentId.'" data-csrf-token="'.$csrfTokenSafe.'" data-require-reason="'.($commentCanModerate && (int) ($USER['id'] ?? 0) !== $commentUserId ? '1' : '0').'">'.htmlspecialchars((string) ($language['comments_4'] ?? 'Редактировать'), ENT_QUOTES, 'UTF-8').'</a>';
            }

            if ($commentCanDelete) {
                echo '<a class="comment-side-button comment-side-button-delete" href="comments.take.php?type='.urlencode($type).'&amp;object_id='.$objectId.'&amp;id_comment='.$commentId.'&amp;act=delete&amp;file='.htmlspecialchars($file, ENT_QUOTES, 'UTF-8').'&amp;csrf_token='.$csrfToken.'" data-wall-delete="1" data-comment-id="'.$commentId.'" data-csrf-token="'.$csrfTokenSafe.'" data-require-reason="'.(!empty($PRIV['comments_delete']) && ((int) ($USER['id'] ?? 0) !== $commentUserId || $type === 'users') ? '1' : '0').'">'.htmlspecialchars((string) ($language['comments_5'] ?? 'Удалить'), ENT_QUOTES, 'UTF-8').'</a>';
            }

            if ($commentCanPin) {
                $pinAction = ($isPinned ? 'unpin' : 'pin');
                echo '<a class="comment-side-button comment-side-button-pin" href="#" data-wall-pin="1" data-pin-action="'.$pinAction.'" data-comment-id="'.$commentId.'" data-csrf-token="'.$csrfTokenSafe.'">'.($isPinned ? 'Открепить' : 'Закрепить').'</a>';
            }

            if ($commentCanRestore) {
                echo '<a class="comment-side-button comment-side-button-restore" href="#" data-wall-restore="1" data-comment-id="'.$commentId.'" data-csrf-token="'.$csrfTokenSafe.'">Восстановить</a>';
            }

            if ($commentHasHistory) {
                echo '<a class="comment-side-button comment-side-button-history" href="#" data-wall-history="1" data-comment-id="'.$commentId.'" data-csrf-token="'.$csrfTokenSafe.'">История</a>';
            }

            echo '</div>';
        }
        echo '</div>';
        if ($showReactionFooter) {
            echo '<div class="comment-reactions" aria-label="Реакции комментария">';
            if ($commentCanReact) {
                $likeActive = ((string) ($node['user_reaction'] ?? '') === 'like');
                $dislikeActive = ((string) ($node['user_reaction'] ?? '') === 'dislike');
                echo '<button class="wall-comment-button comment-reaction-button comment-reaction-like'.($likeActive ? ' comment-reaction-active' : '').'" type="button" data-comment-react="like" data-comment-id="'.$commentId.'" data-csrf-token="'.$csrfTokenSafe.'" title="Нравится (👍)">👍 <span class="comment-reaction-count">'.(int) ($node['like_count'] ?? 0).'</span></button>';
                echo '<button class="wall-comment-button comment-reaction-button comment-reaction-dislike'.($dislikeActive ? ' comment-reaction-active' : '').'" type="button" data-comment-react="dislike" data-comment-id="'.$commentId.'" data-csrf-token="'.$csrfTokenSafe.'" title="Не нравится (👎)">👎 <span class="comment-reaction-count">'.(int) ($node['dislike_count'] ?? 0).'</span></button>';
            } else {
                $likeCount = (int) ($node['like_count'] ?? 0);
                $dislikeCount = (int) ($node['dislike_count'] ?? 0);
                echo '<span class="comment-reaction-summary">👍 '.$likeCount.'</span>';
                echo '<span class="comment-reaction-summary">👎 '.$dislikeCount.'</span>';
            }
            echo '</div>';
        }
        echo '</div>';
    }

    if ($children) {
        $childContext = $context;
        unset($childContext['pinned_clone']);
        echo '<div class="wall-comment-children" data-comment-children="1">';
        foreach ($children as $childNode) {
            comments_render_node($childNode, $type, $objectId, $file, $level + 1, $childContext);
        }
        echo '</div>';
    }

    echo '</div>';
    echo '</article>';
}

function comments_render_list_html($type, $objectId, $file = '', $desc = 0, $limit = '', $sort = '')
{
    $type = preg_replace('~[^a-z0-9_]~i', '', (string) $type);
    $objectId = (int) $objectId;
    $sort = comments_sort_mode($sort !== '' ? $sort : ($desc ? 'new' : comments_current_sort()));
    $rows = comments_fetch_rows($type, $objectId, $limit, $desc, $sort);
    $tree = comments_build_tree($rows, $sort);
    $usersById = comments_preload_users($rows);
    $pinned = comments_pinned_row($type, $objectId);
    $pinnedCommentId = (int) ($pinned['comment_id'] ?? 0);
    $rowsById = array();
    foreach ($rows as $row) {
        $rowsById[(int) ($row['id'] ?? 0)] = $row;
    }
    $context = array(
        'users_by_id' => $usersById,
        'privileges_by_class' => comments_preload_privileges($usersById),
        'pinned_comment_id' => $pinnedCommentId,
    );

    ob_start();
    echo '<div class="comment-stream'.($type === 'users' ? ' wall-comments-list' : ' torrent-comments-list').'" data-comment-stream="1">';
    echo '<div class="comment-toolbar">';
    echo '<div class="comment-toolbar-summary"><span class="comment-toolbar-count">'.count($rows).'</span><span class="comment-toolbar-caption">комментариев</span></div>';
    echo '<label class="comment-sort-label"><span class="comment-sort-text">Упорядочить</span><select class="comment-sort-select" data-comment-sort="1">';
    foreach (array('old' => 'Старые', 'new' => 'Новые', 'popular' => 'Популярные') as $sortKey => $sortLabel) {
        echo '<option value="'.$sortKey.'"'.($sort === $sortKey ? ' selected' : '').'>'.$sortLabel.'</option>';
    }
    echo '</select></label>';
    echo '</div>';

    if ($pinnedCommentId > 0 && !empty($rowsById[$pinnedCommentId])) {
        $pinnedNode = comments_tree_find_node($tree, $pinnedCommentId) ?: $rowsById[$pinnedCommentId];
        $tree = comments_tree_without_node($tree, $pinnedCommentId);
        $pinnedContext = $context;
        $pinnedContext['pinned_clone'] = true;
        echo '<div class="comment-pinned-block">';
        comments_render_node($pinnedNode, $type, $objectId, $file, 0, $pinnedContext);
        echo '</div>';
    }

    if (!$tree) {
        echo '<div class="'.($type === 'users' ? 'wall-comment-empty' : 'torrent-comment-empty').'">'.($type === 'users' ? 'На стене пока нет комментариев.' : 'Комментариев пока нет.').'</div>';
    } else {
        foreach ($tree as $node) {
            if ((int) ($node['id'] ?? 0) === $pinnedCommentId) {
                continue;
            }
            comments_render_node($node, $type, $objectId, $file, 0, $context);
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
    $sort = comments_current_sort();

    if ($type === '' || $object_id <= 0) {
        return;
    }

    comments_ensure_thread_support($type);

    $tableName = comments_table_name($type);
    $objectColumn = comments_object_column($type);
    $threaded = comments_supports_threads($type);
    $count = 0;
    $pagertop = '';
    $pagerbottom = '';
    $limit = '';
    $showPager = false;

    if (!$threaded) {
        $countRow = $db->super_query("SELECT COUNT(*) AS cnt FROM `{$tableName}` WHERE `{$objectColumn}` = {$object_id}");
        $count = isset($countRow['cnt']) ? (int) $countRow['cnt'] : 0;
        list($pagertop, $pagerbottom, $limit) = pager('20', $count, $file . 'id=' . $object_id . '&', array('lastpagedefault' => 1));
        $showPager = ($count > 20);
    }

    $fileSafe = htmlspecialchars($file, ENT_QUOTES, 'UTF-8');
    echo '<div class="comment-thread-root" data-comment-thread="1" data-comment-type="'.htmlspecialchars($type, ENT_QUOTES, 'UTF-8').'" data-object-id="'.$object_id.'" data-file="'.$fileSafe.'" data-endpoint="ajax/comments.php" data-comments-sort="'.htmlspecialchars($sort, ENT_QUOTES, 'UTF-8').'">';
    echo '<div class="comment-ajax-notice" data-comment-notice="1" hidden></div>';

    if ($showPager) {
        echo $pagertop;
    }

    echo comments_render_list_html($type, $object_id, $file, $desc, $limit, $sort);

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
    if (!lt_table_exists($tableName)) {
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
        lt_schema_cache_delete(lt_schema_table_cache_key($tableName));
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

function user_wall_reports_open_count()
{
    global $db;

    if (!user_wall_reports_can_moderate()) {
        return 0;
    }

    $cacheKey = lt_cache_key_admin_open_comment_reports_count();
    $cached = lt_cache_get($cacheKey, lt_cache_key_user_ns());
    if ($cached !== false && is_numeric($cached)) {
        return (int) $cached;
    }

    $tableName = user_wall_reports_table_name();
    if (!lt_table_exists($tableName)) {
        return 0;
    }

    $row = $db->super_query("SELECT COUNT(*) AS c FROM `" . $tableName . "` WHERE status = 'open'");
    $count = (int) ($row['c'] ?? 0);
    lt_cache_set($cacheKey, $count, 20, lt_cache_key_user_ns());

    return $count;
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
