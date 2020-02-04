<?php

/* @var Mage_Core_Model_Resource_Setup $installer */
$installer = $this;
$sql = <<<SQL
DROP TABLE IF EXISTS `{$installer->getTable('m2e_m2i_inventory')}`;
CREATE TABLE `{$installer->getTable('m2e_m2i_inventory')}` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `item_id` DECIMAL(20) UNSIGNED NOT NULL,
  `site` VARCHAR(5) NOT NULL,
  `data` LONGTEXT NOT NULL,
  `update_date` DATETIME DEFAULT NULL ON UPDATE CURRENT_DATE,
  `create_date` DATETIME DEFAULT CURRENT_DATE,
  PRIMARY KEY (`id`),
  INDEX `item_id` (`item_id`),
  INDEX `site` (`site`)
) ENGINE = INNODB CHARACTER SET utf8 COLLATE utf8_general_ci;

DROP TABLE IF EXISTS `{$installer->getTable('m2e_m2i_processing')}`;
CREATE TABLE `{$installer->getTable('m2e_m2i_processing')}` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` DECIMAL(20) UNSIGNED NOT NULL,
  `from` TIMESTAMP NOT NULL,
  `to` TIMESTAMP NOT NULL,
  `item_count` VARCHAR(255) DEFAULT NULL,
  `update_date` TIMESTAMP DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `create_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `name` (`name`)
) ENGINE = INNODB CHARACTER SET utf8 COLLATE utf8_general_ci;
SQL;

$installer->run($sql);
