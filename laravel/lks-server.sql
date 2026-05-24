-- ============================================================
-- LKS DIKMEN TINGKAT PROVINSI JAWA TIMUR TAHUN 2025
-- Web Technologies - Server Side Module
-- Database Dump: lks-server.sql / data_dump.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS `lks_jatim2025`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `lks_jatim2025`;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `personal_access_tokens`;
DROP TABLE IF EXISTS `scores`;
DROP TABLE IF EXISTS `game_versions`;
DROP TABLE IF EXISTS `games`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- Table: users
-- ============================================================
CREATE TABLE `users` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(60) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` VARCHAR(50) NOT NULL DEFAULT 'player',
  `is_blocked` TINYINT(1) NOT NULL DEFAULT 0,
  `block_reason` TEXT DEFAULT NULL,
  `last_login_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_username_unique` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table: games
-- ============================================================
CREATE TABLE `games` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug` VARCHAR(100) NOT NULL,
  `title` VARCHAR(60) NOT NULL,
  `description` VARCHAR(200) NOT NULL,
  `author_id` BIGINT(20) UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `games_slug_unique` (`slug`),
  CONSTRAINT `games_author_id_foreign` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table: game_versions
-- ============================================================
CREATE TABLE `game_versions` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `game_id` BIGINT(20) UNSIGNED NOT NULL,
  `version` INT(11) NOT NULL,
  `path` VARCHAR(255) NOT NULL,
  `thumbnail` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `game_versions_game_id_foreign` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table: scores
-- ============================================================
CREATE TABLE `scores` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT(20) UNSIGNED NOT NULL,
  `game_id` BIGINT(20) UNSIGNED NOT NULL,
  `game_version_id` BIGINT(20) UNSIGNED DEFAULT NULL,
  `score` INT(11) NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `scores_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `scores_game_id_foreign` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON DELETE CASCADE,
  CONSTRAINT `scores_game_version_id_foreign` FOREIGN KEY (`game_version_id`) REFERENCES `game_versions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table: personal_access_tokens (Laravel Sanctum)
-- ============================================================
CREATE TABLE `personal_access_tokens` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `tokenable_type` VARCHAR(255) NOT NULL,
  `tokenable_id` BIGINT(20) UNSIGNED NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `token` VARCHAR(64) NOT NULL,
  `abilities` TEXT DEFAULT NULL,
  `last_used_at` TIMESTAMP NULL DEFAULT NULL,
  `expires_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`, `tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DUMMY DATA SEEDING
-- ============================================================

-- Users (Bcrypt Hashed passwords matching exact specifications)
-- admin1 / hellouniverse1! -> $2y$12$0gRpZ2QgUthxc6l5Tbug2.Q4ta4jEltK.ZR61T8MJtqvkB72O7cw6
-- admin2 / hellouniverse2! -> $2y$12$aKu0uvtYh/LfvAwWgohn/ulvbO7Dhx.rGzgCs7VJzwQLM2UK.v/Da
-- dev1 / hellobyte1! -> $2y$12$DYbiN4gPz66HYW0cLN6R.OStFBSfE7ZyQBhsozpaxda8LBCX4HEli
-- dev2 / hellobyte2! -> $2y$12$rncR7QgYlwA8aUIn.I5b4ORuSrO6zsIe9djFuw33frsEFedmL.ZQe
-- player1 / helloworld1! -> $2y$12$QkhI9WinvBaUMAg92jdaMO2amCniCzmOAEGTdYv/24X8cuhhEYy2C
-- player2 / helloworld2! -> $2y$12$tJF9/SDsi25UaWfJgxn4FOEmH/qVVpsJQGFLiyWKWiVWVc0jmkh6K
INSERT INTO `users` (`id`, `username`, `password`, `role`, `is_blocked`, `block_reason`, `last_login_at`, `created_at`, `updated_at`) VALUES
(1, 'admin1', '$2y$12$0gRpZ2QgUthxc6l5Tbug2.Q4ta4jEltK.ZR61T8MJtqvkB72O7cw6', 'admin', 0, NULL, NULL, '2024-04-05 20:55:40', '2024-04-05 20:55:40'),
(2, 'admin2', '$2y$12$aKu0uvtYh/LfvAwWgohn/ulvbO7Dhx.rGzgCs7VJzwQLM2UK.v/Da', 'admin', 0, NULL, '2024-04-05 20:55:40', '2024-04-05 20:55:40', '2024-04-05 20:55:40'),
(3, 'dev1', '$2y$12$DYbiN4gPz66HYW0cLN6R.OStFBSfE7ZyQBhsozpaxda8LBCX4HEli', 'dev', 0, NULL, NULL, '2032-01-31 21:59:35', '2032-01-31 21:59:35'),
(4, 'dev2', '$2y$12$rncR7QgYlwA8aUIn.I5b4ORuSrO6zsIe9djFuw33frsEFedmL.ZQe', 'dev', 0, NULL, NULL, '2032-01-31 21:59:35', '2032-01-31 21:59:35'),
(5, 'player1', '$2y$12$QkhI9WinvBaUMAg92jdaMO2amCniCzmOAEGTdYv/24X8cuhhEYy2C', 'player', 0, NULL, NULL, '2024-04-05 20:55:40', '2024-04-05 20:55:40'),
(6, 'player2', '$2y$12$tJF9/SDsi25UaWfJgxn4FOEmH/qVVpsJQGFLiyWKWiVWVc0jmkh6K', 'player', 0, NULL, '2024-04-05 20:55:40', '2024-04-05 20:55:40', '2024-04-05 20:55:40'),
(7, 'blocked_player', '$2y$12$QkhI9WinvBaUMAg92jdaMO2amCniCzmOAEGTdYv/24X8cuhhEYy2C', 'player', 1, 'You have been blocked by an administrator', NULL, '2024-04-05 20:55:40', '2024-04-05 20:55:40');

-- Games (Created by dev1 and dev2)
INSERT INTO `games` (`id`, `slug`, `title`, `description`, `author_id`, `created_at`, `updated_at`) VALUES
(1, 'demo-game-1', 'Demo Game 1', 'This is demo game 1', 3, '2032-01-31 21:59:35', '2032-01-31 21:59:35'),
(2, 'demo-game-2', 'Demo Game 2', 'This is demo game 2', 4, '2032-01-31 21:59:35', '2032-01-31 21:59:35'),
(3, 'demo-game-3', 'Demo Game 3 (No Version)', 'This is demo game 3 without any version uploaded yet', 3, '2032-01-31 21:59:35', '2032-01-31 21:59:35');

-- Game Versions (demo-game-1 and demo-game-2 have version 1, demo-game-3 has none)
INSERT INTO `game_versions` (`id`, `game_id`, `version`, `path`, `thumbnail`, `created_at`, `updated_at`) VALUES
(1, 1, 1, '/games/demo-game-1/1/', '/games/demo-game-1/1/thumbnail.png', '2032-01-31 21:59:35', '2032-01-31 21:59:35'),
(2, 2, 1, '/games/demo-game-2/1/', NULL, '2032-01-31 21:59:35', '2032-01-31 21:59:35');

-- Scores (5 score submissions for demo-game-1 to match page 9 "scoreCount: 5")
INSERT INTO `scores` (`id`, `user_id`, `game_id`, `game_version_id`, `score`, `created_at`, `updated_at`) VALUES
(1, 5, 1, 1, 10, '2032-01-31 22:00:00', '2032-01-31 22:00:00'),
(2, 6, 1, 1, 12, '2032-01-31 22:05:00', '2032-01-31 22:05:00'),
(3, 5, 1, 1, 15, '2032-01-31 22:15:00', '2032-01-31 22:15:00'),
(4, 6, 1, 1, 20, '2032-01-31 22:30:00', '2032-01-31 22:30:00'),
(5, 5, 1, 1, 8, '2032-01-31 22:45:00', '2032-01-31 22:45:00'),
-- Scores for demo-game-2
(6, 5, 2, 2, 100, '2032-02-01 10:00:00', '2032-02-01 10:00:00'),
(7, 6, 2, 2, 150, '2032-02-01 10:15:00', '2032-02-01 10:15:00');

-- ============================================================
-- END OF DUMP
-- ============================================================
