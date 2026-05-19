<?php
/*
===================================================================
LiteTracker Admin Module: System Diagnostics (read-only)
===================================================================
*/

admin_require('system');

global $db;

$cacheInfo = lt_cache_runtime_info();

// MySQL version
$mysqlVersion = '—';
try {
    $vRow = $db->super_query("SELECT VERSION() AS v");
    $mysqlVersion = (string) ($vRow['v'] ?? '—');
} catch (\Throwable $e) {
    $mysqlVersion = 'Ошибка: '.$e->getMessage();
}

// Directory checks
$dirsToCheck = array(
    'storage/cache/'   => function_exists('lt_cache_path') ? lt_cache_path() : LT_STORAGE_PATH.'/cache',
    'legacy cache/'    => LT_ROOT_PATH.'/cache',
    'storage/logs/'    => function_exists('lt_logs_path') ? lt_logs_path() : LT_STORAGE_PATH.'/logs',
    'legacy logs/'     => LT_ROOT_PATH.'/logs',
    'storage/uploads/' => function_exists('lt_uploads_path') ? lt_uploads_path() : LT_STORAGE_PATH.'/uploads',
    'public/downloads/' => LT_PUBLIC_PATH.'/downloads',
);

$dirStatuses = array();
foreach ($dirsToCheck as $label => $path) {
    $exists   = is_dir($path);
    $writable = $exists && is_writable($path);
    $status   = ($exists ? ($writable ? 'ok' : 'warning') : 'error');
    $dirStatuses[] = array(
        'label'  => $label,
        'exists' => $exists,
        'writable' => $writable,
        'status' => $status,
    );
}

// Relevant extensions
$extensionsToCheck = array('mysqli', 'memcached', 'json', 'mbstring', 'openssl', 'fileinfo', 'gd', 'imagick');
$extensionStatuses = array();
foreach ($extensionsToCheck as $ext) {
    $loaded = extension_loaded($ext);
    $extensionStatuses[$ext] = $loaded;
}

// Recent audit log
$auditRows = array();
$auditTableExists = false;
try {
    $checkSql = $db->query("SHOW TABLES LIKE 'admin_audit_log'", 0);
    if ($checkSql) {
        $checkRow = $db->get_row($checkSql);
        $db->free($checkSql);
        $auditTableExists = ($checkRow !== false && $checkRow !== null);
    }
    if ($auditTableExists) {
        $auditSql = $db->query(
            "SELECT al.id, al.action, al.module, al.target_type, al.target_id,
                    al.ip, al.created_at, u.name AS admin_name
             FROM admin_audit_log AS al
             LEFT JOIN users AS u ON u.id = al.admin_id
             ORDER BY al.id DESC
             LIMIT 20",
            0
        );
        if ($auditSql) {
            while ($row = $db->get_row($auditSql)) {
                $auditRows[] = $row;
            }
            $db->free($auditSql);
        }
    }
} catch (\Throwable $e) {
    // Graceful fallback
}
?>

<section class='admin-card'>
    <h2 class='admin-card-title'>PHP &amp; Окружение</h2>
    <div class='lt-admin-table-wrap' style='margin-top:16px;'>
        <table class='lt-admin-table'>
            <tbody>
                <tr><td><strong>Версия PHP</strong></td><td><?=htmlspecialchars(PHP_VERSION, ENT_QUOTES, 'UTF-8');?></td></tr>
                <tr><td><strong>ОС</strong></td><td><?=htmlspecialchars(PHP_OS, ENT_QUOTES, 'UTF-8');?></td></tr>
                <tr><td><strong>Версия MySQL</strong></td><td><?=htmlspecialchars($mysqlVersion, ENT_QUOTES, 'UTF-8');?></td></tr>
                <tr>
                    <td><strong>Кэш драйвер</strong></td>
                    <td>
                        <?=htmlspecialchars((string) ($cacheInfo['active_driver'] ?? '—'), ENT_QUOTES, 'UTF-8');?>
                        <?php if (!empty($cacheInfo['fallback_reason'])) { ?>
                        <span style='color:orange;'>(fallback: <?=htmlspecialchars((string) $cacheInfo['fallback_reason'], ENT_QUOTES, 'UTF-8');?>)</span>
                        <?php } ?>
                    </td>
                </tr>
                <tr><td><strong>memory_limit</strong></td><td><?=htmlspecialchars(ini_get('memory_limit'), ENT_QUOTES, 'UTF-8');?></td></tr>
                <tr><td><strong>upload_max_filesize</strong></td><td><?=htmlspecialchars(ini_get('upload_max_filesize'), ENT_QUOTES, 'UTF-8');?></td></tr>
                <tr><td><strong>post_max_size</strong></td><td><?=htmlspecialchars(ini_get('post_max_size'), ENT_QUOTES, 'UTF-8');?></td></tr>
                <tr><td><strong>max_execution_time</strong></td><td><?=htmlspecialchars(ini_get('max_execution_time'), ENT_QUOTES, 'UTF-8');?> сек</td></tr>
                <tr><td><strong>Часовой пояс</strong></td><td><?=htmlspecialchars(ini_get('date.timezone') ?: date_default_timezone_get(), ENT_QUOTES, 'UTF-8');?></td></tr>
            </tbody>
        </table>
    </div>
</section>

<section class='admin-card'>
    <h2 class='admin-card-title'>Директории</h2>
    <div class='lt-admin-table-wrap' style='margin-top:16px;'>
        <table class='lt-admin-table'>
            <thead>
                <tr><th>Директория</th><th>Существует</th><th>Запись</th><th>Статус</th></tr>
            </thead>
            <tbody>
                <?php foreach ($dirStatuses as $dir) { ?>
                <tr>
                    <td><?=htmlspecialchars($dir['label'], ENT_QUOTES, 'UTF-8');?></td>
                    <td><?=($dir['exists'] ? '✓' : '✗');?></td>
                    <td><?=($dir['exists'] ? ($dir['writable'] ? '✓' : '✗') : '—');?></td>
                    <td>
                        <?php if ($dir['status'] === 'ok') { ?>
                        <span style='color:green;font-weight:600;'>OK</span>
                        <?php } elseif ($dir['status'] === 'warning') { ?>
                        <span style='color:orange;font-weight:600;'>WARNING</span>
                        <?php } else { ?>
                        <span style='color:red;font-weight:600;'>ERROR</span>
                        <?php } ?>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</section>

<section class='admin-card'>
    <h2 class='admin-card-title'>PHP-расширения</h2>
    <div class='lt-admin-table-wrap' style='margin-top:16px;'>
        <table class='lt-admin-table'>
            <thead>
                <tr><th>Расширение</th><th>Статус</th></tr>
            </thead>
            <tbody>
                <?php foreach ($extensionStatuses as $extName => $loaded) { ?>
                <tr>
                    <td><?=htmlspecialchars($extName, ENT_QUOTES, 'UTF-8');?></td>
                    <td><?=($loaded
                        ? '<span style="color:green;font-weight:600;">Загружено</span>'
                        : '<span style="color:#aaa;">Не загружено</span>');?></td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</section>

<section class='admin-card'>
    <h2 class='admin-card-title'>Последние записи журнала аудита</h2>
    <?php if (!$auditTableExists) { ?>
    <div class='admin-empty' style='margin-top:16px;'>Таблица admin_audit_log не существует.</div>
    <?php } elseif (!$auditRows) { ?>
    <div class='admin-empty' style='margin-top:16px;'>Журнал пуст.</div>
    <?php } else { ?>
    <div class='lt-admin-table-wrap' style='margin-top:16px;'>
        <table class='lt-admin-table'>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Администратор</th>
                    <th>Действие</th>
                    <th>Модуль</th>
                    <th>Объект</th>
                    <th>IP</th>
                    <th>Время</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($auditRows as $row) { ?>
                <tr>
                    <td><?=(int) ($row['id'] ?? 0);?></td>
                    <td><?=htmlspecialchars((string) ($row['admin_name'] ?? '#'.(int)($row['admin_id']??0)), ENT_QUOTES, 'UTF-8');?></td>
                    <td><?=htmlspecialchars((string) ($row['action'] ?? ''), ENT_QUOTES, 'UTF-8');?></td>
                    <td><?=htmlspecialchars((string) ($row['module'] ?? ''), ENT_QUOTES, 'UTF-8');?></td>
                    <td>
                        <?php
                        $targetType = trim((string) ($row['target_type'] ?? ''));
                        $targetId   = (int) ($row['target_id'] ?? 0);
                        if ($targetType !== '' || $targetId > 0) {
                            echo htmlspecialchars($targetType.($targetId > 0 ? ' #'.$targetId : ''), ENT_QUOTES, 'UTF-8');
                        } else {
                            echo '—';
                        }
                        ?>
                    </td>
                    <td><?=htmlspecialchars((string) ($row['ip'] ?? ''), ENT_QUOTES, 'UTF-8');?></td>
                    <td><?=htmlspecialchars(convent_date((string) ($row['created_at'] ?? '')), ENT_QUOTES, 'UTF-8');?></td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
    <?php } ?>
</section>
