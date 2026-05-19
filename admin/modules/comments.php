<?php
/*
===================================================================
LiteTracker Admin Module: Comment Management
===================================================================
*/

admin_require('comments');

global $db, $PRIV;

$noticeParam = trim((string) ($_GET['notice'] ?? ''));
$isSuperadmin = !empty($PRIV['EDIT_PRIV']);
$canDelete    = $isSuperadmin || !empty($PRIV['comments_delete']);

$commentTypeMap = array(
    'torrents' => 'comments_torrents',
    'news'     => 'comments_news',
    'users'    => 'comments_users',
);

// POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!lt_csrf_validate('admin_dashboard')) {
        header('Location: admin.php?tab=comments&notice=csrf_error');
        die();
    }

    $adminAction = trim((string) ($_POST['admin_action'] ?? ''));

    if ($adminAction === 'delete_comment') {
        if (!$canDelete) {
            header('Location: admin.php?tab=comments&notice=action_denied');
            die();
        }

        $commentId   = (int) ($_POST['comment_id'] ?? 0);
        $commentType = trim((string) ($_POST['comment_type'] ?? ''));

        if ($commentId > 0 && isset($commentTypeMap[$commentType])) {
            $table = $commentTypeMap[$commentType];
            $db->pquery("DELETE FROM `{$table}` WHERE id=?", 'i', [$commentId], false);
            lt_admin_audit_log('comment_delete', 'comments', $commentType, $commentId);
        }
        header('Location: admin.php?tab=comments&active_tab='.rawurlencode($commentType).'&notice=comment_deleted');
        die();
    }

    if ($adminAction === 'mass_delete') {
        if (!$isSuperadmin) {
            header('Location: admin.php?tab=comments&notice=action_denied');
            die();
        }

        $commentType = trim((string) ($_POST['comment_type'] ?? ''));
        $commentIds  = array_map('intval', (array) ($_POST['comment_ids'] ?? array()));
        $commentIds  = array_filter($commentIds, function($id) { return $id > 0; });

        if ($commentIds && isset($commentTypeMap[$commentType])) {
            $table      = $commentTypeMap[$commentType];
            $idList     = implode(',', $commentIds);
            $db->query("DELETE FROM `{$table}` WHERE id IN ({$idList})", 0);
            lt_admin_audit_log('comment_mass_delete', 'comments', $commentType, 0, null,
                json_encode(array('ids' => array_values($commentIds))));
        }
        header('Location: admin.php?tab=comments&active_tab='.rawurlencode($commentType).'&notice=comments_deleted');
        die();
    }
}

$noticeMessages = array(
    'comment_deleted'  => array('type' => 'success', 'text' => 'Комментарий удален.'),
    'comments_deleted' => array('type' => 'success', 'text' => 'Комментарии удалены.'),
    'action_denied'    => array('type' => 'error',   'text' => 'Нет прав на удаление.'),
    'csrf_error'       => array('type' => 'error',   'text' => 'Ошибка CSRF-токена.'),
);
$notice = ($noticeMessages[$noticeParam] ?? null);

$activeTab = trim((string) ($_GET['active_tab'] ?? 'torrents'));
if (!array_key_exists($activeTab, $commentTypeMap)) { $activeTab = 'torrents'; }

// Filters
$searchText = trim((string) ($_GET['search'] ?? ''));
$filterUser = (int) ($_GET['user_id'] ?? 0);
$dateFrom   = trim((string) ($_GET['date_from'] ?? ''));
$dateTo     = trim((string) ($_GET['date_to'] ?? ''));

function admin_comments_load($type, $tableMap, $searchText, $filterUser, $dateFrom, $dateTo)
{
    global $db;

    $table = $tableMap[$type] ?? '';
    if ($table === '') { return array(); }

    // Check table exists
    $r = $db->query("SHOW TABLES LIKE '{$table}'", 0);
    if (!$r) { return array(); }
    $row = $db->get_row($r);
    $db->free($r);
    if (!$row) { return array(); }

    $where = array('1=1');

    if ($searchText !== '') {
        $where[] = "c.text LIKE '%".$db->safesql($searchText)."%'";
    }
    if ($filterUser > 0) {
        $where[] = "c.userid = ".(int)$filterUser;
    }
    if ($dateFrom !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
        $where[] = "c.added >= '".$db->safesql($dateFrom)." 00:00:00'";
    }
    if ($dateTo !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
        $where[] = "c.added <= '".$db->safesql($dateTo)." 23:59:59'";
    }

    $whereSql = implode(' AND ', $where);

    // Detect target column
    if ($type === 'torrents') {
        $targetCol = 'c.torrentid AS target_id';
    } elseif ($type === 'news') {
        $targetCol = 'c.newsid AS target_id';
    } else {
        $targetCol = 'COALESCE(c.ownerid, c.userid) AS target_id';
    }

    $rows = array();
    try {
        $sql = $db->query(
            "SELECT c.id, c.userid, c.text, c.added, {$targetCol}, u.name AS author_name
             FROM `{$table}` AS c
             LEFT JOIN users AS u ON u.id = c.userid
             WHERE {$whereSql}
             ORDER BY c.id DESC
             LIMIT 100",
            0
        );
        if ($sql) {
            while ($r = $db->get_row($sql)) { $rows[] = $r; }
            $db->free($sql);
        }
    } catch (\Throwable $e) {
        // table structure may differ
    }
    return $rows;
}

$comments = admin_comments_load($activeTab, $commentTypeMap, $searchText, $filterUser, $dateFrom, $dateTo);
?>

<?php if ($notice) { ?>
<div class='admin-inline-message admin-inline-message-<?=htmlspecialchars($notice['type'], ENT_QUOTES, 'UTF-8');?>'>
    <?=htmlspecialchars($notice['text'], ENT_QUOTES, 'UTF-8');?>
</div>
<?php } ?>

<section class='admin-card'>
    <h2 class='admin-card-title'>Управление комментариями</h2>

    <div class='admin-tabs' style='position:static;margin-top:16px;'>
        <?php
        $tabLabels = array('torrents' => 'Раздачи', 'news' => 'Новости', 'users' => 'Профили');
        foreach ($tabLabels as $tKey => $tLabel) {
            $isActive = ($activeTab === $tKey);
            $filterQuery = http_build_query(array_filter(array(
                'tab'        => 'comments',
                'active_tab' => $tKey,
                'search'     => $searchText,
                'user_id'    => ($filterUser > 0 ? $filterUser : ''),
                'date_from'  => $dateFrom,
                'date_to'    => $dateTo,
            ), function($v) { return $v !== '' && $v !== 0; }));
            ?>
        <a class='admin-tab-link<?=($isActive ? ' admin-tab-link-active' : '');?>'
           href='admin.php?<?=htmlspecialchars($filterQuery, ENT_QUOTES, 'UTF-8');?>'>
            <?=htmlspecialchars($tLabel, ENT_QUOTES, 'UTF-8');?>
        </a>
        <?php } ?>
    </div>

    <form method='get' action='admin.php' style='display:flex;gap:10px;flex-wrap:wrap;margin-top:16px;'>
        <input type='hidden' name='tab' value='comments'>
        <input type='hidden' name='active_tab' value='<?=htmlspecialchars($activeTab, ENT_QUOTES, 'UTF-8');?>'>
        <input class='admin-settings-input' type='text' name='search' value='<?=htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8');?>' placeholder='Текст...' style='max-width:200px;'>
        <input class='admin-settings-input' type='number' min='1' name='user_id' value='<?=($filterUser > 0 ? $filterUser : '');?>' placeholder='ID пользователя' style='max-width:150px;'>
        <input class='admin-settings-input' type='date' name='date_from' value='<?=htmlspecialchars($dateFrom, ENT_QUOTES, 'UTF-8');?>'>
        <input class='admin-settings-input' type='date' name='date_to' value='<?=htmlspecialchars($dateTo, ENT_QUOTES, 'UTF-8');?>'>
        <button class='admin-settings-submit' type='submit'>Фильтр</button>
        <a href='admin.php?tab=comments&amp;active_tab=<?=htmlspecialchars($activeTab, ENT_QUOTES, 'UTF-8');?>' style='line-height:2;'>Сбросить</a>
    </form>

    <?php if (!$comments) { ?>
    <div class='admin-empty' style='margin-top:16px;'>Комментариев не найдено.</div>
    <?php } else { ?>

    <?php if ($isSuperadmin) { ?>
    <form method='post' action='admin.php' id='mass-delete-form'
          data-admin-confirm='Удалить выбранные комментарии?'>
        <input type='hidden' name='tab' value='comments'>
        <input type='hidden' name='admin_action' value='mass_delete'>
        <input type='hidden' name='comment_type' value='<?=htmlspecialchars($activeTab, ENT_QUOTES, 'UTF-8');?>'>
        <?=lt_csrf_input('admin_dashboard');?>
    <?php } ?>

    <div class='lt-admin-table-wrap' style='margin-top:16px;'>
        <table class='lt-admin-table'>
            <thead>
                <tr>
                    <?php if ($isSuperadmin) { ?><th><input type='checkbox' id='check-all' onclick='document.querySelectorAll(".cmt-cb").forEach(function(el){el.checked=this.checked;},this);'></th><?php } ?>
                    <th>ID</th>
                    <th>Автор</th>
                    <th>Объект</th>
                    <th>Текст</th>
                    <th>Дата</th>
                    <?php if ($canDelete) { ?><th>Действия</th><?php } ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($comments as $cmt) { ?>
                <tr>
                    <?php if ($isSuperadmin) { ?>
                    <td><input type='checkbox' class='cmt-cb' name='comment_ids[]' form='mass-delete-form' value='<?=(int)$cmt['id'];?>'></td>
                    <?php } ?>
                    <td><?=(int)$cmt['id'];?></td>
                    <td><?=htmlspecialchars((string)($cmt['author_name']??'#'.(int)($cmt['userid']??0)), ENT_QUOTES, 'UTF-8');?></td>
                    <td><?=(int)($cmt['target_id']??0);?></td>
                    <td><?=htmlspecialchars(mb_strimwidth((string)($cmt['text']??''), 0, 150, '…'), ENT_QUOTES, 'UTF-8');?></td>
                    <td><?=htmlspecialchars(convent_date((string)($cmt['added']??'')), ENT_QUOTES, 'UTF-8');?></td>
                    <?php if ($canDelete) { ?>
                    <td>
                        <form method='post' action='admin.php' style='display:inline;'
                              data-admin-confirm='Удалить комментарий?'>
                            <input type='hidden' name='tab' value='comments'>
                            <input type='hidden' name='admin_action' value='delete_comment'>
                            <input type='hidden' name='comment_id' value='<?=(int)$cmt['id'];?>'>
                            <input type='hidden' name='comment_type' value='<?=htmlspecialchars($activeTab, ENT_QUOTES, 'UTF-8');?>'>
                            <?=lt_csrf_input('admin_dashboard');?>
                            <button type='submit' class='admin-action-button'
                                    style='padding:2px 8px;font-size:12px;background:var(--color-error,#d32f2f);'>
                                Удалить
                            </button>
                        </form>
                    </td>
                    <?php } ?>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <?php if ($isSuperadmin) { ?>
    <div style='margin-top:10px;'>
        <button type='submit' form='mass-delete-form' class='admin-action-button'
                style='background:var(--color-error,#d32f2f);'>
            Удалить выбранные
        </button>
    </div>
    </form>
    <?php } ?>

    <?php } ?>
</section>
