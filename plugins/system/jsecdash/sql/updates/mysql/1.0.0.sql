-- Schema baseline for plg_system_jsecdash 1.0.0
-- These statements mirror the install script so that sites updating from a
-- pre-schema build are brought up to the 1.0.0 baseline. All statements are
-- idempotent (CREATE TABLE IF NOT EXISTS) and therefore safe to re-run.

CREATE TABLE IF NOT EXISTS `#__jsecdash_attempts` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ip` VARCHAR(45) NOT NULL,
    `username` VARCHAR(255) NOT NULL DEFAULT '',
    `attempt_time` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_jsecdash_attempts_ip` (`ip`),
    KEY `idx_jsecdash_attempts_time` (`attempt_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `#__jsecdash_blocks` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ip` VARCHAR(45) NOT NULL,
    `reason` VARCHAR(255) NOT NULL DEFAULT '',
    `created` DATETIME NOT NULL,
    `expires` DATETIME NULL DEFAULT NULL,
    `auto` TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_jsecdash_blocks_ip` (`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `#__jsecdash_filehashes` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `filepath` VARCHAR(500) NOT NULL,
    `hash` CHAR(64) NOT NULL,
    `filesize` BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `modified` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_jsecdash_filehashes_path` (`filepath`(255))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
