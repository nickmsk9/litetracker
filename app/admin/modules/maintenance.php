<?php
/*
===================================================================
LiteTracker Admin Module: Maintenance Mode
===================================================================
*/

admin_require('maintenance');

$noticeParam = trim((string) ($_GET['notice'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!lt_csrf_validate('admin_dashboard')) {
        header('Location: admin.php?tab=maintenance&notice=csrf_error');
        die();
    }

    $adminAction = trim((string) ($_POST['admin_action'] ?? ''));

    if ($adminAction === 'save_maintenance') {
        admin_require('maintenance');

        $mode          = !empty($_POST['maintenance_mode']) ? '1' : '0';
        $message       = trim((string) ($_POST['maintenance_message'] ?? ''));
        $allowAdmins   = !empty($_POST['maintenance_allowed_admins']) ? '1' : '0';
        $startsAt      = trim((string) ($_POST['maintenance_starts_at'] ?? ''));
        $endsAt        = trim((string) ($_POST['maintenance_ends_at'] ?? ''));

        // Basic date validation
        if ($startsAt !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}(T\d{2}:\d{2})?$/', $startsAt)) {
            $startsAt = '';
        }
        if ($endsAt !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}(T\d{2}:\d{2})?$/', $endsAt)) {
            $endsAt = '';
        }

        $changes = array(
            'maintenance_mode'             => $mode,
            'maintenance_message'          => $message,
            'maintenance_allowed_admins'   => $allowAdmins,
            'maintenance_starts_at'        => $startsAt,
            'maintenance_ends_at'          => $endsAt,
        );

        $adminId = (int) ($GLOBALS['USER']['id'] ?? 0);
        foreach ($changes as $key => $value) {
            lt_setting_set($key, $value, $adminId);
        }

        lt_admin_audit_log('maintenance_save', 'maintenance', '', 0, null, json_encode($changes));

        header('Location: admin.php?tab=maintenance&notice=maintenance_saved');
        die();
    }
}

$noticeMessages = array(
    'maintenance_saved' => array('type' => 'success', 'text' => 'Настройки обслуживания сохранены.'),
    'csrf_error'        => array('type' => 'error', 'text' => 'Ошибка CSRF-токена. Попробуйте ещё раз.'),
    'action_failed'     => array('type' => 'error', 'text' => 'Операция не выполнена.'),
);
$notice = ($noticeMessages[$noticeParam] ?? null);

$maintenanceMode        = lt_setting('maintenance_mode', '0');
$maintenanceMessage     = lt_setting('maintenance_message', '');
$maintenanceAdmins      = lt_setting('maintenance_allowed_admins', '1');
$maintenanceStartsAt    = lt_setting('maintenance_starts_at', '');
$maintenanceEndsAt      = lt_setting('maintenance_ends_at', '');

$isActive = function_exists('lt_maintenance_mode_active') ? lt_maintenance_mode_active() : (bool)(int)$maintenanceMode;
?>

<?php if ($notice) { ?>
<div class='admin-inline-message admin-inline-message-<?=htmlspecialchars($notice['type'], ENT_QUOTES, 'UTF-8');?>'>
    <?=htmlspecialchars($notice['text'], ENT_QUOTES, 'UTF-8');?>
</div>
<?php } ?>

<section class='admin-card'>
    <h2 class='admin-card-title'>Режим обслуживания</h2>
    <p class='admin-card-text'>
        Статус: <?=($isActive
            ? '<strong style="color:var(--color-error,#d32f2f)">АКТИВЕН — сайт закрыт для пользователей</strong>'
            : '<strong style="color:green;">Выключен — сайт работает в обычном режиме</strong>');?>.
        Администраторы всегда могут заходить на сайт при включенном режиме обслуживания.
    </p>

    <form class='admin-settings-form' method='post' action='admin.php' style='margin-top:20px;'>
        <input type='hidden' name='tab' value='maintenance'>
        <input type='hidden' name='admin_action' value='save_maintenance'>
        <?=lt_csrf_input('admin_dashboard');?>

        <div class='admin-settings-grid'>

            <div class='admin-settings-field'>
                <label class='admin-settings-label'>Режим обслуживания</label>
                <label class='admin-settings-checkbox-row'>
                    <input type='checkbox' name='maintenance_mode' value='1'<?=(!empty($maintenanceMode) ? ' checked' : '');?>>
                    <span>Включить режим обслуживания</span>
                </label>
            </div>

            <div class='admin-settings-field'>
                <label class='admin-settings-label'>Доступ администраторов</label>
                <label class='admin-settings-checkbox-row'>
                    <input type='checkbox' name='maintenance_allowed_admins' value='1'<?=($maintenanceAdmins !== '0' ? ' checked' : '');?>>
                    <span>Администраторы могут заходить на сайт</span>
                </label>
                <div class='admin-settings-help'>Рекомендуется держать включенным, чтобы не потерять доступ.</div>
            </div>

            <div class='admin-settings-field'>
                <label class='admin-settings-label' for='maint-message'>Сообщение пользователям</label>
                <textarea class='admin-settings-input' id='maint-message' name='maintenance_message'
                          rows='4' maxlength='2000'
                          placeholder='Сайт временно недоступен. Ведутся технические работы.'
                ><?=htmlspecialchars((string) $maintenanceMessage, ENT_QUOTES, 'UTF-8');?></textarea>
                <div class='admin-settings-help'>Показывается на заглушке вместо сайта.</div>
            </div>

            <div class='admin-settings-field'>
                <label class='admin-settings-label' for='maint-starts'>Начало работ</label>
                <input class='admin-settings-input' id='maint-starts' type='datetime-local'
                       name='maintenance_starts_at'
                       value='<?=htmlspecialchars((string) $maintenanceStartsAt, ENT_QUOTES, 'UTF-8');?>'>
                <div class='admin-settings-help'>Необязательно. Только для информации в сообщении.</div>
            </div>

            <div class='admin-settings-field'>
                <label class='admin-settings-label' for='maint-ends'>Конец работ</label>
                <input class='admin-settings-input' id='maint-ends' type='datetime-local'
                       name='maintenance_ends_at'
                       value='<?=htmlspecialchars((string) $maintenanceEndsAt, ENT_QUOTES, 'UTF-8');?>'>
                <div class='admin-settings-help'>Необязательно. Только для информации в сообщении.</div>
            </div>

        </div>

        <div class='admin-settings-footer'>
            <button class='admin-settings-submit' type='submit'>Сохранить</button>
        </div>
    </form>
</section>
