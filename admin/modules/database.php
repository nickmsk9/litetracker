<?php
/*
===================================================================
LiteTracker Admin Module: Database Maintenance (safe, superadmin)
===================================================================
*/

admin_require('database');

global $db, $PRIV;

$noticeParam = trim((string) ($_GET['notice'] ?? ''));

$allowedBrowse = array('categories', 'news', 'faq', 'priv');
$sensitiveFields = array('password','passhash','secret','token','remember','auth','session','passkey','email');
$isSuperadmin = !empty($PRIV['EDIT_PRIV']);

// POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!lt_csrf_validate('admin_dashboard')) {
        header('Location: admin.php?tab=database&notice=csrf_error');
        die();
    }

    $adminAction = trim((string) ($_POST['admin_action'] ?? ''));

    if ($adminAction === 'db_maintain') {
        admin_require('database');
        $op        = trim((string) ($_POST['operation'] ?? ''));
        $tableName = trim((string) ($_POST['table_name'] ?? ''));
        $allowedOps = array('CHECK', 'ANALYZE', 'OPTIMIZE', 'REPAIR');

        if (!in_array(strtoupper($op), $allowedOps, true) || $tableName === '') {
            header('Location: admin.php?tab=database&notice=action_failed');
            die();
        }

        $op        = strtoupper($op);
        $tableName = str_replace('`', '``', $tableName);

        try {
            $result = $db->super_query("{$op} TABLE `{$tableName}`");
            $msg    = trim((string) ($result['Msg_text'] ?? 'done'));
            lt_admin_audit_log('db_maintain', 'database', 'table', 0, null, json_encode(array('op' => $op, 'table' => $tableName, 'msg' => $msg)));
            header('Location: admin.php?tab=database&notice=db_op_done&op='.rawurlencode($op).'&tbl='.rawurlencode($tableName).'&msg='.rawurlencode($msg));
        } catch (\Throwable $e) {
            header('Location: admin.php?tab=database&notice=action_failed');
        }
        die();
    }

    if ($adminAction === 'db_sql' && $isSuperadmin) {
        $rawSql = trim((string) ($_POST['raw_sql'] ?? ''));

        $blockedPattern = '/\b(DROP|DELETE|UPDATE|INSERT|ALTER|TRUNCATE|CREATE|REPLACE|CALL|GRANT|REVOKE|LOCK|UNLOCK|LOAD)\b|INTO\s+(OUTFILE|DUMPFILE)/i';
        if ($rawSql === '' || preg_match($blockedPattern, $rawSql)) {
            header('Location: admin.php?tab=database&notice=sql_blocked');
            die();
        }

        if (!preg_match('/\bLIMIT\b/i', $rawSql)) {
            $rawSql .= ' LIMIT 100';
        }

        // Store SQL in session for redisplay
        if (!isset($_SESSION)) { @session_start(); }
        $_SESSION['admin_db_last_sql'] = $rawSql;

        lt_admin_audit_log('db_sql', 'database', 'sql', 0, null, mb_substr($rawSql, 0, 500));
        header('Location: admin.php?tab=database&notice=sql_run&sql=1');
        die();
    }
}

$noticeMessages = array(
    'db_op_done'    => array('type' => 'success', 'text' => 'Операция выполнена.'),
    'sql_run'       => array('type' => 'success', 'text' => 'Запрос выполнен.'),
    'sql_blocked'   => array('type' => 'error',   'text' => 'Запрос заблокирован. Разрешены только SELECT-запросы.'),
    'action_failed' => array('type' => 'error',   'text' => 'Операция не выполнена.'),
    'csrf_error'    => array('type' => 'error',   'text' => 'Ошибка CSRF-токена.'),
);
$notice = ($noticeMessages[$noticeParam] ?? null);
if ($noticeParam === 'db_op_done') {
    $opLabel  = htmlspecialchars(trim((string) ($_GET['op'] ?? '')), ENT_QUOTES, 'UTF-8');
    $tblLabel = htmlspecialchars(trim((string) ($_GET['tbl'] ?? '')), ENT_QUOTES, 'UTF-8');
    $msgLabel = htmlspecialchars(trim((string) ($_GET['msg'] ?? '')), ENT_QUOTES, 'UTF-8');
    if ($opLabel && $tblLabel) {
        $notice['text'] = "{$opLabel} `{$tblLabel}`: {$msgLabel}";
    }
}

$viewTable   = trim((string) ($_GET['view_table'] ?? ''));
$browseTable = trim((string) ($_GET['browse_table'] ?? ''));

// Load tables status
$tablesList = array();
try {
    $sql = $db->query("SHOW TABLE STATUS", 0);
    if ($sql) {
        while ($row = $db->get_row($sql)) { $tablesList[] = $row; }
        $db->free($sql);
    }
} catch (\Throwable $e) { /* ignore */ }

// Load columns for view_table
$tableColumns = array();
if ($viewTable !== '') {
    $safeTable = str_replace('`', '``', $viewTable);
    try {
        $sql = $db->query("SHOW COLUMNS FROM `{$safeTable}`", 0);
        if ($sql) {
            while ($row = $db->get_row($sql)) { $tableColumns[] = $row; }
            $db->free($sql);
        }
    } catch (\Throwable $e) { /* ignore */ }
}

// Browse allowlisted table
$browseRows    = array();
$browseColumns = array();
if ($browseTable !== '' && in_array($browseTable, $allowedBrowse, true)) {
    $safeTable = str_replace('`', '``', $browseTable);
    try {
        $colSql = $db->query("SHOW COLUMNS FROM `{$safeTable}`", 0);
        if ($colSql) {
            while ($row = $db->get_row($colSql)) { $browseColumns[] = $row['Field'] ?? ''; }
            $db->free($colSql);
        }
        $sql = $db->query("SELECT * FROM `{$safeTable}` LIMIT 50", 0);
        if ($sql) {
            while ($row = $db->get_row($sql)) { $browseRows[] = $row; }
            $db->free($sql);
        }
    } catch (\Throwable $e) { /* ignore */ }
}

// SQL results
$sqlResults    = array();
$sqlResultCols = array();
$showSqlResults = (!empty($_GET['sql']) && isset($_SESSION['admin_db_last_sql']));
$lastSql = '';
if ($showSqlResults) {
    if (!isset($_SESSION)) { @session_start(); }
    $lastSql = (string) ($_SESSION['admin_db_last_sql'] ?? '');
    if ($lastSql !== '') {
        try {
            $sql = $db->query($lastSql, 0);
            if ($sql) {
                $firstRow = $db->get_row($sql);
                if ($firstRow) {
                    $sqlResultCols = array_keys($firstRow);
                    $sqlResults[] = $firstRow;
                    while ($row = $db->get_row($sql)) { $sqlResults[] = $row; }
                }
                $db->free($sql);
            }
        } catch (\Throwable $e) {
            $notice = array('type' => 'error', 'text' => 'Ошибка запроса: '.htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
        }
    }
}

function admin_db_is_sensitive($fieldName) {
    global $sensitiveFields;
    $lower = strtolower((string) $fieldName);
    foreach ($sensitiveFields as $s) {
        if (strpos($lower, $s) !== false) { return true; }
    }
    return false;
}
?>

<?php if ($notice) { ?>
<div class='admin-inline-message admin-inline-message-<?=htmlspecialchars($notice['type'], ENT_QUOTES, 'UTF-8');?>'>
    <?=htmlspecialchars($notice['text'], ENT_QUOTES, 'UTF-8');?>
</div>
<?php } ?>

<section class='admin-card'>
    <h2 class='admin-card-title'>Таблицы базы данных</h2>
    <p class='admin-card-text'>Состояние таблиц. Операции выполняются по одной.</p>

    <?php if (!$tablesList) { ?>
    <div class='admin-empty' style='margin-top:16px;'>Не удалось получить список таблиц.</div>
    <?php } else { ?>
    <div class='lt-admin-table-wrap' style='margin-top:16px;'>
        <table class='lt-admin-table'>
            <thead>
                <tr>
                    <th>Таблица</th>
                    <th>Строк</th>
                    <th>Размер</th>
                    <th>Движок</th>
                    <th>Collation</th>
                    <th>Операции</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tablesList as $tbl) {
                    $tblName   = (string) ($tbl['Name'] ?? '');
                    $tblRows   = (int)    ($tbl['Rows'] ?? 0);
                    $tblSize   = (int)    ($tbl['Data_length'] ?? 0) + (int) ($tbl['Index_length'] ?? 0);
                    $tblEngine = (string) ($tbl['Engine'] ?? '—');
                    $tblColl   = (string) ($tbl['Collation'] ?? '—');
                    ?>
                <tr>
                    <td>
                        <a href='admin.php?tab=database&amp;view_table=<?=rawurlencode($tblName);?>'><?=htmlspecialchars($tblName, ENT_QUOTES, 'UTF-8');?></a>
                        <?php if (in_array($tblName, $allowedBrowse, true)) { ?>
                        &nbsp;<a href='admin.php?tab=database&amp;browse_table=<?=rawurlencode($tblName);?>'>[просмотр]</a>
                        <?php } ?>
                    </td>
                    <td><?=number_format($tblRows);?></td>
                    <td><?=htmlspecialchars(mksize((float) $tblSize), ENT_QUOTES, 'UTF-8');?></td>
                    <td><?=htmlspecialchars($tblEngine, ENT_QUOTES, 'UTF-8');?></td>
                    <td><?=htmlspecialchars($tblColl, ENT_QUOTES, 'UTF-8');?></td>
                    <td>
                        <?php foreach (array('CHECK', 'ANALYZE', 'OPTIMIZE') as $op) { ?>
                        <form method='post' action='admin.php' style='display:inline;'>
                            <input type='hidden' name='tab' value='database'>
                            <input type='hidden' name='admin_action' value='db_maintain'>
                            <input type='hidden' name='operation' value='<?=htmlspecialchars($op, ENT_QUOTES, 'UTF-8');?>'>
                            <input type='hidden' name='table_name' value='<?=htmlspecialchars($tblName, ENT_QUOTES, 'UTF-8');?>'>
                            <?=lt_csrf_input('admin_dashboard');?>
                            <button type='submit' class='admin-action-button' style='padding:2px 6px;font-size:11px;'><?=htmlspecialchars($op, ENT_QUOTES, 'UTF-8');?></button>
                        </form>
                        <?php } ?>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
    <?php } ?>
</section>

<?php if ($viewTable !== '' && $tableColumns) { ?>
<section class='admin-card'>
    <h2 class='admin-card-title'>Структура: <?=htmlspecialchars($viewTable, ENT_QUOTES, 'UTF-8');?></h2>
    <div class='lt-admin-table-wrap' style='margin-top:16px;'>
        <table class='lt-admin-table'>
            <thead>
                <tr><th>Поле</th><th>Тип</th><th>NULL</th><th>Ключ</th><th>По умолчанию</th><th>Чувст.</th></tr>
            </thead>
            <tbody>
                <?php foreach ($tableColumns as $col) {
                    $colName = (string) ($col['Field'] ?? '');
                    $isSens  = admin_db_is_sensitive($colName) && !$isSuperadmin;
                    ?>
                <tr>
                    <td><?=htmlspecialchars($colName, ENT_QUOTES, 'UTF-8');?></td>
                    <td><?=htmlspecialchars((string)($col['Type']??''), ENT_QUOTES, 'UTF-8');?></td>
                    <td><?=htmlspecialchars((string)($col['Null']??''), ENT_QUOTES, 'UTF-8');?></td>
                    <td><?=htmlspecialchars((string)($col['Key']??''), ENT_QUOTES, 'UTF-8');?></td>
                    <td><?=htmlspecialchars((string)($col['Default']??''), ENT_QUOTES, 'UTF-8');?></td>
                    <td><?=($isSens ? '⚠' : '');?></td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</section>
<?php } ?>

<?php if ($browseTable !== '' && in_array($browseTable, $allowedBrowse, true) && $browseColumns) { ?>
<section class='admin-card'>
    <h2 class='admin-card-title'>Просмотр: <?=htmlspecialchars($browseTable, ENT_QUOTES, 'UTF-8');?> (первые 50 строк)</h2>
    <?php if (!$browseRows) { ?>
    <div class='admin-empty' style='margin-top:16px;'>Таблица пуста.</div>
    <?php } else { ?>
    <div class='lt-admin-table-wrap' style='margin-top:16px;overflow-x:auto;'>
        <table class='lt-admin-table'>
            <thead>
                <tr>
                    <?php foreach ($browseColumns as $colName) { ?>
                    <th><?=htmlspecialchars($colName, ENT_QUOTES, 'UTF-8');?></th>
                    <?php } ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($browseRows as $row) { ?>
                <tr>
                    <?php foreach ($browseColumns as $colName) {
                        $val = (string) ($row[$colName] ?? '');
                        $sens = admin_db_is_sensitive($colName);
                        if ($sens && !$isSuperadmin) { $val = '***'; }
                        ?>
                    <td><?=htmlspecialchars(mb_strimwidth($val, 0, 120, '…'), ENT_QUOTES, 'UTF-8');?></td>
                    <?php } ?>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
    <?php } ?>
</section>
<?php } ?>

<?php if ($isSuperadmin) { ?>
<section class='admin-card'>
    <h2 class='admin-card-title'>SQL-консоль (только SELECT)</h2>
    <p class='admin-card-text'>Только SELECT-запросы. Автоматически добавляется LIMIT 100. Запросы логируются.</p>
    <form class='admin-settings-form' method='post' action='admin.php' style='margin-top:16px;'>
        <input type='hidden' name='tab' value='database'>
        <input type='hidden' name='admin_action' value='db_sql'>
        <?=lt_csrf_input('admin_dashboard');?>
        <div class='admin-settings-grid'>
            <div class='admin-settings-field' style='grid-column:1/-1;'>
                <label class='admin-settings-label' for='sql-input'>SQL-запрос</label>
                <textarea class='admin-settings-input' id='sql-input' name='raw_sql' rows='5'
                          placeholder='SELECT id, name FROM users LIMIT 10'
                          style='font-family:monospace;'
                ><?=htmlspecialchars($lastSql, ENT_QUOTES, 'UTF-8');?></textarea>
            </div>
        </div>
        <div class='admin-settings-footer'>
            <button class='admin-settings-submit' type='submit'>Выполнить</button>
        </div>
    </form>

    <?php if ($showSqlResults && $sqlResultCols) { ?>
    <div class='lt-admin-table-wrap' style='margin-top:16px;overflow-x:auto;'>
        <table class='lt-admin-table'>
            <thead>
                <tr>
                    <?php foreach ($sqlResultCols as $col) { ?>
                    <th><?=htmlspecialchars($col, ENT_QUOTES, 'UTF-8');?></th>
                    <?php } ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sqlResults as $row) { ?>
                <tr>
                    <?php foreach ($sqlResultCols as $col) {
                        $val = (string) ($row[$col] ?? '');
                        $sens = admin_db_is_sensitive($col);
                        if ($sens && !$isSuperadmin) { $val = '***'; }
                        ?>
                    <td><?=htmlspecialchars(mb_strimwidth($val, 0, 200, '…'), ENT_QUOTES, 'UTF-8');?></td>
                    <?php } ?>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
    <p style='margin-top:8px;color:#888;font-size:13px;'>Строк: <?=count($sqlResults);?></p>
    <?php } elseif ($showSqlResults) { ?>
    <div class='admin-empty' style='margin-top:16px;'>Запрос вернул 0 строк.</div>
    <?php } ?>
</section>
<?php } ?>
