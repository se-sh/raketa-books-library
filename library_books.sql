CREATE DATABASE IF NOT EXISTS library_books
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_0900_ai_ci;

USE library_books;

-- Table structure for table `users`
CREATE TABLE `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `login` VARCHAR(64) UNIQUE NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `created` TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    `updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Table structure for table `books`
CREATE TABLE `books` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `text` LONGTEXT NOT NULL,
    `external_id` VARCHAR(255) DEFAULT NULL,
    `created` TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    `updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
    `is_deleted` TINYINT(1) NOT NULL DEFAULT 0,
    KEY `idx_books_user_id` (`user_id`),
    KEY `idx_books_is_deleted` (`is_deleted`),
    CONSTRAINT `fk_books_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Table structure for table `library_access`
CREATE TABLE `library_access` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `owner_id` INT UNSIGNED NOT NULL,
    `target_id` INT UNSIGNED NOT NULL,
    `created` TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    KEY `idx_library_access_owner` (`owner_id`),
    KEY `idx_library_access_target` (`target_id`),
    CONSTRAINT `uq_library_access` UNIQUE (`owner_id`, `target_id`),
    CONSTRAINT `fk_library_access_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_library_access_target` FOREIGN KEY (`target_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
