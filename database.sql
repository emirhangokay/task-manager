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
