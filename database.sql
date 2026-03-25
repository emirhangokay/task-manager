-- ============================================================
-- Görev Yönetim Uygulaması - Veritabanı Şeması
-- ============================================================
-- Bu dosya uygulamanın tüm veritabanı tablolarını içerir.
-- İmport etmek için: mysql -u kullanici -p veritabani_adi < database.sql
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET NAMES utf8mb4;

-- Veritabanı oluştur (gerekirse)
CREATE DATABASE IF NOT EXISTS `task_manager`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `task_manager`;

-- ============================================================
-- users tablosu
-- Kayıtlı kullanıcıları saklar
-- ============================================================
CREATE TABLE IF NOT EXISTS `users` (
  `id`         INT(11)      NOT NULL AUTO_INCREMENT,
  `username`   VARCHAR(50)  NOT NULL,
  `email`      VARCHAR(100) NOT NULL,
  `password`   VARCHAR(255) NOT NULL COMMENT 'bcrypt hash',
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_username` (`username`),
  UNIQUE KEY `uq_email`    (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- categories tablosu
-- Her kullanıcının kendi kategorileri
-- ============================================================
CREATE TABLE IF NOT EXISTS `categories` (
  `id`      INT(11)     NOT NULL AUTO_INCREMENT,
  `user_id` INT(11)     NOT NULL,
  `name`    VARCHAR(50) NOT NULL,
  `color`   VARCHAR(7)  NOT NULL DEFAULT '#6B7280' COMMENT 'HEX renk kodu',
  PRIMARY KEY (`id`),
  KEY `idx_categories_user` (`user_id`),
  CONSTRAINT `fk_categories_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- tasks tablosu
-- Kullanıcılara ait görevler
-- ============================================================
CREATE TABLE IF NOT EXISTS `tasks` (
  `id`          INT(11)                                   NOT NULL AUTO_INCREMENT,
  `user_id`     INT(11)                                   NOT NULL,
  `category_id` INT(11)                                   DEFAULT NULL,
  `title`       VARCHAR(200)                              NOT NULL,
  `description` TEXT                                      DEFAULT NULL,
  `priority`    ENUM('low','medium','high')               NOT NULL DEFAULT 'medium',
  `status`      ENUM('pending','in_progress','completed') NOT NULL DEFAULT 'pending',
  `due_date`    DATE                                      DEFAULT NULL,
  `created_at`  DATETIME                                  NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME                                  NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tasks_user`     (`user_id`),
  KEY `idx_tasks_category` (`category_id`),
  KEY `idx_tasks_status`   (`status`),
  KEY `idx_tasks_priority` (`priority`),
  CONSTRAINT `fk_tasks_user`
    FOREIGN KEY (`user_id`)     REFERENCES `users`      (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tasks_category`
    FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SEED VERİSİ
-- Test kullanıcısı: demo@example.com / şifre: 123456
-- ============================================================

INSERT INTO `users` (`id`, `username`, `email`, `password`, `created_at`) VALUES
(1, 'demo', 'demo@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-03-01 09:00:00');

INSERT INTO `categories` (`id`, `user_id`, `name`, `color`) VALUES
(1, 1, 'Genel',   '#6B7280'),
(2, 1, 'İş',      '#3B82F6'),
(3, 1, 'Kişisel', '#22C55E'),
(4, 1, 'Acil',    '#EF4444');

INSERT INTO `tasks` (`user_id`, `category_id`, `title`, `description`, `priority`, `status`, `due_date`, `created_at`, `updated_at`) VALUES
(1, 2, 'Proje toplantısına hazırlan',   'Q2 yol haritası sunumu için slaytları hazırla.',        'high',   'pending',     CURDATE() + INTERVAL 1 DAY,  NOW(), NOW()),
(1, 2, 'Haftalık raporu gönder',        'Geçen haftanın sprint özetini ekiple paylaş.',           'medium', 'in_progress', CURDATE(),                   NOW(), NOW()),
(1, 4, 'Sunucu faturasını öde',         'AWS faturası bu ay sona eriyor, ödemeyi unutma.',        'high',   'pending',     CURDATE(),                   NOW(), NOW()),
(1, 3, 'Spor salonuna git',             'Pazartesi-Çarşamba-Cuma antrenman programı.',            'low',    'pending',     CURDATE() + INTERVAL 2 DAY,  NOW(), NOW()),
(1, 3, 'Kitap oku',                     '"Atomik Alışkanlıklar" — 3. bölüme kadar bitir.',        'low',    'completed',   NULL,                        NOW(), NOW()),
(1, 1, 'Alışveriş listesi hazırla',     'Haftalık market ihtiyaçlarını listele.',                 'low',    'pending',     CURDATE() + INTERVAL 3 DAY,  NOW(), NOW()),
(1, 2, 'Code review yap',              'Takım arkadaşının PR incelemesi ve geri bildirim.',      'medium', 'in_progress', CURDATE() + INTERVAL 1 DAY,  NOW(), NOW()),
(1, 4, 'Doktor randevusu al',           'Yıllık kontrol için randevu oluştur.',                   'high',   'pending',     CURDATE() - INTERVAL 1 DAY,  NOW(), NOW()),
(1, 2, 'API dokümantasyonunu güncelle', 'Yeni endpointleri Swagger a ekle.',                      'medium', 'completed',   NULL,                        NOW(), NOW()),
(1, 3, 'Tatil planı yap',               'Yaz tatili için otel ve uçak araştır.',                  'low',    'pending',     CURDATE() + INTERVAL 14 DAY, NOW(), NOW());

-- ============================================================
-- V2 MİGRASYON — Mevcut veritabanına uygula
-- ============================================================

-- Kanban sıralama pozisyonu
ALTER TABLE `tasks` ADD COLUMN IF NOT EXISTS `position` INT NOT NULL DEFAULT 0 AFTER `status`;

-- Aktivite logları
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id`         INT(11)  NOT NULL AUTO_INCREMENT,
  `user_id`    INT(11)  NOT NULL,
  `task_id`    INT(11)  DEFAULT NULL,
  `action`     ENUM('created','updated','completed','deleted','status_changed') NOT NULL,
  `details`    TEXT     DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_activity_user` (`user_id`),
  KEY `idx_activity_task` (`task_id`),
  CONSTRAINT `fk_activity_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_activity_task` FOREIGN KEY (`task_id`) REFERENCES `tasks`  (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Kullanıcı ayarları (tema, varsayılan görünüm)
CREATE TABLE IF NOT EXISTS `user_settings` (
  `id`                    INT(11)     NOT NULL AUTO_INCREMENT,
  `user_id`               INT(11)     NOT NULL,
  `theme`                 VARCHAR(10) NOT NULL DEFAULT 'light',
  `default_view`          ENUM('list','kanban','calendar') NOT NULL DEFAULT 'list',
  `notifications_enabled` TINYINT(1)  NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_settings` (`user_id`),
  CONSTRAINT `fk_settings_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- V3 MİGRASYON — Bildirimler, profil, e-posta doğrulama
-- ============================================================

-- Kullanıcı profil alanları
ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `display_name`        VARCHAR(100) DEFAULT NULL AFTER `username`,
  ADD COLUMN IF NOT EXISTS `email_verified`       TINYINT(1)   NOT NULL DEFAULT 0 AFTER `email`,
  ADD COLUMN IF NOT EXISTS `verification_code`    VARCHAR(6)   DEFAULT NULL AFTER `email_verified`,
  ADD COLUMN IF NOT EXISTS `verification_expires` DATETIME     DEFAULT NULL AFTER `verification_code`;

-- Kullanıcı ayarları ek alanlar
ALTER TABLE `user_settings`
  ADD COLUMN IF NOT EXISTS `avatar_color` VARCHAR(7) NOT NULL DEFAULT '#6366f1' AFTER `notifications_enabled`,
  ADD COLUMN IF NOT EXISTS `language`     VARCHAR(5) NOT NULL DEFAULT 'tr'      AFTER `avatar_color`;

-- Bildirimler tablosu
CREATE TABLE IF NOT EXISTS `notifications` (
  `id`         INT(11)      NOT NULL AUTO_INCREMENT,
  `user_id`    INT(11)      NOT NULL,
  `type`       ENUM('due_today','overdue','assigned','system') NOT NULL DEFAULT 'system',
  `title`      VARCHAR(200) NOT NULL,
  `message`    TEXT         DEFAULT NULL,
  `task_id`    INT(11)      DEFAULT NULL,
  `is_read`    TINYINT(1)   NOT NULL DEFAULT 0,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notif_user`   (`user_id`),
  KEY `idx_notif_unread` (`user_id`, `is_read`),
  CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_notif_task` FOREIGN KEY (`task_id`) REFERENCES `tasks`  (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
