<?php

/* @var Mage_Core_Model_Resource_Setup $installer */
$installer = $this;

//########################################

$sql = <<<SQL
DROP TABLE IF EXISTS `m2e_e2m_cron_tasks_in_processing`;
CREATE TABLE `m2e_e2m_cron_tasks_in_processing` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `is_running` BOOL NOT NULL DEFAULT FALSE,
  `instance` VARCHAR(255) NOT NULL,
  `data` TEXT DEFAULT NULL,
  `updated` TIMESTAMP DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `created` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY (`instance`)
) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;

DROP TABLE IF EXISTS `m2e_e2m_inventory_ebay`;
CREATE TABLE `m2e_e2m_inventory_ebay` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `marketplace_id` SMALLINT NOT NULL,
  `item_id` DECIMAL(20) UNSIGNED NOT NULL,
  `variation` BOOL NOT NULL DEFAULT FALSE,
  `data` TEXT DEFAULT NULL,
  `updated` TIMESTAMP DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `created` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `marketplace_id` (`marketplace_id`),
  INDEX `item_id` (`item_id`),
  INDEX `variation` (`variation`)
) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;

INSERT INTO `m2e_e2m_cron_tasks_in_processing` (`instance`, `data`) VALUES ('Cron_Task_Completed', '{}');
SQL;

$installer->run($sql);
