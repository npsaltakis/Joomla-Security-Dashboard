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

CREATE TABLE IF NOT EXISTS `#__jsecdash_waf_log` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ip` VARCHAR(45) NOT NULL,
    `rule_id` VARCHAR(50) NOT NULL DEFAULT '',
    `category` VARCHAR(30) NOT NULL DEFAULT '',
    `matched_field` VARCHAR(100) NOT NULL DEFAULT '',
    `payload` VARCHAR(255) NOT NULL DEFAULT '',
    `uri` VARCHAR(500) NOT NULL DEFAULT '',
    `action` VARCHAR(20) NOT NULL DEFAULT 'detected',
    `created` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_jsecdash_waf_ip` (`ip`),
    KEY `idx_jsecdash_waf_created` (`created`),
    KEY `idx_jsecdash_waf_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
