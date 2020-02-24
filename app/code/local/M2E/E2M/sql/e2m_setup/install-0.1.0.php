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
DROP TABLE IF EXISTS `{$installer->getTable('m2e_e2m_cron_tasks_in_processing')}`;
CREATE TABLE `{$installer->getTable('m2e_e2m_cron_tasks_in_processing')}` (
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

INSERT INTO `{$installer->getTable('m2e_e2m_cron_tasks_in_processing')}`
    (`instance`, `data`)
VALUES
    ('Cron_Task_Completed', '{}');

INSERT INTO `{$installer->getTable('core_config_data')}`
    (`path`, `value`)
VALUES
    ('/m2e/e2m/ebay/account/mode/', 2),
    ('/m2e/e2m/ebay/account/session_id/', NULL),
    ('/m2e/e2m/ebay/account/token/', NULL),
    ('/m2e/e2m/ebay/account/expiration_time/', NULL),
    ('/m2e/e2m/ebay/account/user_id/', NULL),
    ('/m2e/e2m/settings/marketplaces/stores/map/', '[]'),
    ('/m2e/e2m/settings/inventory/product_identifier/', '"SKU"'),
    ('/m2e/e2m/settings/inventory/action_found/', '"IGNORE"'),
    ('/m2e/e2m/settings/product/import/qty/', FALSE),
    ('/m2e/e2m/settings/product/generate_sku/', FALSE),
    ('/m2e/e2m/settings/product/import/image/', FALSE),
    ('/m2e/e2m/settings/product/delete_html/', FALSE),
    ('/m2e/e2m/settings/product/attribute_set/', FALSE),
    ('/m2e/e2m/settings/product/fields_attributes/map/', '[]'),
    ('/m2e/e2m/settings/full/', FALSE),
    ('/m2e/e2m/inventory/items/count/total/', 0),
    ('/m2e/e2m/inventory/items/count/variation/', 0),
    ('/m2e/e2m/inventory/items/count/simple/', 0),
    ('/m2e/e2m/inventory/marketplaces/', '[]'),
    ('/m2e/e2m/inventory/download/', FALSE),
    ('/m2e/e2m/inventory/import/', FALSE);
SQL;

$installer->run($sql);
