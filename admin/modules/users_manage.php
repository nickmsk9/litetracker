<?php
/*
===================================================================
LiteTracker Admin Module: Extended User Management
===================================================================
*/

admin_require('users');

global $db, $PRIV, $USER;

$noticeParam  = trim((string) ($_GET['notice'] ?? ''));
$isSuperadmin = !empty($PRIV['EDIT_PRIV']);
$userId       = (int) ($_GET['user_id'] ?? 0);

$noticeMessages = array(
    'class_changed'     => array('type' => 'success', 'text' => 'Класс пользователя изменен.'),
    'user_banned'       => array('type' => 'success', 'text' => 'Пользователь заблокирован.'),
    'user_unbanned'     => array('type' => 'success', 'text' => 'Блокировка снята.'),
    'temp_banned'       => array('type' => 'success', 'text' => 'Временный бан установлен.'),
    'passkey_reset'     => array('type' => 'success', 'text' => 'Passkey сброшен.'),
    'email_changed'     => array('type' => 'success', 'text' => 'Email изменен.'),
    'avatar_reset'      => array('type' => 'success', 'text' => 'Аватар сброшен.'),
    'note_added'        => array('type' => 'success', 'text' => 'Заметка добавлена.'),
    'action_denied'     => array('type' => 'error',   'text' => 'Нет прав на это действие.'),
    'action_failed'     => array('type' => 'error',   'text' => 'Операция не выполнена.'),
    'csrf_error'        => array('type' => 'error',   'text' => 'Ошибка CSRF-токена.'),
    'validation_error'  => array('type' => 'error',   'text' => 'Ошибка валидации.'),
);
$notice = ($noticeMessages[$noticeParam] ?? null);

// POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!lt_csrf_validate('admin_dashboard')) {
        header('Location: admin.php?tab=users_manage&user_id='.(int)($_POST['user_id']??0).'&notice=csrf_error');
        die();
    }

    $adminAction  = trim((string) ($_POST['admin_action'] ?? ''));
    $targetUserId = (int) ($_POST['user_id'] ?? 0);
    $adminId      = (int) ($USER['id'] ?? 0);

    if ($adminAction === 'change_class' && $targetUserId > 0) {
        admin_require('users');
        $newClass = (int) ($_POST['new_class'] ?? 0);
        if ($newClass > 0) {
            $oldRow = $db->super_query("SELECT class FROM users WHERE id = ".$targetUserId);
            $oldClass = (int) ($oldRow['class'] ?? 0);
            $db->pquery("UPDATE users SET class=? WHERE id=?", 'ii', [$newClass, $targetUserId], false);
            lt_admin_audit_log('change_class', 'users_manage', 'user', $targetUserId,
                (string)$oldClass, (string)$newClass);
            lt_cache_invalidate_namespace('user');
        }
        header('Location: admin.php?tab=users_manage&user_id='.$targetUserId.'&notice=class_changed');
        die();
    }

    if ($adminAction === 'ban_user' && $targetUserId > 0) {
        admin_require('users');
        if ($targetUserId === $adminId) {
            header('Location: admin.php?tab=users_manage&user_id='.$targetUserId.'&notice=action_denied');
            die();
        }
        $db->pquery("UPDATE users SET enabled=0 WHERE id=?", 'i', [$targetUserId], false);
        lt_admin_audit_log('ban_user', 'users_manage', 'user', $targetUserId, '1', '0');
        lt_cache_invalidate_namespace('user');
        header('Location: admin.php?tab=users_manage&user_id='.$targetUserId.'&notice=user_banned');
        die();
    }

    if ($adminAction === 'unban_user' && $targetUserId > 0) {
        admin_require('users');
        $db->pquery("UPDATE users SET enabled=1 WHERE id=?", 'i', [$targetUserId], false);
        lt_admin_audit_log('unban_user', 'users_manage', 'user', $targetUserId, '0', '1');
        lt_cache_invalidate_namespace('user');
        header('Location: admin.php?tab=users_manage&user_id='.$targetUserId.'&notice=user_unbanned');
        die();
    }

    if ($adminAction === 'temp_ban' && $targetUserId > 0) {
        admin_require('users');
        if ($targetUserId === $adminId) {
            header('Location: admin.php?tab=users_manage&user_id='.$targetUserId.'&notice=action_denied');
            die();
        }
        $days = max(1, (int) ($_POST['ban_days'] ?? 1));
        $until = date('Y-m-d H:i:s', time() + $days * 86400);
        $db->pquery("UPDATE users SET enabled=0, banned=? WHERE id=?", 'si', [$until, $targetUserId], false);
        lt_admin_audit_log('temp_ban', 'users_manage', 'user', $targetUserId, null, json_encode(array('days'=>$days,'until'=>$until)));
        lt_cache_invalidate_namespace('user');
        header('Location: admin.php?tab=users_manage&user_id='.$targetUserId.'&notice=temp_banned');
        die();
    }

    if ($adminAction === 'reset_passkey' && $targetUserId > 0) {
        admin_require('users');
        $newPasskey = function_exists('lt_generate_unique_passkey') ? lt_generate_unique_passkey() : mksecret(32);
        $db->pquery("UPDATE users SET passkey=? WHERE id=?", 'si', [$newPasskey, $targetUserId], false);
        lt_admin_audit_log('reset_passkey', 'users_manage', 'user', $targetUserId);
        lt_cache_invalidate_namespace('user');
        header('Location: admin.php?tab=users_manage&user_id='.$targetUserId.'&notice=passkey_reset');
        die();
    }

    if ($adminAction === 'change_email' && $targetUserId > 0) {
        if (!$isSuperadmin) {
            header('Location: admin.php?tab=users_manage&user_id='.$targetUserId.'&notice=action_denied');
            die();
        }
        $newEmail = trim((string) ($_POST['new_email'] ?? ''));
        if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            header('Location: admin.php?tab=users_manage&user_id='.$targetUserId.'&notice=validation_error');
            die();
        }
        $oldRow = $db->super_query("SELECT email FROM users WHERE id = ".$targetUserId);
        $db->pquery("UPDATE users SET email=? WHERE id=?", 'si', [$newEmail, $targetUserId], false);
        lt_admin_audit_log('change_email', 'users_manage', 'user', $targetUserId,
            (string)($oldRow['email']??''), $newEmail);
        lt_cache_invalidate_namespace('user');
        header('Location: admin.php?tab=users_manage&user_id='.$targetUserId.'&notice=email_changed');
        die();
    }

    if ($adminAction === 'reset_avatar' && $targetUserId > 0) {
        admin_require('users');
        $db->pquery("UPDATE users SET avatar='' WHERE id=?", 'i', [$targetUserId], false);
        lt_admin_audit_log('reset_avatar', 'users_manage', 'user', $targetUserId);
        lt_cache_invalidate_namespace('user');
        header('Location: admin.php?tab=users_manage&user_id='.$targetUserId.'&notice=avatar_reset');
        die();
    }

    if ($adminAction === 'add_note' && $targetUserId > 0) {
        admin_require('users');
        $noteText = mb_substr(trim((string) ($_POST['note_text'] ?? '')), 0, 5000);
        if ($noteText !== '') {
            // Graceful: create table if needed
            try {
                $db->pquery(
                    "INSERT INTO user_admin_notes (user_id, admin_id, note, created_at)
                     VALUES (?, ?, ?, NOW())",
                    'iis', [$targetUserId, $adminId, $noteText], false
                );
            } catch (\Throwable $e) { /* table may not exist */ }
            lt_admin_audit_log('add_note', 'users_manage', 'user', $targetUserId, null, mb_substr($noteText, 0, 200));
        }
        header('Location: admin.php?tab=users_manage&user_id='.$targetUserId.'&notice=note_added');
        die();
    }
}

// ==== User detail view ====
if ($userId > 0) {
    $userRow = null;
    try {
        $userRow = $db->super_query(
            "SELECT u.id, u.name, u.email, u.class, u.enabled, u.banned, u.added,
                    u.uploaded, u.downloaded, u.passkey, u.avatar, u.last_access,
                    u.last_ip, u.num_messages
             FROM users AS u
             WHERE u.id = ".$userId
        );
    } catch (\Throwable $e) { /* ignore */ }

    if (!$userRow || empty($userRow['id'])) {
        ?>
        <?php if ($notice) { ?>
        <div class='admin-inline-message admin-inline-message-<?=htmlspecialchars($notice['type'], ENT_QUOTES, 'UTF-8');?>'>
            <?=htmlspecialchars($notice['text'], ENT_QUOTES, 'UTF-8');?>
        </div>
        <?php } ?>
        <section class='admin-card'>
            <div class='admin-inline-message admin-inline-message-error'>Пользователь не найден.</div>
            <p><a href='admin.php?tab=users_manage'>← Назад к списку</a></p>
        </section>
        <?php
        return;
    }

    // Load classes
    $classes = array();
    try { $classes = get_classes_list(); } catch (\Throwable $e) { /* ignore */ }

    // Last 10 torrents
    $lastTorrents = array();
    try {
        $sql = $db->query(
            "SELECT id, name, added FROM torrents WHERE id_user = ".$userId." ORDER BY id DESC LIMIT 10",
            0
        );
        if ($sql) {
            while ($r = $db->get_row($sql)) { $lastTorrents[] = $r; }
            $db->free($sql);
        }
    } catch (\Throwable $e) { /* ignore */ }

    // Last 10 comments (torrents)
    $lastComments = array();
    try {
        $sql = $db->query(
            "SELECT c.id, c.text, c.added, t.name AS torrent_name, t.id AS torrent_id
             FROM comments_torrents AS c
             LEFT JOIN torrents AS t ON t.id = c.torrentid
             WHERE c.userid = ".$userId."
             ORDER BY c.id DESC LIMIT 10",
            0
        );
        if ($sql) {
            while ($r = $db->get_row($sql)) { $lastComments[] = $r; }
            $db->free($sql);
        }
    } catch (\Throwable $e) { /* ignore */ }

    // Admin notes
    $adminNotes = array();
    try {
        $sql = $db->query(
            "SELECT n.id, n.note, n.created_at, u.name AS admin_name
             FROM user_admin_notes AS n
             LEFT JOIN users AS u ON u.id = n.admin_id
             WHERE n.user_id = ".$userId."
             ORDER BY n.id DESC LIMIT 20",
            0
        );
        if ($sql) {
            while ($r = $db->get_row($sql)) { $adminNotes[] = $r; }
            $db->free($sql);
        }
    } catch (\Throwable $e) { /* ignore */ }

    // Audit log for this user
    $auditRows = array();
    try {
        $sql = $db->query(
            "SELECT al.action, al.module, al.ip, al.created_at, au.name AS admin_name
             FROM admin_audit_log AS al
             LEFT JOIN users AS au ON au.id = al.admin_id
             WHERE al.target_type = 'user' AND al.target_id = ".$userId."
             ORDER BY al.id DESC LIMIT 20",
            0
        );
        if ($sql) {
            while ($r = $db->get_row($sql)) { $auditRows[] = $r; }
            $db->free($sql);
        }
    } catch (\Throwable $e) { /* ignore */ }

    $uploaded   = (float) ($userRow['uploaded']   ?? 0);
    $downloaded = (float) ($userRow['downloaded'] ?? 0);
    $ratio      = ($downloaded > 0 ? round($uploaded / $downloaded, 2) : '∞');
    $isBanned   = empty($userRow['enabled']) || (int)$userRow['enabled'] === 0;
    ?>

    <?php if ($notice) { ?>
    <div class='admin-inline-message admin-inline-message-<?=htmlspecialchars($notice['type'], ENT_QUOTES, 'UTF-8');?>'>
        <?=htmlspecialchars($notice['text'], ENT_QUOTES, 'UTF-8');?>
    </div>
    <?php } ?>

    <section class='admin-card'>
        <h2 class='admin-card-title'>
            Пользователь: <?=htmlspecialchars((string)($userRow['name']??''), ENT_QUOTES, 'UTF-8');?>
            <span style='font-size:14px;font-weight:400;color:#888;'>&nbsp;#<?=(int)$userRow['id'];?></span>
        </h2>
        <p class='admin-card-text'><a href='admin.php?tab=users_manage'>← Назад к списку</a></p>

        <div class='lt-admin-table-wrap' style='margin-top:16px;'>
            <table class='lt-admin-table'>
                <tbody>
                    <tr><td><strong>ID</strong></td><td><?=(int)$userRow['id'];?></td></tr>
                    <tr><td><strong>Логин</strong></td><td><?=htmlspecialchars((string)($userRow['name']??''), ENT_QUOTES, 'UTF-8');?></td></tr>
                    <tr>
                        <td><strong>Email</strong></td>
                        <td><?=($isSuperadmin
                            ? htmlspecialchars((string)($userRow['email']??''), ENT_QUOTES, 'UTF-8')
                            : '***скрыт***');?></td>
                    </tr>
                    <tr><td><strong>Класс</strong></td><td><?=(int)($userRow['class']??0);?></td></tr>
                    <tr>
                        <td><strong>Статус</strong></td>
                        <td><?=($isBanned
                            ? '<span style="color:red;">Заблокирован</span>'.(trim((string)($userRow['banned']??''))?' до '.htmlspecialchars(convent_date((string)$userRow['banned']), ENT_QUOTES, 'UTF-8'):'')
                            : '<span style="color:green;">Активен</span>');?></td>
                    </tr>
                    <tr><td><strong>Регистрация</strong></td><td><?=htmlspecialchars(convent_date((string)($userRow['added']??'')), ENT_QUOTES, 'UTF-8');?></td></tr>
                    <tr><td><strong>Последний визит</strong></td><td><?=htmlspecialchars(convent_date((string)($userRow['last_access']??'')), ENT_QUOTES, 'UTF-8');?></td></tr>
                    <tr><td><strong>Последний IP</strong></td><td><?=htmlspecialchars((string)($userRow['last_ip']??''), ENT_QUOTES, 'UTF-8');?></td></tr>
                    <tr><td><strong>Загружено</strong></td><td><?=htmlspecialchars(mksize($uploaded), ENT_QUOTES, 'UTF-8');?></td></tr>
                    <tr><td><strong>Скачано</strong></td><td><?=htmlspecialchars(mksize($downloaded), ENT_QUOTES, 'UTF-8');?></td></tr>
                    <tr><td><strong>Рейтинг</strong></td><td><?=htmlspecialchars((string)$ratio, ENT_QUOTES, 'UTF-8');?></td></tr>
                    <?php if ($isSuperadmin) { ?>
                    <tr>
                        <td><strong>Passkey</strong></td>
                        <td><code><?=htmlspecialchars((string)($userRow['passkey']??''), ENT_QUOTES, 'UTF-8');?></code></td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class='admin-card'>
        <h2 class='admin-card-title'>Действия</h2>
        <div class='admin-action-grid' style='margin-top:16px;'>

            <div class='admin-action-card'>
                <form method='post' action='admin.php'>
                    <input type='hidden' name='tab' value='users_manage'>
                    <input type='hidden' name='admin_action' value='<?=($isBanned ? 'unban_user' : 'ban_user');?>'>
                    <input type='hidden' name='user_id' value='<?=(int)$userRow['id'];?>'>
                    <?=lt_csrf_input('admin_dashboard');?>
                    <div class='admin-action-row'>
                        <div class='admin-action-title'><?=($isBanned ? 'Разблокировать' : 'Заблокировать');?></div>
                        <button class='admin-action-button' type='submit'
                                <?=($isBanned ? '' : 'style="background:var(--color-error,#d32f2f);"');?>>
                            <?=($isBanned ? 'Разбан' : 'Бан');?>
                        </button>
                    </div>
                </form>
            </div>

            <?php if ($isSuperadmin) { ?>
            <div class='admin-action-card'>
                <form method='post' action='admin.php'
                      data-admin-confirm='Сбросить passkey пользователя?'>
                    <input type='hidden' name='tab' value='users_manage'>
                    <input type='hidden' name='admin_action' value='reset_passkey'>
                    <input type='hidden' name='user_id' value='<?=(int)$userRow['id'];?>'>
                    <?=lt_csrf_input('admin_dashboard');?>
                    <div class='admin-action-row'>
                        <div class='admin-action-title'>Сбросить Passkey</div>
                        <button class='admin-action-button' type='submit'>Сбросить</button>
                    </div>
                </form>
            </div>
            <?php } ?>

            <div class='admin-action-card'>
                <form method='post' action='admin.php'
                      data-admin-confirm='Сбросить аватар?'>
                    <input type='hidden' name='tab' value='users_manage'>
                    <input type='hidden' name='admin_action' value='reset_avatar'>
                    <input type='hidden' name='user_id' value='<?=(int)$userRow['id'];?>'>
                    <?=lt_csrf_input('admin_dashboard');?>
                    <div class='admin-action-row'>
                        <div class='admin-action-title'>Сбросить аватар</div>
                        <button class='admin-action-button' type='submit'>Сбросить</button>
                    </div>
                </form>
            </div>

        </div>
    </section>

    <section class='admin-card'>
        <h2 class='admin-card-title'>Временный бан</h2>
        <form class='admin-settings-form' method='post' action='admin.php'>
            <input type='hidden' name='tab' value='users_manage'>
            <input type='hidden' name='admin_action' value='temp_ban'>
            <input type='hidden' name='user_id' value='<?=(int)$userRow['id'];?>'>
            <?=lt_csrf_input('admin_dashboard');?>
            <div class='admin-settings-grid'>
                <div class='admin-settings-field'>
                    <label class='admin-settings-label' for='ban-days'>Дней</label>
                    <input class='admin-settings-input' id='ban-days' type='number' name='ban_days'
                           min='1' max='9999' value='1' style='max-width:100px;'>
                </div>
            </div>
            <div class='admin-settings-footer'>
                <button class='admin-settings-submit' type='submit'
                        style='background:var(--color-error,#d32f2f);'>Временный бан</button>
            </div>
        </form>
    </section>

    <section class='admin-card'>
        <h2 class='admin-card-title'>Изменить класс</h2>
        <form class='admin-settings-form' method='post' action='admin.php'>
            <input type='hidden' name='tab' value='users_manage'>
            <input type='hidden' name='admin_action' value='change_class'>
            <input type='hidden' name='user_id' value='<?=(int)$userRow['id'];?>'>
            <?=lt_csrf_input('admin_dashboard');?>
            <div class='admin-settings-grid'>
                <div class='admin-settings-field'>
                    <label class='admin-settings-label' for='new-class'>Класс</label>
                    <select class='admin-settings-input' id='new-class' name='new_class'>
                        <?php foreach ($classes as $cls) { ?>
                        <option value='<?=(int)($cls['id']??0);?>'
                            <?=((int)($userRow['class']??0) === (int)($cls['id']??0) ? ' selected' : '');?>>
                            <?=htmlspecialchars((string)($cls['NAME']??''), ENT_QUOTES, 'UTF-8');?>
                        </option>
                        <?php } ?>
                    </select>
                </div>
            </div>
            <div class='admin-settings-footer'>
                <button class='admin-settings-submit' type='submit'>Изменить класс</button>
            </div>
        </form>
    </section>

    <?php if ($isSuperadmin) { ?>
    <section class='admin-card'>
        <h2 class='admin-card-title'>Изменить Email</h2>
        <form class='admin-settings-form' method='post' action='admin.php'>
            <input type='hidden' name='tab' value='users_manage'>
            <input type='hidden' name='admin_action' value='change_email'>
            <input type='hidden' name='user_id' value='<?=(int)$userRow['id'];?>'>
            <?=lt_csrf_input('admin_dashboard');?>
            <div class='admin-settings-grid'>
                <div class='admin-settings-field'>
                    <label class='admin-settings-label' for='new-email'>Новый Email</label>
                    <input class='admin-settings-input' id='new-email' type='email' name='new_email'
                           maxlength='255' value='<?=htmlspecialchars((string)($userRow['email']??''), ENT_QUOTES, 'UTF-8');?>'>
                </div>
            </div>
            <div class='admin-settings-footer'>
                <button class='admin-settings-submit' type='submit'>Изменить Email</button>
            </div>
        </form>
    </section>
    <?php } ?>

    <section class='admin-card'>
        <h2 class='admin-card-title'>Добавить заметку</h2>
        <form class='admin-settings-form' method='post' action='admin.php'>
            <input type='hidden' name='tab' value='users_manage'>
            <input type='hidden' name='admin_action' value='add_note'>
            <input type='hidden' name='user_id' value='<?=(int)$userRow['id'];?>'>
            <?=lt_csrf_input('admin_dashboard');?>
            <div class='admin-settings-grid'>
                <div class='admin-settings-field' style='grid-column:1/-1;'>
                    <label class='admin-settings-label' for='note-text'>Заметка</label>
                    <textarea class='admin-settings-input' id='note-text' name='note_text'
                              rows='4' maxlength='5000'></textarea>
                </div>
            </div>
            <div class='admin-settings-footer'>
                <button class='admin-settings-submit' type='submit'>Добавить заметку</button>
            </div>
        </form>

        <?php if ($adminNotes) { ?>
        <div class='lt-admin-table-wrap' style='margin-top:16px;'>
            <table class='lt-admin-table'>
                <thead><tr><th>Администратор</th><th>Заметка</th><th>Дата</th></tr></thead>
                <tbody>
                    <?php foreach ($adminNotes as $note) { ?>
                    <tr>
                        <td><?=htmlspecialchars((string)($note['admin_name']??'—'), ENT_QUOTES, 'UTF-8');?></td>
                        <td style='white-space:pre-wrap;'><?=htmlspecialchars((string)($note['note']??''), ENT_QUOTES, 'UTF-8');?></td>
                        <td><?=htmlspecialchars(convent_date((string)($note['created_at']??'')), ENT_QUOTES, 'UTF-8');?></td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
        <?php } ?>
    </section>

    <?php if ($lastTorrents) { ?>
    <section class='admin-card'>
        <h2 class='admin-card-title'>Последние раздачи</h2>
        <div class='lt-admin-table-wrap' style='margin-top:16px;'>
            <table class='lt-admin-table'>
                <thead><tr><th>ID</th><th>Название</th><th>Добавлено</th></tr></thead>
                <tbody>
                    <?php foreach ($lastTorrents as $tor) { ?>
                    <tr>
                        <td><a href='details.php?id=<?=(int)$tor['id'];?>'>#<?=(int)$tor['id'];?></a></td>
                        <td><?=htmlspecialchars((string)($tor['name']??''), ENT_QUOTES, 'UTF-8');?></td>
                        <td><?=htmlspecialchars(convent_date((string)($tor['added']??'')), ENT_QUOTES, 'UTF-8');?></td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </section>
    <?php } ?>

    <?php if ($lastComments) { ?>
    <section class='admin-card'>
        <h2 class='admin-card-title'>Последние комментарии</h2>
        <div class='lt-admin-table-wrap' style='margin-top:16px;'>
            <table class='lt-admin-table'>
                <thead><tr><th>ID</th><th>Раздача</th><th>Текст</th><th>Дата</th></tr></thead>
                <tbody>
                    <?php foreach ($lastComments as $cmt) { ?>
                    <tr>
                        <td><?=(int)$cmt['id'];?></td>
                        <td>
                            <?php if (!empty($cmt['torrent_id'])) { ?>
                            <a href='details.php?id=<?=(int)$cmt['torrent_id'];?>'>
                                <?=htmlspecialchars(mb_strimwidth((string)($cmt['torrent_name']??''), 0, 40, '…'), ENT_QUOTES, 'UTF-8');?>
                            </a>
                            <?php } ?>
                        </td>
                        <td><?=htmlspecialchars(mb_strimwidth((string)($cmt['text']??''), 0, 100, '…'), ENT_QUOTES, 'UTF-8');?></td>
                        <td><?=htmlspecialchars(convent_date((string)($cmt['added']??'')), ENT_QUOTES, 'UTF-8');?></td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </section>
    <?php } ?>

    <?php if ($auditRows) { ?>
    <section class='admin-card'>
        <h2 class='admin-card-title'>Журнал действий над пользователем</h2>
        <div class='lt-admin-table-wrap' style='margin-top:16px;'>
            <table class='lt-admin-table'>
                <thead><tr><th>Действие</th><th>Модуль</th><th>Администратор</th><th>IP</th><th>Дата</th></tr></thead>
                <tbody>
                    <?php foreach ($auditRows as $arow) { ?>
                    <tr>
                        <td><?=htmlspecialchars((string)($arow['action']??''), ENT_QUOTES, 'UTF-8');?></td>
                        <td><?=htmlspecialchars((string)($arow['module']??''), ENT_QUOTES, 'UTF-8');?></td>
                        <td><?=htmlspecialchars((string)($arow['admin_name']??'—'), ENT_QUOTES, 'UTF-8');?></td>
                        <td><?=htmlspecialchars((string)($arow['ip']??''), ENT_QUOTES, 'UTF-8');?></td>
                        <td><?=htmlspecialchars(convent_date((string)($arow['created_at']??'')), ENT_QUOTES, 'UTF-8');?></td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </section>
    <?php } ?>

    <?php
    return; // Don't show the search list when viewing a user
}
// ==== END user detail view ====

// ==== Search / list view ====
$searchName  = trim((string) ($_GET['search'] ?? ''));
$searchEmail = trim((string) ($_GET['email'] ?? ''));
$searchIp    = trim((string) ($_GET['ip'] ?? ''));
$filterClass = (int) ($_GET['class'] ?? 0);
$filterBanned = trim((string) ($_GET['banned'] ?? ''));
$page        = max(1, (int) ($_GET['p'] ?? 1));
$perPage     = 30;
$offset      = ($page - 1) * $perPage;

$where = array('1=1');

if ($searchName !== '') {
    $where[] = "u.name LIKE '%".$db->safesql($searchName)."%'";
}
if ($isSuperadmin && $searchEmail !== '') {
    $where[] = "u.email LIKE '%".$db->safesql($searchEmail)."%'";
}
if ($searchIp !== '') {
    $where[] = "u.last_ip = '".$db->safesql($searchIp)."'";
}
if ($filterClass > 0) {
    $where[] = "u.class = ".$filterClass;
}
if ($filterBanned === '1') {
    $where[] = "(u.enabled = 0 OR u.banned IS NOT NULL)";
} elseif ($filterBanned === '0') {
    $where[] = "(u.enabled = 1 AND (u.banned IS NULL OR u.banned = ''))";
}

$whereSql = implode(' AND ', $where);

$totalRow = $db->super_query("SELECT COUNT(*) AS c FROM users AS u WHERE {$whereSql}");
$total    = (int) ($totalRow['c'] ?? 0);
$pages    = (int) ceil($total / $perPage);

$users = array();
try {
    $sql = $db->query(
        "SELECT u.id, u.name, u.email, u.class, u.enabled, u.banned, u.added, u.last_ip
         FROM users AS u
         WHERE {$whereSql}
         ORDER BY u.id DESC
         LIMIT {$perPage} OFFSET {$offset}",
        0
    );
    if ($sql) {
        while ($r = $db->get_row($sql)) { $users[] = $r; }
        $db->free($sql);
    }
} catch (\Throwable $e) { /* ignore */ }

$classes = array();
try { $classes = get_classes_list(); } catch (\Throwable $e) { /* ignore */ }

$filterQuery = array_filter(array(
    'search' => $searchName,
    'email'  => $searchEmail,
    'ip'     => $searchIp,
    'class'  => ($filterClass > 0 ? $filterClass : ''),
    'banned' => $filterBanned,
), function($v) { return $v !== '' && $v !== 0; });
?>

<?php if ($notice) { ?>
<div class='admin-inline-message admin-inline-message-<?=htmlspecialchars($notice['type'], ENT_QUOTES, 'UTF-8');?>'>
    <?=htmlspecialchars($notice['text'], ENT_QUOTES, 'UTF-8');?>
</div>
<?php } ?>

<section class='admin-card'>
    <h2 class='admin-card-title'>Управление пользователями</h2>

    <form method='get' action='admin.php' style='display:flex;gap:10px;flex-wrap:wrap;margin-top:16px;'>
        <input type='hidden' name='tab' value='users_manage'>
        <input class='admin-settings-input' type='text' name='search'
               value='<?=htmlspecialchars($searchName, ENT_QUOTES, 'UTF-8');?>'
               placeholder='Логин...' style='max-width:180px;'>
        <?php if ($isSuperadmin) { ?>
        <input class='admin-settings-input' type='text' name='email'
               value='<?=htmlspecialchars($searchEmail, ENT_QUOTES, 'UTF-8');?>'
               placeholder='Email...' style='max-width:180px;'>
        <?php } ?>
        <input class='admin-settings-input' type='text' name='ip'
               value='<?=htmlspecialchars($searchIp, ENT_QUOTES, 'UTF-8');?>'
               placeholder='IP...' style='max-width:140px;'>
        <select class='admin-settings-input' name='class' style='min-width:130px;'>
            <option value='0'>Все классы</option>
            <?php foreach ($classes as $cls) {
                $sel = ($filterClass === (int)($cls['id']??0) ? ' selected' : '');
                echo '<option value="'.(int)($cls['id']??0).'"'.$sel.'>'.htmlspecialchars((string)($cls['NAME']??''), ENT_QUOTES, 'UTF-8').'</option>';
            } ?>
        </select>
        <select class='admin-settings-input' name='banned' style='min-width:130px;'>
            <option value=''>Все</option>
            <option value='0'<?=($filterBanned==='0'?' selected':'');?>>Активные</option>
            <option value='1'<?=($filterBanned==='1'?' selected':'');?>>Заблокированные</option>
        </select>
        <button class='admin-settings-submit' type='submit'>Найти</button>
        <a href='admin.php?tab=users_manage' style='line-height:2;'>Сбросить</a>
    </form>

    <p style='margin-top:12px;color:#888;font-size:13px;'>
        Найдено: <?=number_format($total);?> | Страница <?=$page;?> из <?=max(1,$pages);?>
    </p>

    <?php if (!$users) { ?>
    <div class='admin-empty' style='margin-top:16px;'>Пользователей не найдено.</div>
    <?php } else { ?>
    <div class='lt-admin-table-wrap' style='margin-top:16px;'>
        <table class='lt-admin-table'>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Логин</th>
                    <?php if ($isSuperadmin) { ?><th>Email</th><?php } ?>
                    <th>Класс</th>
                    <th>Статус</th>
                    <th>Регистрация</th>
                    <th>IP</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u) { ?>
                <tr>
                    <td><?=(int)$u['id'];?></td>
                    <td>
                        <a href='profile.php?id=<?=(int)$u['id'];?>' target='_blank'>
                            <?=htmlspecialchars((string)($u['name']??''), ENT_QUOTES, 'UTF-8');?>
                        </a>
                    </td>
                    <?php if ($isSuperadmin) { ?>
                    <td><?=htmlspecialchars((string)($u['email']??''), ENT_QUOTES, 'UTF-8');?></td>
                    <?php } ?>
                    <td><?=(int)($u['class']??0);?></td>
                    <td><?=(empty($u['enabled']) ? '<span style="color:red;">Бан</span>' : '<span style="color:green;">OK</span>');?></td>
                    <td><?=htmlspecialchars(convent_date((string)($u['added']??'')), ENT_QUOTES, 'UTF-8');?></td>
                    <td><?=htmlspecialchars((string)($u['last_ip']??''), ENT_QUOTES, 'UTF-8');?></td>
                    <td>
                        <a href='admin.php?tab=users_manage&amp;user_id=<?=(int)$u['id'];?>'>Управление</a>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <?php if ($pages > 1) {
        $pagerBase = 'admin.php?'.http_build_query(array_merge($filterQuery, array('tab'=>'users_manage'))).'&p=';
        ?>
    <div style='margin-top:16px;display:flex;gap:8px;flex-wrap:wrap;'>
        <?php for ($p = 1; $p <= $pages; $p++) { ?>
        <a href='<?=htmlspecialchars($pagerBase.$p, ENT_QUOTES, 'UTF-8');?>'
           style='<?=($p===$page?'font-weight:bold;':'');?>'>
            <?=$p;?>
        </a>
        <?php } ?>
    </div>
    <?php } ?>

    <?php } ?>
</section>
