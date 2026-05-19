<?php
/**
 * PHPUnit bootstrap — loads only pure, DB-free functions for unit testing.
 * DB-dependent functions are NOT loaded here; use integration tests for those.
 */

// ---------------------------------------------------------------------------
// Constants required by the function files
// ---------------------------------------------------------------------------
defined('CMS')        || define('CMS', true);
defined('DB_PREFIX')  || define('DB_PREFIX', '');
defined('COOKIE_SALT') || define('COOKIE_SALT', 'test-cookie-salt');
defined('DEBUG')      || define('DEBUG', false);
defined('DEBUG_SQL')  || define('DEBUG_SQL', false);
defined('COLLATE')    || define('COLLATE', 'utf8mb4');

// ---------------------------------------------------------------------------
// Minimal globals so included files don't blow up on first-use
// ---------------------------------------------------------------------------
$GLOBALS['config'] = [
    'max_size_image' => 5 * 1024 * 1024,
    'sitename'       => 'TestTracker',
    'gzip'           => 0,
];
$GLOBALS['language'] = [
    'announce_1'  => 'Missing required field: %s',
    'announce_2'  => 'Invalid length for %s: got %d bytes (%s)',
    'default_1'   => 'Error',
    'upload_19'   => 'Empty filename',
    'upload_20'   => 'Invalid filename',
    'upload_21'   => 'Not a .torrent file',
    'upload_22'   => 'Not an uploaded file',
    'upload_23'   => 'Empty file',
    'upload_44'   => 'Thumbs.db not allowed',
    'comments_15' => 'Failed to save comment',
    'comments_16' => 'Failed to update comment',
];

// ---------------------------------------------------------------------------
// Override err() so tests can assert on errors instead of dying
// ---------------------------------------------------------------------------
if (!function_exists('err')) {
    function err($msg = '', $sub = '', $type = 0): never
    {
        throw new \RuntimeException((string) ($sub !== '' ? $sub : $msg));
    }
}

// ---------------------------------------------------------------------------
// Load pure function files — no DB access on load
// ---------------------------------------------------------------------------
require_once dirname(__DIR__, 2) . '/app/system/functions/functions.upload.php';
require_once dirname(__DIR__, 2) . '/app/system/functions/functions.announce.php';
require_once dirname(__DIR__, 2) . '/app/system/functions/functions.tags.php';
require_once dirname(__DIR__, 2) . '/app/core/http.php';
require_once dirname(__DIR__, 2) . '/app/core/browse.php';

// ---------------------------------------------------------------------------
// Auth helpers (copied verbatim from system/functions/functions.php so we
// don't have to pull in the entire file with its DB dependencies)
// ---------------------------------------------------------------------------
if (!function_exists('lt_password_hash_value')) {
    function lt_password_hash_value(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }
}

if (!function_exists('lt_password_verify_user')) {
    function lt_password_verify_user(string $password, array $userRow, bool &$needsRehash = false): bool
    {
        $needsRehash  = false;
        $storedHash   = trim((string) ($userRow['password'] ?? ''));
        $passwordCode = (string) ($userRow['password_code'] ?? '');

        if ($storedHash === '') {
            return false;
        }

        if (str_starts_with($storedHash, '$2y$') || str_starts_with($storedHash, '$argon2')) {
            $isValid = password_verify($password, $storedHash);
            if ($isValid) {
                $needsRehash = password_needs_rehash($storedHash, PASSWORD_DEFAULT);
            }
            return $isValid;
        }

        // Legacy MD5 check
        $legacyHash = md5($passwordCode . $password . $passwordCode);
        if (!hash_equals($storedHash, $legacyHash)) {
            return false;
        }

        $needsRehash = true;
        return true;
    }
}

// ---------------------------------------------------------------------------
// Validation helpers (copied from system/functions/functions.php)
// ---------------------------------------------------------------------------
if (!function_exists('validfilename')) {
    function validfilename(string $name): bool
    {
        return (bool) preg_match('/^[^\0-\x1f:\\\\\/?*\xff#<>|]+$/si', $name);
    }
}

if (!function_exists('validusername')) {
    function validusername(string $username): bool
    {
        if ($username === '') {
            return false;
        }
        $allowed = 'abcdefghijklmnopqrstuvwxyz'
            . 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'
            . '0123456789_'
            . 'абвгдеёжзйиклмнопрстуфхшщэюяьъчйыц'
            . 'ЦЧЙАБВГДЕЁЖЗИКЛМНОПРСТУФХШЩЭЮЯЬЪЙЫ';
        for ($i = 0, $len = strlen($username); $i < $len; ++$i) {
            if (strpos($allowed, $username[$i]) === false) {
                return false;
            }
        }
        return true;
    }
}

if (!function_exists('validemail')) {
    function validemail(string $email): bool
    {
        return (bool) preg_match('/^[\w.+-]+@([\w.-]+\.)+[a-z]{2,6}$/is', $email);
    }
}
