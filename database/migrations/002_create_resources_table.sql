-- 002_create_resources_and_user_resources.sql
CREATE TABLE IF NOT EXISTS `resources` (
                                           `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                                           `resource_name` VARCHAR(255) NOT NULL,
                                           `description` TEXT NULL,
                                           `resource_type` VARCHAR(64) NOT NULL,
                                           `file_size` VARCHAR(32) NULL,
                                           `duration` VARCHAR(32) NULL,
                                           `google_drive_id` VARCHAR(128) NULL,
                                           `google_drive_url` TEXT NULL,
                                           `thumbnail_url` TEXT NULL,
                                           `created_by` INT UNSIGNED NULL,
                                           `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                           PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_resources` (
                                                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                                                `user_id` INT UNSIGNED NOT NULL,
                                                `resource_id` INT UNSIGNED NOT NULL,
                                                `category` VARCHAR(64) NOT NULL,
                                                `is_completed` TINYINT(1) NOT NULL DEFAULT 0,
                                                `assigned_by_admin_id` INT UNSIGNED NULL,
                                                `assigned_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                                PRIMARY KEY (`id`),
                                                INDEX (`user_id`),
                                                INDEX (`resource_id`),
                                                FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
                                                FOREIGN KEY (`resource_id`) REFERENCES `resources`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
