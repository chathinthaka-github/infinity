-- 001_create_users_table.sql
CREATE TABLE IF NOT EXISTS `users` (
                                       `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                                       `email` VARCHAR(255) NOT NULL UNIQUE,
                                       `password_hash` VARCHAR(255) NOT NULL,
                                       `full_name` VARCHAR(191) NULL,
                                       `whatsapp_number` VARCHAR(32) NULL,
                                       `role` VARCHAR(32) NOT NULL DEFAULT 'student',
                                       `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                                       `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                       PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
