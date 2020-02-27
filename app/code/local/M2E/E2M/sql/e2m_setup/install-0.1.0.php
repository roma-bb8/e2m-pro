<?php
/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

/* @var Mage_Core_Model_Resource_Setup $installer */
$installer = $this;

//########################################

$sql = <<<SQL
DROP TABLE IF EXISTS `{$installer->getTable('m2e_e2m_cron_tasks')}`;
CREATE TABLE `{$installer->getTable('m2e_e2m_cron_tasks')}` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `is_running` BOOL NOT NULL DEFAULT FALSE,
  `pause` BOOL NOT NULL DEFAULT FALSE,
  `instance` VARCHAR(255) NOT NULL,
  `data` TEXT DEFAULT NULL,
  `progress` TINYINT DEFAULT 0,
  `updated` TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY (`instance`)
) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;

DROP TABLE IF EXISTS `{$installer->getTable('m2e_e2m_inventory_ebay')}`;
CREATE TABLE `{$installer->getTable('m2e_e2m_inventory_ebay')}` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `marketplace_id` SMALLINT NOT NULL,
  `item_id` DECIMAL(20) UNSIGNED NOT NULL,
  `variation` BOOL NOT NULL DEFAULT FALSE,
  `data` TEXT DEFAULT NULL,
  `updated` TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `marketplace_id` (`marketplace_id`),
  INDEX `item_id` (`item_id`),
  INDEX `variation` (`variation`)
) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;

DROP TABLE IF EXISTS `{$installer->getTable('m2e_e2m_log')}`;
CREATE TABLE `{$installer->getTable('m2e_e2m_log')}` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `task_id` INT(11) UNSIGNED NOT NULL,
  `type` TINYINT(5) NOT NULL DEFAULT 1,
  `description` TEXT DEFAULT NULL,
  `created` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;

INSERT INTO `{$installer->getTable('m2e_e2m_cron_tasks')}` (`instance`, `data`)
VALUES
    ('M2E_E2M_Model_Cron_Task_Completed', '{}');
SQL;

$installer->run($sql);
