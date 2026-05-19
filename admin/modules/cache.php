<?php
/*
===================================================================
LiteTracker Admin Module: Cache Management
===================================================================
*/

admin_require('cache');

$cacheNotice = trim((string) ($_GET['notice'] ?? ''));

// POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!lt_csrf_validate('admin_dashboard')) {
        header('Location: admin.php?tab=cache&notice=csrf_error');
        die();
    }

    $adminAction = trim((string) ($_POST['admin_action'] ?? ''));

    if ($adminAction === 'cache_flush_all') {
        admin_require('cache');
        $flushed = admin_dashboard_flush_cache();
        lt_admin_audit_log('cache_flush_all', 'cache', '', 0, null, $flushed ? 'flushed' : 'fallback');
        header('Location: admin.php?tab=cache&notice=cache_flushed');
        die();
    }

    if ($adminAction === 'cache_invalidate_ns') {
        admin_require('cache');
        $ns = trim((string) ($_POST['ns'] ?? ''));
        $allowedNs = array('settings', 'categories', 'torrent', 'user', 'site_settings');
        if ($ns !== '' && in_array($ns, $allowedNs, true)) {
            lt_cache_invalidate_namespace($ns);
            lt_admin_audit_log('cache_invalidate_ns', 'cache', 'namespace', 0, null, $ns);
            header('Location: admin.php?tab=cache&notice=cache_ns_invalidated');
        } else {
            header('Location: admin.php?tab=cache&notice=action_failed');
        }
        die();
    }

    if ($adminAction === 'cache_delete_key') {
        admin_require('cache');
        $rawKey = trim((string) ($_POST['cache_key'] ?? ''));
        if ($rawKey !== '') {
            lt_cache_raw_delete($rawKey);
            lt_admin_audit_log('cache_delete_key', 'cache', 'key', 0, null, $rawKey);
            header('Location: admin.php?tab=cache&notice=cache_key_deleted');
        } else {
            header('Location: admin.php?tab=cache&notice=action_failed');
        }
        die();
    }
}

$noticeMessages = array(
    'cache_flushed'       => array('type' => 'success', 'text' => 'Кэш полностью сброшен.'),
    'cache_ns_invalidated' => array('type' => 'success', 'text' => 'Namespace инвалидирован.'),
    'cache_key_deleted'   => array('type' => 'success', 'text' => 'Ключ удален из кэша.'),
    'csrf_error'          => array('type' => 'error', 'text' => 'Ошибка CSRF-токена. Попробуйте ещё раз.'),
    'action_failed'       => array('type' => 'error', 'text' => 'Операция не выполнена.'),
);
$notice = ($noticeMessages[$cacheNotice] ?? null);

$cacheInfo = lt_cache_runtime_info();
$driver     = (string) ($cacheInfo['active_driver'] ?? 'unknown');
$isMemcached = ($driver === 'memcached');
$isOnline    = !empty($cacheInfo['memcached_online']);
$host        = (string) ($cacheInfo['memcached_host'] ?? '');
$port        = (int)   ($cacheInfo['memcached_port'] ?? 0);

$memStats = array();
if ($isMemcached && $isOnline) {
    global $memcached;
    if (is_object($memcached)
        && isset($memcached->client)
        && is_object($memcached->client)
        && method_exists($memcached->client, 'getStats')
    ) {
        $rawStats = $memcached->client->getStats();
        if (is_array($rawStats)) {
            foreach ($rawStats as $serverStats) {
                if (is_array($serverStats)) {
                    $memStats = $serverStats;
                    break;
                }
            }
        }
    }
}
?>

<?php if ($notice) { ?>
<div class='admin-inline-message admin-inline-message-<?=htmlspecialchars($notice['type'], ENT_QUOTES, 'UTF-8');?>'>
    <?=htmlspecialchars($notice['text'], ENT_QUOTES, 'UTF-8');?>
</div>
<?php } ?>

<section class='admin-card'>
    <h2 class='admin-card-title'>Статус кэша</h2>
    <p class='admin-card-text'>Текущее состояние кэш-драйвера и подключения.</p>
    <div class='lt-admin-table-wrap' style='margin-top:16px;'>
        <table class='lt-admin-table'>
            <tbody>
                <tr>
                    <td><strong>Настроенный драйвер</strong></td>
                    <td><?=htmlspecialchars((string) ($cacheInfo['configured_driver'] ?? '—'), ENT_QUOTES, 'UTF-8');?></td>
                </tr>
                <tr>
                    <td><strong>Активный драйвер</strong></td>
                    <td><?=htmlspecialchars($driver, ENT_QUOTES, 'UTF-8');?></td>
                </tr>
                <?php if (!empty($cacheInfo['fallback_reason'])) { ?>
                <tr>
                    <td><strong>Причина fallback</strong></td>
                    <td><?=htmlspecialchars((string) $cacheInfo['fallback_reason'], ENT_QUOTES, 'UTF-8');?></td>
                </tr>
                <?php } ?>
                <?php if ($isMemcached) { ?>
                <tr>
                    <td><strong>Хост</strong></td>
                    <td><?=htmlspecialchars($host.($port ? ':'.$port : ''), ENT_QUOTES, 'UTF-8');?></td>
                </tr>
                <tr>
                    <td><strong>Статус Memcached</strong></td>
                    <td><?=($isOnline ? '<span style="color:green;">Online</span>' : '<span style="color:red;">Offline</span>');?></td>
                </tr>
                <?php } ?>
                <tr>
                    <td><strong>Namespace</strong></td>
                    <td><?=htmlspecialchars((string) ($cacheInfo['namespace'] ?? '—'), ENT_QUOTES, 'UTF-8');?></td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<?php if ($isMemcached && $isOnline && $memStats) { ?>
<section class='admin-card'>
    <h2 class='admin-card-title'>Статистика Memcached</h2>
    <div class='lt-admin-table-wrap' style='margin-top:16px;'>
        <table class='lt-admin-table'>
            <tbody>
                <?php
                $showKeys = array(
                    'uptime'       => 'Аптайм (сек)',
                    'curr_items'   => 'Элементов в кэше',
                    'total_items'  => 'Всего добавлено',
                    'bytes'        => 'Памяти занято',
                    'limit_maxbytes' => 'Лимит памяти',
                    'get_hits'     => 'Попаданий (hits)',
                    'get_misses'   => 'Промахов (misses)',
                    'cmd_get'      => 'Запросов GET',
                    'cmd_set'      => 'Запросов SET',
                    'evictions'    => 'Вытеснений',
                    'version'      => 'Версия сервера',
                );
                foreach ($showKeys as $statKey => $statLabel) {
                    if (!array_key_exists($statKey, $memStats)) {
                        continue;
                    }
                    $val = $memStats[$statKey];
                    if (in_array($statKey, array('bytes', 'limit_maxbytes'), true)) {
                        $val = mksize((float) $val);
                    }
                    ?>
                <tr>
                    <td><?=htmlspecialchars($statLabel, ENT_QUOTES, 'UTF-8');?></td>
                    <td><?=htmlspecialchars((string) $val, ENT_QUOTES, 'UTF-8');?></td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</section>
<?php } elseif ($isMemcached && !$isOnline) { ?>
<section class='admin-card'>
    <div class='admin-inline-message admin-inline-message-error'>
        Memcached недоступен. Кэш работает через fallback-драйвер. Проверьте подключение к серверу Memcached.
    </div>
</section>
<?php } ?>

<section class='admin-card'>
    <h2 class='admin-card-title'>Управление кэшем</h2>
    <p class='admin-card-text'>Сброс и инвалидация. Осторожно: полный flush удалит весь кэш сайта.</p>

    <div class='admin-action-grid' style='margin-top:18px;'>

        <div class='admin-action-card'>
            <form method='post' action='admin.php'
                  data-admin-confirm='Очистить весь кэш? Это временно увеличит нагрузку на БД.'>
                <input type='hidden' name='tab' value='cache'>
                <input type='hidden' name='admin_action' value='cache_flush_all'>
                <?=lt_csrf_input('admin_dashboard');?>
                <div class='admin-action-row'>
                    <div class='admin-action-title'>Полный сброс кэша</div>
                    <button class='admin-action-button' type='submit'>Выполнить</button>
                </div>
                <div class='admin-action-text'>Сбрасывает весь Memcached/файловый кэш.</div>
            </form>
        </div>

        <?php
        $namespaces = array(
            'settings'   => 'Настройки (settings)',
            'site_settings' => 'Параметры сайта (site_settings)',
            'categories' => 'Категории (categories)',
            'torrent'    => 'Раздачи (torrent)',
            'user'       => 'Пользователи (user)',
        );
        foreach ($namespaces as $nsKey => $nsLabel) {
            ?>
        <div class='admin-action-card'>
            <form method='post' action='admin.php'>
                <input type='hidden' name='tab' value='cache'>
                <input type='hidden' name='admin_action' value='cache_invalidate_ns'>
                <input type='hidden' name='ns' value='<?=htmlspecialchars($nsKey, ENT_QUOTES, 'UTF-8');?>'>
                <?=lt_csrf_input('admin_dashboard');?>
                <div class='admin-action-row'>
                    <div class='admin-action-title'>Инвалидировать</div>
                    <button class='admin-action-button' type='submit'>Выполнить</button>
                </div>
                <div class='admin-action-text'><?=htmlspecialchars($nsLabel, ENT_QUOTES, 'UTF-8');?></div>
            </form>
        </div>
        <?php } ?>

    </div>
</section>

<section class='admin-card'>
    <h2 class='admin-card-title'>Удалить ключ</h2>
    <p class='admin-card-text'>Удалить конкретный ключ из кэша (raw-ключ без namespace-префикса).</p>
    <form class='admin-settings-form' method='post' action='admin.php' style='margin-top:16px;'>
        <input type='hidden' name='tab' value='cache'>
        <input type='hidden' name='admin_action' value='cache_delete_key'>
        <?=lt_csrf_input('admin_dashboard');?>
        <div class='admin-settings-grid'>
            <div class='admin-settings-field'>
                <label class='admin-settings-label' for='cache-key-input'>Ключ</label>
                <input class='admin-settings-input' id='cache-key-input' type='text' name='cache_key'
                       placeholder='Например: user:123' maxlength='255'>
            </div>
        </div>
        <div class='admin-settings-footer'>
            <button class='admin-settings-submit' type='submit'>Удалить ключ</button>
        </div>
    </form>
</section>
