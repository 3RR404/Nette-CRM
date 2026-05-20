-- Nette CRM - Initial Database Schema
-- Run: mysql -u root -p nette_crm < db/migrations/001_initial_schema.sql

SET NAMES utf8mb4;
SET foreign_key_checks = 0;

-- =============================================
-- USERS
-- =============================================
CREATE TABLE `users` (
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `first_name`    VARCHAR(100) NOT NULL,
    `last_name`     VARCHAR(100) NOT NULL,
    `email`         VARCHAR(255) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `role`          ENUM('admin','manager','agent') NOT NULL DEFAULT 'agent',
    `active`        TINYINT(1) NOT NULL DEFAULT 1,
    `avatar`        VARCHAR(255) DEFAULT NULL,
    `phone`         VARCHAR(50) DEFAULT NULL,
    `last_login_at` DATETIME DEFAULT NULL,
    `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- COMPANIES
-- =============================================
CREATE TABLE `companies` (
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`          VARCHAR(255) NOT NULL,
    `email`         VARCHAR(255) DEFAULT NULL,
    `phone`         VARCHAR(50) DEFAULT NULL,
    `website`       VARCHAR(255) DEFAULT NULL,
    `industry`      VARCHAR(100) DEFAULT NULL,
    `employees`     INT UNSIGNED DEFAULT NULL,
    `annual_revenue` DECIMAL(15,2) DEFAULT NULL,
    `address`       VARCHAR(255) DEFAULT NULL,
    `city`          VARCHAR(100) DEFAULT NULL,
    `state`         VARCHAR(100) DEFAULT NULL,
    `postal_code`   VARCHAR(20) DEFAULT NULL,
    `country`       VARCHAR(100) DEFAULT NULL,
    `description`   TEXT DEFAULT NULL,
    `owner_id`      INT UNSIGNED DEFAULT NULL,
    `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_companies_owner` (`owner_id`),
    KEY `idx_companies_name` (`name`),
    CONSTRAINT `fk_companies_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- CONTACTS
-- =============================================
CREATE TABLE `contacts` (
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `first_name`    VARCHAR(100) NOT NULL,
    `last_name`     VARCHAR(100) NOT NULL,
    `email`         VARCHAR(255) DEFAULT NULL,
    `phone`         VARCHAR(50) DEFAULT NULL,
    `mobile`        VARCHAR(50) DEFAULT NULL,
    `company_id`    INT UNSIGNED DEFAULT NULL,
    `job_title`     VARCHAR(100) DEFAULT NULL,
    `status`        ENUM('lead','prospect','customer','churned','other') NOT NULL DEFAULT 'lead',
    `source`        ENUM('web','phone','email','referral','social','event','other') DEFAULT NULL,
    `address`       VARCHAR(255) DEFAULT NULL,
    `city`          VARCHAR(100) DEFAULT NULL,
    `state`         VARCHAR(100) DEFAULT NULL,
    `postal_code`   VARCHAR(20) DEFAULT NULL,
    `country`       VARCHAR(100) DEFAULT NULL,
    `description`   TEXT DEFAULT NULL,
    `owner_id`      INT UNSIGNED DEFAULT NULL,
    `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_contacts_company` (`company_id`),
    KEY `idx_contacts_owner` (`owner_id`),
    KEY `idx_contacts_status` (`status`),
    KEY `idx_contacts_email` (`email`),
    CONSTRAINT `fk_contacts_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_contacts_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- DEALS (Sales Pipeline)
-- =============================================
CREATE TABLE `deals` (
    `id`                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title`               VARCHAR(255) NOT NULL,
    `contact_id`          INT UNSIGNED DEFAULT NULL,
    `company_id`          INT UNSIGNED DEFAULT NULL,
    `owner_id`            INT UNSIGNED DEFAULT NULL,
    `stage`               ENUM('new','qualified','proposal','negotiation','won','lost') NOT NULL DEFAULT 'new',
    `value`               DECIMAL(15,2) DEFAULT NULL,
    `currency`            CHAR(3) NOT NULL DEFAULT 'USD',
    `probability`         TINYINT UNSIGNED DEFAULT NULL COMMENT '0-100 percent',
    `expected_close_date` DATE DEFAULT NULL,
    `won_at`              DATETIME DEFAULT NULL,
    `lost_at`             DATETIME DEFAULT NULL,
    `lost_reason`         VARCHAR(255) DEFAULT NULL,
    `description`         TEXT DEFAULT NULL,
    `created_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_deals_contact` (`contact_id`),
    KEY `idx_deals_company` (`company_id`),
    KEY `idx_deals_owner` (`owner_id`),
    KEY `idx_deals_stage` (`stage`),
    CONSTRAINT `fk_deals_contact` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_deals_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_deals_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- TASKS & ACTIVITIES
-- =============================================
CREATE TABLE `tasks` (
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title`         VARCHAR(255) NOT NULL,
    `type`          ENUM('call','meeting','email','task','deadline') NOT NULL DEFAULT 'task',
    `related_type`  ENUM('contact','company','deal') DEFAULT NULL,
    `related_id`    INT UNSIGNED DEFAULT NULL,
    `assigned_to`   INT UNSIGNED DEFAULT NULL,
    `due_date`      DATE DEFAULT NULL,
    `due_time`      TIME DEFAULT NULL,
    `status`        ENUM('open','in_progress','completed','cancelled') NOT NULL DEFAULT 'open',
    `priority`      ENUM('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
    `description`   TEXT DEFAULT NULL,
    `completed_at`  DATETIME DEFAULT NULL,
    `created_by`    INT UNSIGNED NOT NULL,
    `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_tasks_related` (`related_type`, `related_id`),
    KEY `idx_tasks_assigned` (`assigned_to`),
    KEY `idx_tasks_status` (`status`),
    KEY `idx_tasks_due` (`due_date`),
    CONSTRAINT `fk_tasks_assigned` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_tasks_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- NOTES
-- =============================================
CREATE TABLE `notes` (
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `content`       TEXT NOT NULL,
    `related_type`  ENUM('contact','company','deal') NOT NULL,
    `related_id`    INT UNSIGNED NOT NULL,
    `created_by`    INT UNSIGNED NOT NULL,
    `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_notes_related` (`related_type`, `related_id`),
    KEY `idx_notes_created_by` (`created_by`),
    CONSTRAINT `fk_notes_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- TAGS
-- =============================================
CREATE TABLE `tags` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`       VARCHAR(100) NOT NULL,
    `color`      VARCHAR(7) NOT NULL DEFAULT '#6c757d',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_tags_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `entity_tags` (
    `entity_type` ENUM('contact','company','deal') NOT NULL,
    `entity_id`   INT UNSIGNED NOT NULL,
    `tag_id`      INT UNSIGNED NOT NULL,
    PRIMARY KEY (`entity_type`, `entity_id`, `tag_id`),
    KEY `idx_entity_tags_tag` (`tag_id`),
    CONSTRAINT `fk_entity_tags_tag` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- ACTIVITY LOG
-- =============================================
CREATE TABLE `activity_log` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`     INT UNSIGNED DEFAULT NULL,
    `action`      VARCHAR(100) NOT NULL,
    `entity_type` VARCHAR(50) DEFAULT NULL,
    `entity_id`   INT UNSIGNED DEFAULT NULL,
    `description` TEXT DEFAULT NULL,
    `old_values`  JSON DEFAULT NULL,
    `new_values`  JSON DEFAULT NULL,
    `ip_address`  VARCHAR(45) DEFAULT NULL,
    `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_log_user` (`user_id`),
    KEY `idx_log_entity` (`entity_type`, `entity_id`),
    KEY `idx_log_created` (`created_at`),
    CONSTRAINT `fk_log_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- PIPELINE STAGES (configurable)
-- =============================================
CREATE TABLE `pipeline_stages` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`       VARCHAR(100) NOT NULL,
    `slug`       VARCHAR(50) NOT NULL,
    `color`      VARCHAR(7) NOT NULL DEFAULT '#6c757d',
    `sort_order` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `is_won`     TINYINT(1) NOT NULL DEFAULT 0,
    `is_lost`    TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_stage_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- SEED DATA
-- =============================================

-- Default admin user (password: admin123)
INSERT INTO `users` (`first_name`, `last_name`, `email`, `password_hash`, `role`) VALUES
('Admin', 'User', 'admin@crm.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('John', 'Manager', 'manager@crm.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager'),
('Jane', 'Agent', 'agent@crm.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'agent');

-- Default pipeline stages
INSERT INTO `pipeline_stages` (`name`, `slug`, `color`, `sort_order`, `is_won`, `is_lost`) VALUES
('New Lead',      'new',          '#6c757d', 1, 0, 0),
('Qualified',     'qualified',    '#0d6efd', 2, 0, 0),
('Proposal Sent', 'proposal',     '#fd7e14', 3, 0, 0),
('Negotiation',   'negotiation',  '#6f42c1', 4, 0, 0),
('Won',           'won',          '#198754', 5, 1, 0),
('Lost',          'lost',         '#dc3545', 6, 0, 1);

-- Default tags
INSERT INTO `tags` (`name`, `color`) VALUES
('VIP',        '#ffc107'),
('Hot Lead',   '#dc3545'),
('Cold Lead',  '#0dcaf0'),
('Newsletter', '#0d6efd'),
('Partner',    '#6f42c1');

-- Sample company
INSERT INTO `companies` (`name`, `email`, `phone`, `website`, `industry`, `employees`, `city`, `country`, `owner_id`) VALUES
('Acme Corp', 'info@acme.com', '+1-555-0100', 'https://acme.com', 'Technology', 250, 'New York', 'USA', 1),
('Beta Ltd', 'hello@betaltd.com', '+1-555-0200', 'https://betaltd.com', 'Finance', 50, 'London', 'UK', 2);

-- Sample contacts
INSERT INTO `contacts` (`first_name`, `last_name`, `email`, `phone`, `company_id`, `job_title`, `status`, `source`, `city`, `country`, `owner_id`) VALUES
('Alice', 'Smith', 'alice@acme.com', '+1-555-1001', 1, 'CEO', 'customer', 'referral', 'New York', 'USA', 1),
('Bob', 'Jones', 'bob@betaltd.com', '+1-555-1002', 2, 'CFO', 'prospect', 'web', 'London', 'UK', 2),
('Carol', 'Brown', 'carol@example.com', '+1-555-1003', NULL, 'Freelancer', 'lead', 'email', 'Berlin', 'Germany', 3);

-- Sample deals
INSERT INTO `deals` (`title`, `contact_id`, `company_id`, `owner_id`, `stage`, `value`, `currency`, `probability`, `expected_close_date`) VALUES
('Acme Enterprise License', 1, 1, 1, 'negotiation', 25000.00, 'USD', 75, '2026-06-30'),
('Beta Analytics Suite', 2, 2, 2, 'proposal',     12000.00, 'USD', 50, '2026-07-15'),
('Carol Consulting',     3, NULL, 3, 'qualified',     3500.00, 'USD', 40, '2026-08-01');

SET foreign_key_checks = 1;
