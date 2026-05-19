<?php
/*
===================================================================
LiteTracker Admin Module: Reports & Complaints
===================================================================
*/

admin_require('reports');

global $db;

$noticeParam = trim((string) ($_GET['notice'] ?? ''));

// Check table
$reportsTableExists = false;
try {
    $r = $db->query("SHOW TABLES LIKE 'reports'", 0);
    if ($r) { $row = $db->get_row($r); $db->free($r); $reportsTableExists = ($row !== false && $row !== null); }
} catch (\Throwable $e) { /* ignore */ }

// POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!lt_csrf_validate('admin_dashboard')) {
        header('Location: admin.php?tab=reports&notice=csrf_error');
        die();
    }

    $adminAction = trim((string) ($_POST['admin_action'] ?? ''));

    if ($adminAction === 'update_report' && $reportsTableExists) {
        $reportId = (int) ($_POST['report_id'] ?? 0);
        $newStatus = trim((string) ($_POST['status'] ?? ''));
        $allowedStatuses = array('new', 'in_progress', 'resolved', 'rejected');
        if (!in_array($newStatus, $allowedStatuses, true)) {
            $newStatus = 'new';
        }
        $assignTo = (int) ($_POST['assigned_to'] ?? 0);
        $comment  = mb_substr(trim((string) ($_POST['moderator_comment'] ?? '')), 0, 2000);
        $adminId  = (int) ($GLOBALS['USER']['id'] ?? 0);

        if ($reportId > 0) {
            $db->pquery(
                "UPDATE reports SET status=?, assigned_to=?, moderator_comment=?, updated_at=NOW(), updated_by=? WHERE id=?",
                'sisii',
                [$newStatus, ($assignTo > 0 ? $assignTo : null), $comment, $adminId, $reportId],
                false
            );
            lt_admin_audit_log('report_update', 'reports', 'report', $reportId, null,
                json_encode(compact('newStatus', 'comment')));
        }
        header('Location: admin.php?tab=reports&notice=report_updated');
        die();
    }
}

$noticeMessages = array(
    'report_updated' => array('type' => 'success', 'text' => 'Жалоба обновлена.'),
    'csrf_error'     => array('type' => 'error', 'text' => 'Ошибка CSRF-токена.'),
);
$notice = ($noticeMessages[$noticeParam] ?? null);

// Filters
$filterStatus = trim((string) ($_GET['filter_status'] ?? ''));
$filterType   = trim((string) ($_GET['filter_type'] ?? ''));
$reportId     = (int) ($_GET['report_id'] ?? 0);

$validStatuses = array('new', 'in_progress', 'resolved', 'rejected');
$validTypes    = array('torrent', 'user', 'comment');
if (!in_array($filterStatus, $validStatuses, true)) { $filterStatus = ''; }
if (!in_array($filterType, $validTypes, true)) { $filterType = ''; }

// Single report detail
$reportDetail = null;
if ($reportId > 0 && $reportsTableExists) {
    try {
        $reportDetail = $db->super_query(
            "SELECT r.*, u.name AS reporter_name
             FROM reports AS r
             LEFT JOIN users AS u ON u.id = r.reporter_id
             WHERE r.id = ".(int)$reportId
        );
    } catch (\Throwable $e) { /* ignore */ }
}

// List
$reports = array();
if ($reportsTableExists && !$reportId) {
    try {
        $where = array();
        if ($filterStatus !== '') {
            $where[] = "r.status = '".$db->safesql($filterStatus)."'";
        }
        if ($filterType !== '') {
            $where[] = "r.target_type = '".$db->safesql($filterType)."'";
        }
        $whereSql = ($where ? 'WHERE '.implode(' AND ', $where) : '');

        $sql = $db->query(
            "SELECT r.id, r.target_type, r.target_id, r.reason, r.status,
                    r.created_at, u.name AS reporter_name
             FROM reports AS r
             LEFT JOIN users AS u ON u.id = r.reporter_id
             {$whereSql}
             ORDER BY r.id DESC
             LIMIT 100",
            0
        );
        if ($sql) {
            while ($row = $db->get_row($sql)) { $reports[] = $row; }
            $db->free($sql);
        }
    } catch (\Throwable $e) { /* ignore */ }
}
?>

<?php if ($notice) { ?>
<div class='admin-inline-message admin-inline-message-<?=htmlspecialchars($notice['type'], ENT_QUOTES, 'UTF-8');?>'>
    <?=htmlspecialchars($notice['text'], ENT_QUOTES, 'UTF-8');?>
</div>
<?php } ?>

<?php if (!$reportsTableExists) { ?>
<section class='admin-card'>
    <div class='admin-inline-message admin-inline-message-error'>
        Таблица <code>reports</code> не существует. Создайте таблицу для использования этого раздела.
    </div>
</section>

<?php } elseif ($reportId > 0 && $reportDetail) { ?>

<section class='admin-card'>
    <h2 class='admin-card-title'>Жалоба #<?=(int)$reportDetail['id'];?></h2>
    <p class='admin-card-text'>
        <a href='admin.php?tab=reports'>← Назад к списку</a>
    </p>

    <div class='lt-admin-table-wrap' style='margin-top:16px;'>
        <table class='lt-admin-table'>
            <tbody>
                <tr><td><strong>ID</strong></td><td><?=(int)$reportDetail['id'];?></td></tr>
                <tr><td><strong>Пользователь</strong></td><td><?=htmlspecialchars((string)($reportDetail['reporter_name']??'—'), ENT_QUOTES, 'UTF-8');?></td></tr>
                <tr><td><strong>Тип объекта</strong></td><td><?=htmlspecialchars((string)($reportDetail['target_type']??''), ENT_QUOTES, 'UTF-8');?></td></tr>
                <tr><td><strong>ID объекта</strong></td><td><?=(int)($reportDetail['target_id']??0);?></td></tr>
                <tr><td><strong>Статус</strong></td><td><?=htmlspecialchars((string)($reportDetail['status']??''), ENT_QUOTES, 'UTF-8');?></td></tr>
                <tr><td><strong>Дата</strong></td><td><?=htmlspecialchars(convent_date((string)($reportDetail['created_at']??'')), ENT_QUOTES, 'UTF-8');?></td></tr>
                <tr>
                    <td><strong>Причина</strong></td>
                    <td style='white-space:pre-wrap;'><?=htmlspecialchars((string)($reportDetail['reason']??''), ENT_QUOTES, 'UTF-8');?></td>
                </tr>
                <?php if (!empty($reportDetail['moderator_comment'])) { ?>
                <tr>
                    <td><strong>Комментарий модератора</strong></td>
                    <td style='white-space:pre-wrap;'><?=htmlspecialchars((string)$reportDetail['moderator_comment'], ENT_QUOTES, 'UTF-8');?></td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <?php
    $targetType = trim((string)($reportDetail['target_type']??''));
    $targetId   = (int)($reportDetail['target_id']??0);
    $targetLinks = array(
        'torrent' => ($targetId > 0 ? 'details.php?id='.$targetId : null),
        'user'    => ($targetId > 0 ? 'profile.php?id='.$targetId : null),
        'comment' => null,
    );
    $targetLink = ($targetLinks[$targetType] ?? null);
    ?>
    <?php if ($targetLink !== null) { ?>
    <p><a href='<?=htmlspecialchars($targetLink, ENT_QUOTES, 'UTF-8');?>' target='_blank'>Перейти к объекту ↗</a></p>
    <?php } ?>

    <form class='admin-settings-form' method='post' action='admin.php' style='margin-top:20px;'>
        <input type='hidden' name='tab' value='reports'>
        <input type='hidden' name='admin_action' value='update_report'>
        <input type='hidden' name='report_id' value='<?=(int)$reportDetail['id'];?>'>
        <?=lt_csrf_input('admin_dashboard');?>
        <div class='admin-settings-grid'>
            <div class='admin-settings-field'>
                <label class='admin-settings-label' for='rep-status'>Статус</label>
                <select class='admin-settings-input' id='rep-status' name='status'>
                    <?php
                    $statusLabels = array(
                        'new'         => 'Новая',
                        'in_progress' => 'В работе',
                        'resolved'    => 'Решена',
                        'rejected'    => 'Отклонена',
                    );
                    foreach ($statusLabels as $sKey => $sLabel) {
                        $sel = ((string)($reportDetail['status']??'') === $sKey ? ' selected' : '');
                        echo '<option value="'.htmlspecialchars($sKey, ENT_QUOTES, 'UTF-8').'"'.$sel.'>'.htmlspecialchars($sLabel, ENT_QUOTES, 'UTF-8').'</option>';
                    }
                    ?>
                </select>
            </div>
            <div class='admin-settings-field'>
                <label class='admin-settings-label' for='rep-comment'>Комментарий модератора</label>
                <textarea class='admin-settings-input' id='rep-comment' name='moderator_comment'
                          rows='4' maxlength='2000'
                ><?=htmlspecialchars((string)($reportDetail['moderator_comment']??''), ENT_QUOTES, 'UTF-8');?></textarea>
            </div>
        </div>
        <div class='admin-settings-footer'>
            <button class='admin-settings-submit' type='submit'>Сохранить</button>
        </div>
    </form>
</section>

<?php } else { ?>

<section class='admin-card'>
    <h2 class='admin-card-title'>Жалобы пользователей</h2>
    <p class='admin-card-text'>Фильтрация и обработка жалоб на раздачи, пользователей и комментарии.</p>

    <form method='get' action='admin.php' style='display:flex;gap:12px;flex-wrap:wrap;margin-top:16px;'>
        <input type='hidden' name='tab' value='reports'>
        <select class='admin-settings-input' name='filter_status' style='min-width:140px;'>
            <option value=''>Все статусы</option>
            <?php
            $statusLabels = array('new'=>'Новые','in_progress'=>'В работе','resolved'=>'Решенные','rejected'=>'Отклоненные');
            foreach ($statusLabels as $sKey => $sLabel) {
                $sel = ($filterStatus === $sKey ? ' selected' : '');
                echo '<option value="'.htmlspecialchars($sKey, ENT_QUOTES, 'UTF-8').'"'.$sel.'>'.htmlspecialchars($sLabel, ENT_QUOTES, 'UTF-8').'</option>';
            }
            ?>
        </select>
        <select class='admin-settings-input' name='filter_type' style='min-width:140px;'>
            <option value=''>Все типы</option>
            <?php
            $typeLabels = array('torrent'=>'Раздачи','user'=>'Пользователи','comment'=>'Комментарии');
            foreach ($typeLabels as $tKey => $tLabel) {
                $sel = ($filterType === $tKey ? ' selected' : '');
                echo '<option value="'.htmlspecialchars($tKey, ENT_QUOTES, 'UTF-8').'"'.$sel.'>'.htmlspecialchars($tLabel, ENT_QUOTES, 'UTF-8').'</option>';
            }
            ?>
        </select>
        <button class='admin-settings-submit' type='submit'>Фильтр</button>
        <a href='admin.php?tab=reports' style='line-height:2;'>Сбросить</a>
    </form>

    <?php if (!$reports) { ?>
    <div class='admin-empty' style='margin-top:16px;'>Жалоб не найдено.</div>
    <?php } else { ?>
    <div class='lt-admin-table-wrap' style='margin-top:16px;'>
        <table class='lt-admin-table'>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Пользователь</th>
                    <th>Тип/Объект</th>
                    <th>Причина</th>
                    <th>Статус</th>
                    <th>Дата</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reports as $rep) { ?>
                <tr>
                    <td><?=(int)$rep['id'];?></td>
                    <td><?=htmlspecialchars((string)($rep['reporter_name']??'—'), ENT_QUOTES, 'UTF-8');?></td>
                    <td><?=htmlspecialchars((string)($rep['target_type']??'').((int)($rep['target_id']??0)>0?' #'.(int)$rep['target_id']:''), ENT_QUOTES, 'UTF-8');?></td>
                    <td><?=htmlspecialchars(mb_strimwidth((string)($rep['reason']??''), 0, 80, '…'), ENT_QUOTES, 'UTF-8');?></td>
                    <td><?=htmlspecialchars((string)($rep['status']??''), ENT_QUOTES, 'UTF-8');?></td>
                    <td><?=htmlspecialchars(convent_date((string)($rep['created_at']??'')), ENT_QUOTES, 'UTF-8');?></td>
                    <td>
                        <a href='admin.php?tab=reports&amp;report_id=<?=(int)$rep['id'];?>'>Открыть</a>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
    <?php } ?>
</section>

<?php } // end if reportId ?>
