-- =============================================================================
-- Migration: Admin Control Center
-- Description: Creates tables for audit log, DB-backed settings, advertising,
--              and general reports/complaints system.
-- Safe to run multiple times (IF NOT EXISTS + INSERT IGNORE).
-- =============================================================================

-- 1. admin_audit_log — full log of all admin actions
CREATE TABLE IF NOT EXISTS `admin_audit_log` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `admin_id`    INT UNSIGNED  NOT NULL DEFAULT 0,
  `action`      VARCHAR(100)  NOT NULL DEFAULT '',
  `module`      VARCHAR(60)   NOT NULL DEFAULT '',
  `target_type` VARCHAR(60)   NOT NULL DEFAULT '',
  `target_id`   INT UNSIGNED  NOT NULL DEFAULT 0,
  `old_value`   TEXT          NULL,
  `new_value`   TEXT          NULL,
  `ip`          VARCHAR(45)   NOT NULL DEFAULT '',
  `user_agent`  VARCHAR(255)  NOT NULL DEFAULT '',
  `created_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_admin_id`   (`admin_id`),
  KEY `idx_action`     (`action`),
  KEY `idx_module`     (`module`),
  KEY `idx_target`     (`target_type`, `target_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. site_settings — DB-backed site configuration
CREATE TABLE IF NOT EXISTS `site_settings` (
  `id`            INT UNSIGNED                                   NOT NULL AUTO_INCREMENT,
  `setting_key`   VARCHAR(100)                                   NOT NULL,
  `setting_value` TEXT                                           NULL,
  `setting_type`  ENUM('text','int','bool','json','select')      NOT NULL DEFAULT 'text',
  `description`   TEXT                                           NULL,
  `is_public`     TINYINT(1)                                     NOT NULL DEFAULT 0,
  `updated_by`    INT UNSIGNED                                   NOT NULL DEFAULT 0,
  `updated_at`    DATETIME                                       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. ad_slots — advertising slot definitions
CREATE TABLE IF NOT EXISTS `ad_slots` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `code`        VARCHAR(100)  NOT NULL,
  `title`       VARCHAR(255)  NOT NULL DEFAULT '',
  `description` TEXT          NULL,
  `is_active`   TINYINT(1)    NOT NULL DEFAULT 1,
  `created_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. ads — individual advertisement entries
CREATE TABLE IF NOT EXISTS `ads` (
  `id`              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `slot_id`         INT UNSIGNED  NOT NULL DEFAULT 0,
  `title`           VARCHAR(255)  NOT NULL DEFAULT '',
  `html_code`       TEXT          NULL,
  `image_url`       VARCHAR(500)  NOT NULL DEFAULT '',
  `target_url`      VARCHAR(500)  NOT NULL DEFAULT '',
  `is_active`       TINYINT(1)    NOT NULL DEFAULT 1,
  `show_to_guests`  TINYINT(1)    NOT NULL DEFAULT 1,
  `show_to_users`   TINYINT(1)    NOT NULL DEFAULT 1,
  `show_on_desktop` TINYINT(1)    NOT NULL DEFAULT 1,
  `show_on_mobile`  TINYINT(1)    NOT NULL DEFAULT 1,
  `starts_at`       DATETIME      NULL,
  `ends_at`         DATETIME      NULL,
  `created_by`      INT UNSIGNED  NOT NULL DEFAULT 0,
  `created_at`      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_slot_id`   (`slot_id`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. reports — general user reports/complaints
--    Unifies comments_reports, comments_users_reports, and future report types.
CREATE TABLE IF NOT EXISTS `reports` (
  `id`                INT UNSIGNED                                            NOT NULL AUTO_INCREMENT,
  `reporter_id`       INT UNSIGNED                                            NOT NULL DEFAULT 0,
  `target_type`       VARCHAR(60)                                             NOT NULL DEFAULT '',
  `target_id`         INT UNSIGNED                                            NOT NULL DEFAULT 0,
  `reason`            TEXT                                                    NOT NULL,
  `status`            ENUM('new','in_progress','resolved','rejected')         NOT NULL DEFAULT 'new',
  `assigned_to`       INT UNSIGNED                                            NOT NULL DEFAULT 0,
  `moderator_comment` TEXT                                                    NULL,
  `created_at`        DATETIME                                                NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        DATETIME                                                NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `resolved_at`       DATETIME                                                NULL,
  PRIMARY KEY (`id`),
  KEY `idx_status`   (`status`),
  KEY `idx_target`   (`target_type`, `target_id`),
  KEY `idx_reporter` (`reporter_id`),
  KEY `idx_assigned` (`assigned_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- Default site_settings values
-- INSERT IGNORE ensures existing values are never overwritten.
-- =============================================================================

INSERT IGNORE INTO `site_settings` (`setting_key`, `setting_value`, `setting_type`, `description`, `is_public`) VALUES
-- Maintenance mode
('maintenance_mode',           '0',                          'bool',   'Режим обслуживания: 1 — сайт закрыт для пользователей',        0),
('maintenance_message',        'Сайт временно недоступен.', 'text',   'Сообщение, отображаемое в режиме обслуживания',                 0),
('maintenance_allowed_admins', '1',                          'bool',   'Разрешить вход администраторам в режиме обслуживания',          0),
('maintenance_starts_at',      '',                           'text',   'Плановое начало обслуживания (DATETIME или пусто)',             0),
('maintenance_ends_at',        '',                           'text',   'Плановое окончание обслуживания (DATETIME или пусто)',          0),

-- Site meta
('site_description',           '',                           'text',   'Описание сайта (meta description)',                            1),
('admin_email',                '',                           'text',   'E-mail администратора для системных уведомлений',              0),

-- Registration & accounts
('email_confirmation',         '0',                          'bool',   'Требовать подтверждение e-mail при регистрации',               0),
('invites_enabled',            '0',                          'bool',   'Включить систему инвайтов',                                    0),

-- Feature flags
('comments_enabled',           '1',                          'bool',   'Разрешить комментарии на сайте',                               0),
('ratings_enabled',            '1',                          'bool',   'Разрешить оценки раздач',                                      0),
('bookmarks_enabled',          '1',                          'bool',   'Разрешить закладки',                                           0),
('chat_enabled',               '0',                          'bool',   'Включить чат',                                                 0),
('ads_enabled',                '0',                          'bool',   'Включить показ рекламы',                                       0),

-- Cache TTLs (seconds)
('cache_ttl_main',             '300',                        'int',    'TTL кэша главной страницы (секунды)',                          0),
('cache_ttl_categories',       '600',                        'int',    'TTL кэша списка категорий (секунды)',                          0),
('cache_ttl_stats',            '300',                        'int',    'TTL кэша статистики сайта (секунды)',                          0),
('cache_ttl_torrent',          '1800',                       'int',    'TTL кэша страницы раздачи (секунды)',                          0),
('cache_ttl_user',             '300',                        'int',    'TTL кэша профиля пользователя (секунды)',                      0);
