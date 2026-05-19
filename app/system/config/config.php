<?php
/*
===================================================================
LiteTracker Source
===================================================================
by Nick
-------------------------------------------------------------------
Назначение: Конфиг
===================================================================
*/

if (!function_exists('lt_env')) {
    function lt_env($name, $default = null)
    {
        $value = getenv($name);
        return ($value === false || $value === null || $value === '' ? $default : $value);
    }
}

if (!function_exists('lt_env_value')) {
    function lt_env_value($name, $default = null)
    {
        return lt_env($name, $default);
    }
}

if (!function_exists('lt_env_bool')) {
    function lt_env_bool($name, $default = 0)
    {
        $value = getenv($name);
        if ($value === false || $value === '') {
            return (int) $default;
        }

        $value = strtolower(trim((string) $value));
        return (int) in_array($value, array('1', 'true', 'yes', 'on'), true);
    }
}

if (!function_exists('lt_runtime_environment')) {
    function lt_runtime_environment()
    {
        static $runtime = null;

        if ($runtime !== null) {
            return $runtime;
        }

        $env = strtolower(trim((string) lt_env('LITETRACKER_RUNTIME', '')));
        if (in_array($env, array('docker', 'local', 'production'), true)) {
            $runtime = $env;
            return $runtime;
        }

        if (is_file('/.dockerenv')) {
            $runtime = 'docker';
            return $runtime;
        }

        $appEnv = strtolower(trim((string) lt_env('APP_ENV', lt_env('ENVIRONMENT', ''))));
        if (in_array($appEnv, array('prod', 'production'), true)) {
            $runtime = 'production';
            return $runtime;
        }

        $serverName = strtolower((string) ($_SERVER['SERVER_NAME'] ?? ''));
        if ($serverName === 'localhost' || $serverName === '127.0.0.1' || $serverName === '::1' || PHP_SAPI === 'cli') {
            $runtime = 'local';
            return $runtime;
        }

        $runtime = 'unknown';
        return $runtime;
    }
}

$ltRootDir = dirname(__DIR__, 2);
$ltRuntimeEnvironment = lt_runtime_environment();
$ltCacheDriver = trim((string) lt_env('LITETRACKER_CACHE_DRIVER', 'memcached'));
$ltDefaultMemcachedHost = ($ltRuntimeEnvironment === 'docker' ? 'memcached' : '127.0.0.1');
$ltCacheHost = trim((string) lt_env('LITETRACKER_MEMCACHED_HOST', lt_env('LITETRACKER_CACHE_HOST', $ltDefaultMemcachedHost)));
$ltCachePort = (int) lt_env('LITETRACKER_MEMCACHED_PORT', lt_env('LITETRACKER_CACHE_PORT', 11211));
$ltCacheNamespace = trim((string) lt_env('LITETRACKER_CACHE_NAMESPACE', 'litetracker'));
$ltCronMode = strtolower(trim((string) lt_env_value('LITETRACKER_CRON_MODE', 'browser')));
$ltUseExternalCron = (int) in_array($ltCronMode, array('external', 'scheduler', 'cron'), true);
$ltDebug = lt_env_bool('LITETRACKER_DEBUG', 0);
$ltSqlDebug = lt_env_bool('LITETRACKER_DEBUG_SQL', lt_env_bool('LITETRACKER_SQL_DEBUG', 0));
$ltRemoteTrackerTimeout = max(1, (int) lt_env_value('LITETRACKER_REMOTE_TIMEOUT', 2));
$ltPublicScheme = strtolower(trim((string) lt_env_value('LITETRACKER_PUBLIC_SCHEME', 'https')));
if (!in_array($ltPublicScheme, array('http', 'https'), true)) {
    $ltPublicScheme = 'https';
}
$ltPublicHost = trim((string) lt_env_value('LITETRACKER_PUBLIC_HOST', 'localhost'));
$ltAnnounceHost = trim((string) lt_env_value('LITETRACKER_ANNOUNCE_HOST', 'bt.localhost'));
$ltAnnouncePath = trim((string) lt_env_value('LITETRACKER_ANNOUNCE_PATH', '/announce.php'));
$ltCookieSalt = trim((string) lt_env_value('LITETRACKER_COOKIE_SALT', sha1($ltRootDir . '|' . $ltPublicHost)));
$ltTimezone = trim((string) lt_env_value('LITETRACKER_TIMEZONE', 'Europe/Moscow'));
if ($ltTimezone === '' || date_default_timezone_set($ltTimezone) === false) {
    $ltTimezone = 'Europe/Moscow';
    date_default_timezone_set($ltTimezone);
}
$ltMysqlTimezoneOffset = (new DateTime('now', new DateTimeZone($ltTimezone)))->format('P');
$ltCronToken = trim((string) lt_env_value('LITETRACKER_CRON_TOKEN', ''));
$ltCaptchaEnabled = lt_env_bool('LITETRACKER_CAPTCHA_ENABLED', lt_env_bool('LITETRACKER_RECAPTCHA_ENABLED', 0));
$ltCaptchaLogin = lt_env_bool('LITETRACKER_CAPTCHA_LOGIN', lt_env_bool('LITETRACKER_RECAPTCHA_LOGIN', 0));
$ltCaptchaSignup = lt_env_bool('LITETRACKER_CAPTCHA_SIGNUP', lt_env_bool('LITETRACKER_RECAPTCHA_SIGNUP', 0));
$ltCaptchaDownload = lt_env_bool('LITETRACKER_CAPTCHA_DOWNLOAD', lt_env_bool('LITETRACKER_RECAPTCHA_DOWNLOAD', 0));
$ltMailFrom = trim((string) lt_env_value('LITETRACKER_MAIL_FROM', 'admin@localhost'));
$ltMailLogin = trim((string) lt_env_value('LITETRACKER_MAIL_LOGIN', ''));
$ltMailPassword = trim((string) lt_env_value('LITETRACKER_MAIL_PASSWORD', ''));
$ltWmzNumber = trim((string) lt_env_value('LITETRACKER_WMZ_NUMBER', ''));
$ltWmrNumber = trim((string) lt_env_value('LITETRACKER_WMR_NUMBER', ''));
$ltAnnounceConnectivityProbe = lt_env_bool('LITETRACKER_ANNOUNCE_CONNECTIVITY_PROBE', 0);
$ltMetadataGrabberEnabled = lt_env_bool('LITETRACKER_METADATA_GRABBER_ENABLED', 1);
if ($ltAnnouncePath === '') {
    $ltAnnouncePath = '/announce.php';
}
if ($ltAnnouncePath[0] !== '/') {
    $ltAnnouncePath = '/' . $ltAnnouncePath;
}
$ltAnnounceUrl = trim((string) lt_env_value('LITETRACKER_ANNOUNCE_URL', $ltPublicScheme . '://' . $ltAnnounceHost . $ltAnnouncePath));
$ltLocalRetrackerUrl = trim((string) lt_env_value('LITETRACKER_LOCAL_RETRACKER_URL', $ltAnnounceUrl));

$config  = array(
    'sitename' => 'LiteTracker Engine', //Название сайта
    'gzip' => 1, //Использовать gzip-сжатие
    'template' => 'default', //Шаблон сайта
    'default_theme' => '', //Тема оформления по умолчанию (пусто = используется шаблон 'template')
    'lang' => 'Russian', //Язык сайта
    'siteonline' => 1, //Сайт открыт - 1 / Сайт закрыт - 0
    'rewrite' => 0, //ЧПУ

    //Рейтинг
    'bad_rating' => 5, //Рейтинг , который на грани плохого . Если у пользователя рейтинг ниже , то ему закрываются некоторые функции
    'days_rating' => 30, //Через какое время будет записываться плохой рейтинг (в днях)
    //Деньги
    'begin_money' => '3', //Начальный деньги при регистрации
    'wmz_number' => $ltWmzNumber, //Кошелек WMZ
    'wmr_number' => $ltWmrNumber, //Кошелек WMR
    'project_help_text' => 'Оплата аренды сервера, принимаем любую помощь.',
    'project_help_period' => '',
    'project_help_current' => 0,
    'project_help_goal' => 0,
    'project_help_button_label' => 'Помочь проекту',
    'project_help_button_href' => '',

    'registeronline' => 1, //Регистрация открыта
    'announce_url' => $ltAnnounceUrl, //Основной announce URL для новых скачиваемых torrent-файлов
    'local_retracker_url' => $ltLocalRetrackerUrl, //Локальный retracker для torrent-файлов; при необходимости можно изменить в конфиге
    'announce_interval' => 30 * 60,
    'remote_tracker_timeout' => $ltRemoteTrackerTimeout,
    'metadata_grabber_enabled' => $ltMetadataGrabberEnabled,
    'timezone' => $ltTimezone,
    'mysql_timezone_offset' => $ltMysqlTimezoneOffset,

    'max_size_image' => 5 * 1024 * 1024, //Макс размер загружаемой картинки

    //Модули поиска
    'search_forum' => 1, //Включить форумный вид (список категорий) на главной поиска
    'search_image' => 0, //Включить модуль «Картинки» в поиске
    'search_image_lenght' => 0, //Количество символов, при котором выводятся картинки



    //Новинка
    'releases_news' => 30, //В течение какого времени релиз считается "новикой" (n дней)


    //Привязка cookies к домену
    'cookies_mode' => 0,

    //Система бонусов
    // бонусы начисляются за сидирование или активное присутствие на сайте; за 1 час пользователь получает {bonus_price} бонусов
    'bonus_source' => 'seeding', //Источник бонусов: seeding или online
    'bonus_price' => 10, //Количество бонусов, получаемых пользователем за час
    'voice_price' => 10, //Совместимость со старым конфигом

    //Локальная CAPTCHA
    'captcha' => 1, //Использовать локальную CAPTCHA
    'reCaptcha' => $ltCaptchaEnabled, //Совместимость со старым ключом (план удаления: v1.0, после миграции админ-настроек на всех установках)
    'reCaptcha_publickey' => '', //Устаревший ключ; оставлен только для совместимости (см. план удаления выше)
    'reCaptcha_privatekey' => '', //Устаревший ключ; оставлен только для совместимости (см. план удаления выше)

    //CAPTCHA for LiteTracker
    'reCaptcha_login' => $ltCaptchaLogin, //Использовать для входа
    'reCaptcha_signup' => $ltCaptchaSignup, //Использовать для регистрации
    'reCaptcha_download' => $ltCaptchaDownload, //Использовать для скачивания


    //Настройка отправки писем
    'mail' => array(
        'use' => 1, //Использовать e-mail функции
        'type' => 'mail', //Тип отправки почты
        //mail - по умолчанию , отправка функцией mail
        //smtp - отправка smtp
        'from' => $ltMailFrom, //Какой e-mail указывать
        'from_name' => 'Torrent - Tracker',
        //Если используете SMTP
        'host' => '', //Хост сервера
        'port' => 25, //Порт сервера
        'login' => $ltMailLogin, //Логин
        'password' => $ltMailPassword,  //Пароль
    ),

    //Настройка кеша
    'cache' => array(
        'driver' => $ltCacheDriver, //filecache | memcached
        'namespace' => $ltCacheNamespace,
        'memcached' => array(
            'host' => $ltCacheHost,
            'port' => $ltCachePort,
            'namespace' => $ltCacheNamespace,
            'connect_timeout_ms' => 150,
            'poll_timeout_ms' => 150,
            'send_timeout_ms' => 150,
            'recv_timeout_ms' => 150,
            'retry_timeout' => 1,
            'server_failure_limit' => 1,
            'remove_failed_servers' => 1,
        ),
    ),

    'filecache' => array(
        'use' => 1,
        'dir' => (function_exists('lt_cache_path') ? lt_cache_path('filecache').'/' : $ltRootDir . '/storage/cache/filecache/'),
        'type' => '.cache',
        'timeout' => 60,
    ),

    'crontab' => $ltUseExternalCron, //Использовать планировщик заданий cronNNLite
    //При использовании данной функции требуется программа cronNNLite или добавить задание в etc/crontab
    //[Внимание! При включение данной фукнции, все части трекера (к примеру : обновление, автоочистка) отключаются]
    //0,15,30,45   *   *   *   *   root   /usr/bin/wget -O /dev/null -q http://site.com/autoclean.php > /dev/null 2>&1
    //0/10   *   *   *   *   root   /usr/bin/wget -O /dev/null -q http://site.com/update.peers.php > /dev/null 2>&1

    'cron_token' => $ltCronToken,
    'announce_connectivity_probe' => $ltAnnounceConnectivityProbe,



    'sql_log_file' => (function_exists('lt_logs_path') ? lt_logs_path('mysql_log_' . date("M_d_Y") . '.log') : $ltRootDir . '/storage/logs/mysql_log_' . date("M_d_Y") . '.log'), //Файл с логами ошибок mySQL

);


//Debug flags. Debug output is additionally restricted to administrators.
define('DEBUG', $ltDebug);
define('DEBUG_SQL', $ltSqlDebug);

//Настройка cookies
define("COOKIE_SALT", $ltCookieSalt);
define("COOKIE_ID", 'id_user'); //Название ID
define("COOKIE_PASSWORD", 'id_password'); //Название PASSWORD
