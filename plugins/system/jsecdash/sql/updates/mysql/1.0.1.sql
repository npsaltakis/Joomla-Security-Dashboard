-- Schema update for plg_system_jsecdash 1.0.1
-- Adds the Web Application Firewall log table.

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
