<?php
/*
===================================================================
LiteTracker Admin Module: Advertising Management
===================================================================
*/

admin_require('ads');

global $db;

$noticeParam = trim((string) ($_GET['notice'] ?? ''));

// Ensure tables exist
$slotsTableExists = false;
$adsTableExists   = false;
try {
    $r = $db->query("SHOW TABLES LIKE 'ad_slots'", 0);
    if ($r) { $row = $db->get_row($r); $db->free($r); $slotsTableExists = ($row !== false && $row !== null); }
    $r = $db->query("SHOW TABLES LIKE 'ads'", 0);
    if ($r) { $row = $db->get_row($r); $db->free($r); $adsTableExists = ($row !== false && $row !== null); }
} catch (\Throwable $e) { /* ignore */ }

// POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!lt_csrf_validate('admin_dashboard')) {
        header('Location: admin.php?tab=ads&notice=csrf_error');
        die();
    }

    $adminAction = trim((string) ($_POST['admin_action'] ?? ''));
    $adminId     = (int) ($GLOBALS['USER']['id'] ?? 0);

    if ($adminAction === 'save_slot' && $slotsTableExists) {
        $slotId = (int) ($_POST['slot_id'] ?? 0);
        $code   = preg_replace('/[^a-zA-Z0-9_\-]/', '', trim((string) ($_POST['code'] ?? '')));
        $title  = mb_substr(trim((string) ($_POST['title'] ?? '')), 0, 255);
        $desc   = mb_substr(trim((string) ($_POST['description'] ?? '')), 0, 500);

        if ($code === '' || mb_strlen($code) > 60 || $title === '') {
            header('Location: admin.php?tab=ads&notice=ads_validation_error');
            die();
        }

        if ($slotId > 0) {
            $db->pquery(
                "UPDATE ad_slots SET code=?, title=?, description=? WHERE id=?",
                'sssi', [$code, $title, $desc, $slotId], false
            );
            lt_admin_audit_log('slot_edit', 'ads', 'slot', $slotId, null, json_encode(compact('code','title')));
        } else {
            $db->pquery(
                "INSERT INTO ad_slots (code, title, description, is_active) VALUES (?, ?, ?, 1)",
                'sss', [$code, $title, $desc], false
            );
            lt_admin_audit_log('slot_add', 'ads', 'slot', 0, null, json_encode(compact('code','title')));
        }
        header('Location: admin.php?tab=ads&notice=ads_slot_saved');
        die();
    }

    if ($adminAction === 'save_ad' && $adsTableExists && $slotsTableExists) {
        $adId       = (int) ($_POST['ad_id'] ?? 0);
        $slotId     = (int) ($_POST['slot_id'] ?? 0);
        $title      = mb_substr(trim((string) ($_POST['title'] ?? '')), 0, 255);
        $htmlCode   = trim((string) ($_POST['html_code'] ?? ''));
        $imageUrl   = mb_substr(trim((string) ($_POST['image_url'] ?? '')), 0, 1000);
        $targetUrl  = mb_substr(trim((string) ($_POST['target_url'] ?? '')), 0, 1000);
        $startsAt   = trim((string) ($_POST['starts_at'] ?? '')) ?: null;
        $endsAt     = trim((string) ($_POST['ends_at'] ?? '')) ?: null;
        $showGuests = !empty($_POST['show_to_guests']) ? 1 : 0;
        $showUsers  = !empty($_POST['show_to_users'])  ? 1 : 0;

        if ($slotId <= 0 || $title === '') {
            header('Location: admin.php?tab=ads&notice=ads_validation_error');
            die();
        }

        if ($adId > 0) {
            $db->pquery(
                "UPDATE ads SET slot_id=?, title=?, html_code=?, image_url=?, target_url=?,
                 starts_at=?, ends_at=?, show_to_guests=?, show_to_users=? WHERE id=?",
                'isssssssii',
                [$slotId, $title, $htmlCode, $imageUrl, $targetUrl, $startsAt, $endsAt, $showGuests, $showUsers, $adId],
                false
            );
            lt_admin_audit_log('ad_edit', 'ads', 'ad', $adId, null, json_encode(compact('title','slotId')));
        } else {
            $db->pquery(
                "INSERT INTO ads (slot_id, title, html_code, image_url, target_url, is_active,
                 starts_at, ends_at, show_to_guests, show_to_users, created_by)
                 VALUES (?, ?, ?, ?, ?, 1, ?, ?, ?, ?, ?)",
                'isssssssii',
                [$slotId, $title, $htmlCode, $imageUrl, $targetUrl, $startsAt, $endsAt, $showGuests, $showUsers, $adminId],
                false
            );
            lt_admin_audit_log('ad_add', 'ads', 'ad', 0, null, json_encode(compact('title','slotId')));
        }
        header('Location: admin.php?tab=ads&slot_id='.$slotId.'&notice=ads_ad_saved');
        die();
    }

    if ($adminAction === 'toggle_slot' && $slotsTableExists) {
        $slotId = (int) ($_POST['slot_id'] ?? 0);
        if ($slotId > 0) {
            $db->pquery("UPDATE ad_slots SET is_active = 1 - is_active WHERE id=?", 'i', [$slotId], false);
            lt_admin_audit_log('slot_toggle', 'ads', 'slot', $slotId);
        }
        header('Location: admin.php?tab=ads&notice=ads_toggled');
        die();
    }

    if ($adminAction === 'toggle_ad' && $adsTableExists) {
        $adId = (int) ($_POST['ad_id'] ?? 0);
        if ($adId > 0) {
            $db->pquery("UPDATE ads SET is_active = 1 - is_active WHERE id=?", 'i', [$adId], false);
            lt_admin_audit_log('ad_toggle', 'ads', 'ad', $adId);
        }
        $slotId = (int) ($_POST['slot_id'] ?? 0);
        header('Location: admin.php?tab=ads'.($slotId > 0 ? '&slot_id='.$slotId : '').'&notice=ads_toggled');
        die();
    }

    if ($adminAction === 'delete_ad' && $adsTableExists) {
        $adId = (int) ($_POST['ad_id'] ?? 0);
        if ($adId > 0) {
            $db->pquery("DELETE FROM ads WHERE id=?", 'i', [$adId], false);
            lt_admin_audit_log('ad_delete', 'ads', 'ad', $adId);
        }
        $slotId = (int) ($_POST['slot_id'] ?? 0);
        header('Location: admin.php?tab=ads'.($slotId > 0 ? '&slot_id='.$slotId : '').'&notice=ads_deleted');
        die();
    }

    if ($adminAction === 'delete_slot' && $slotsTableExists) {
        $slotId = (int) ($_POST['slot_id'] ?? 0);
        if ($slotId > 0) {
            if ($adsTableExists) {
                $db->pquery("DELETE FROM ads WHERE slot_id=?", 'i', [$slotId], false);
            }
            $db->pquery("DELETE FROM ad_slots WHERE id=?", 'i', [$slotId], false);
            lt_admin_audit_log('slot_delete', 'ads', 'slot', $slotId);
        }
        header('Location: admin.php?tab=ads&notice=ads_deleted');
        die();
    }
}

$noticeMessages = array(
    'ads_slot_saved'       => array('type' => 'success', 'text' => 'Слот сохранен.'),
    'ads_ad_saved'         => array('type' => 'success', 'text' => 'Объявление сохранено.'),
    'ads_toggled'          => array('type' => 'success', 'text' => 'Статус изменен.'),
    'ads_deleted'          => array('type' => 'success', 'text' => 'Удалено.'),
    'ads_validation_error' => array('type' => 'error', 'text' => 'Ошибка валидации. Проверьте поля.'),
    'csrf_error'           => array('type' => 'error', 'text' => 'Ошибка CSRF-токена.'),
);
$notice = ($noticeMessages[$noticeParam] ?? null);

$activeSlotId = (int) ($_GET['slot_id'] ?? 0);

// Load slots
$slots = array();
if ($slotsTableExists) {
    $sql = $db->query(
        "SELECT s.*, COUNT(a.id) AS ad_count
         FROM ad_slots AS s
         LEFT JOIN ".($adsTableExists ? 'ads' : 'ad_slots')." AS a ON ".($adsTableExists ? "a.slot_id = s.id" : "1=0")."
         GROUP BY s.id
         ORDER BY s.id DESC",
        0
    );
    if ($sql) {
        while ($row = $db->get_row($sql)) { $slots[] = $row; }
        $db->free($sql);
    }
}

// Load ads for selected slot
$slotAds = array();
if ($activeSlotId > 0 && $adsTableExists) {
    $sql = $db->query(
        "SELECT a.*, u.name AS created_by_name
         FROM ads AS a
         LEFT JOIN users AS u ON u.id = a.created_by
         WHERE a.slot_id = ".(int)$activeSlotId."
         ORDER BY a.id DESC",
        0
    );
    if ($sql) {
        while ($row = $db->get_row($sql)) { $slotAds[] = $row; }
        $db->free($sql);
    }
}
?>

<?php if ($notice) { ?>
<div class='admin-inline-message admin-inline-message-<?=htmlspecialchars($notice['type'], ENT_QUOTES, 'UTF-8');?>'>
    <?=htmlspecialchars($notice['text'], ENT_QUOTES, 'UTF-8');?>
</div>
<?php } ?>

<?php if (!$slotsTableExists) { ?>
<section class='admin-card'>
    <div class='admin-inline-message admin-inline-message-error'>
        Таблица <code>ad_slots</code> не существует. Создайте таблицы рекламы для использования этого раздела.
    </div>
</section>
<?php } else { ?>

<section class='admin-card'>
    <h2 class='admin-card-title'>Рекламные слоты</h2>
    <p class='admin-card-text'>Слот — место на сайте, куда выводится реклама. У каждого слота уникальный код.</p>

    <?php if (!$slots) { ?>
    <div class='admin-empty' style='margin-top:16px;'>Слотов пока нет.</div>
    <?php } else { ?>
    <div class='lt-admin-table-wrap' style='margin-top:16px;'>
        <table class='lt-admin-table'>
            <thead>
                <tr><th>ID</th><th>Код</th><th>Название</th><th>Объявлений</th><th>Активен</th><th>Действия</th></tr>
            </thead>
            <tbody>
                <?php foreach ($slots as $slot) { ?>
                <tr>
                    <td><?=(int) $slot['id'];?></td>
                    <td><code><?=htmlspecialchars((string) ($slot['code'] ?? ''), ENT_QUOTES, 'UTF-8');?></code></td>
                    <td><?=htmlspecialchars((string) ($slot['title'] ?? ''), ENT_QUOTES, 'UTF-8');?></td>
                    <td><?=(int) ($slot['ad_count'] ?? 0);?></td>
                    <td><?=(!empty($slot['is_active']) ? 'Да' : 'Нет');?></td>
                    <td>
                        <a href='admin.php?tab=ads&amp;slot_id=<?=(int)$slot['id'];?>'>Объявления</a>
                        &nbsp;
                        <form method='post' action='admin.php' style='display:inline;'>
                            <input type='hidden' name='tab' value='ads'>
                            <input type='hidden' name='admin_action' value='toggle_slot'>
                            <input type='hidden' name='slot_id' value='<?=(int)$slot['id'];?>'>
                            <?=lt_csrf_input('admin_dashboard');?>
                            <button type='submit' class='admin-action-button' style='padding:2px 8px;font-size:12px;'>
                                <?=(!empty($slot['is_active']) ? 'Выкл' : 'Вкл');?>
                            </button>
                        </form>
                        &nbsp;
                        <form method='post' action='admin.php' style='display:inline;'
                              data-admin-confirm='Удалить слот и все его объявления?'>
                            <input type='hidden' name='tab' value='ads'>
                            <input type='hidden' name='admin_action' value='delete_slot'>
                            <input type='hidden' name='slot_id' value='<?=(int)$slot['id'];?>'>
                            <?=lt_csrf_input('admin_dashboard');?>
                            <button type='submit' class='admin-action-button' style='padding:2px 8px;font-size:12px;background:var(--color-error,#d32f2f);'>Удалить</button>
                        </form>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
    <?php } ?>
</section>

<section class='admin-card'>
    <h2 class='admin-card-title'>Добавить слот</h2>
    <form class='admin-settings-form' method='post' action='admin.php'>
        <input type='hidden' name='tab' value='ads'>
        <input type='hidden' name='admin_action' value='save_slot'>
        <?=lt_csrf_input('admin_dashboard');?>
        <div class='admin-settings-grid'>
            <div class='admin-settings-field'>
                <label class='admin-settings-label' for='slot-code'>Код слота</label>
                <input class='admin-settings-input' id='slot-code' type='text' name='code'
                       maxlength='60' pattern='[a-zA-Z0-9_\-]+'
                       placeholder='sidebar_top' required>
                <div class='admin-settings-help'>Только латиница, цифры, _ и -. Макс. 60 символов.</div>
            </div>
            <div class='admin-settings-field'>
                <label class='admin-settings-label' for='slot-title'>Название</label>
                <input class='admin-settings-input' id='slot-title' type='text' name='title'
                       maxlength='255' placeholder='Боковая панель, верх' required>
            </div>
            <div class='admin-settings-field'>
                <label class='admin-settings-label' for='slot-desc'>Описание</label>
                <input class='admin-settings-input' id='slot-desc' type='text' name='description'
                       maxlength='500' placeholder='Необязательное описание'>
            </div>
        </div>
        <div class='admin-settings-footer'>
            <button class='admin-settings-submit' type='submit'>Добавить слот</button>
        </div>
    </form>
</section>

<?php if ($activeSlotId > 0 && $adsTableExists) { ?>
<section class='admin-card'>
    <h2 class='admin-card-title'>Объявления слота #<?=$activeSlotId;?></h2>
    <p class='admin-card-text'>
        <strong>Внимание:</strong> HTML-код объявления выводится без экранирования.
        Вставляйте только доверенный код.
    </p>

    <?php if (!$slotAds) { ?>
    <div class='admin-empty' style='margin-top:16px;'>В этом слоте нет объявлений.</div>
    <?php } else { ?>
    <div class='lt-admin-table-wrap' style='margin-top:16px;'>
        <table class='lt-admin-table'>
            <thead>
                <tr><th>ID</th><th>Название</th><th>Активно</th><th>Период</th><th>Гости/Юзеры</th><th>Действия</th></tr>
            </thead>
            <tbody>
                <?php foreach ($slotAds as $ad) { ?>
                <tr>
                    <td><?=(int)$ad['id'];?></td>
                    <td><?=htmlspecialchars((string)($ad['title']??''), ENT_QUOTES, 'UTF-8');?></td>
                    <td><?=(!empty($ad['is_active']) ? 'Да' : 'Нет');?></td>
                    <td>
                        <?php
                        $from = trim((string)($ad['starts_at']??''));
                        $to   = trim((string)($ad['ends_at']??''));
                        echo htmlspecialchars(($from ?: '∞').' – '.($to ?: '∞'), ENT_QUOTES, 'UTF-8');
                        ?>
                    </td>
                    <td>
                        <?=(!empty($ad['show_to_guests']) ? 'Гости ' : '');?>
                        <?=(!empty($ad['show_to_users'])  ? 'Юзеры' : '');?>
                    </td>
                    <td>
                        <form method='post' action='admin.php' style='display:inline;'>
                            <input type='hidden' name='tab' value='ads'>
                            <input type='hidden' name='admin_action' value='toggle_ad'>
                            <input type='hidden' name='ad_id' value='<?=(int)$ad['id'];?>'>
                            <input type='hidden' name='slot_id' value='<?=$activeSlotId;?>'>
                            <?=lt_csrf_input('admin_dashboard');?>
                            <button type='submit' class='admin-action-button' style='padding:2px 8px;font-size:12px;'>
                                <?=(!empty($ad['is_active']) ? 'Выкл' : 'Вкл');?>
                            </button>
                        </form>
                        <form method='post' action='admin.php' style='display:inline;'
                              data-admin-confirm='Удалить объявление?'>
                            <input type='hidden' name='tab' value='ads'>
                            <input type='hidden' name='admin_action' value='delete_ad'>
                            <input type='hidden' name='ad_id' value='<?=(int)$ad['id'];?>'>
                            <input type='hidden' name='slot_id' value='<?=$activeSlotId;?>'>
                            <?=lt_csrf_input('admin_dashboard');?>
                            <button type='submit' class='admin-action-button' style='padding:2px 8px;font-size:12px;background:var(--color-error,#d32f2f);'>Удалить</button>
                        </form>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
    <?php } ?>

    <h3 style='margin-top:24px;margin-bottom:8px;'>Добавить объявление</h3>
    <form class='admin-settings-form' method='post' action='admin.php'>
        <input type='hidden' name='tab' value='ads'>
        <input type='hidden' name='admin_action' value='save_ad'>
        <input type='hidden' name='slot_id' value='<?=$activeSlotId;?>'>
        <?=lt_csrf_input('admin_dashboard');?>
        <div class='admin-settings-grid'>
            <div class='admin-settings-field'>
                <label class='admin-settings-label' for='ad-title'>Название</label>
                <input class='admin-settings-input' id='ad-title' type='text' name='title' maxlength='255' required>
            </div>
            <div class='admin-settings-field'>
                <label class='admin-settings-label' for='ad-image'>URL изображения</label>
                <input class='admin-settings-input' id='ad-image' type='text' name='image_url' maxlength='1000'>
            </div>
            <div class='admin-settings-field'>
                <label class='admin-settings-label' for='ad-target'>Ссылка объявления</label>
                <input class='admin-settings-input' id='ad-target' type='text' name='target_url' maxlength='1000'>
            </div>
            <div class='admin-settings-field'>
                <label class='admin-settings-label' for='ad-starts'>Начало показа</label>
                <input class='admin-settings-input' id='ad-starts' type='date' name='starts_at'>
            </div>
            <div class='admin-settings-field'>
                <label class='admin-settings-label' for='ad-ends'>Конец показа</label>
                <input class='admin-settings-input' id='ad-ends' type='date' name='ends_at'>
            </div>
            <div class='admin-settings-field'>
                <label class='admin-settings-label'>Аудитория</label>
                <label class='admin-settings-checkbox-row'>
                    <input type='checkbox' name='show_to_guests' value='1' checked> <span>Показывать гостям</span>
                </label>
                <label class='admin-settings-checkbox-row'>
                    <input type='checkbox' name='show_to_users' value='1' checked> <span>Показывать пользователям</span>
                </label>
            </div>
            <div class='admin-settings-field' style='grid-column:1/-1;'>
                <label class='admin-settings-label' for='ad-html'>HTML-код</label>
                <textarea class='admin-settings-input' id='ad-html' name='html_code' rows='6'
                          placeholder='<!-- Вставьте HTML-код баннера -->'></textarea>
                <div class='admin-settings-help' style='color:orange;'>
                    ⚠ HTML выводится без экранирования. Используйте только доверенный код.
                </div>
            </div>
        </div>
        <div class='admin-settings-footer'>
            <button class='admin-settings-submit' type='submit'>Добавить объявление</button>
        </div>
    </form>
</section>
<?php } ?>

<?php } // end if slotsTableExists ?>
