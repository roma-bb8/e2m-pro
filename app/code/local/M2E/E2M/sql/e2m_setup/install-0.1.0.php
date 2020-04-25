<?php

/* @var Mage_Core_Model_Resource_Setup $installer */
$installer = $this;

//########################################

$sql = <<<SQL
DROP TABLE IF EXISTS `{$installer->getTable('m2e_e2m_ebay_items')}`;
CREATE TABLE IF NOT EXISTS `{$installer->getTable('m2e_e2m_ebay_items')}`
(
    `id`                      INT UNSIGNED         NOT NULL AUTO_INCREMENT,
    `site`                    VARCHAR(5)           NOT NULL, -- alpha-2 code and adding custom two code length 5.
    `ebay_item_id`            DECIMAL(20) UNSIGNED NOT NULL, -- not information.
    `sku`                     VARCHAR(50)  DEFAULT NULL,     -- Max length: 50 on eBay.
    `upc`                     VARCHAR(12)  DEFAULT NULL,     -- UPC consists of 12 numeric on Google.
    `ean`                     VARCHAR(128) DEFAULT NULL,     -- Max length 128 on Google.
    `isbn`                    VARCHAR(13)  DEFAULT NULL,     -- ISBNs were 10 digits in length up to the end of December 2006, but since 1 January 2007 they now always consist of 13 digits on Google.
    `ePID`                    VARCHAR(38)  DEFAULT NULL,     -- Max length: 38 on eBay.
    `mpn`                     VARCHAR(65)  DEFAULT NULL,     -- Max length: 65 on eBay.
    `brand`                   VARCHAR(65)  DEFAULT NULL,     -- Max length: 65 on eBay.
    `title`                   VARCHAR(80)          NOT NULL, -- Max length: 80 on eBay.
    `subtitle`                VARCHAR(55)  DEFAULT NULL,     -- Max length: 55 on eBay.
    `description`             LONGTEXT             NOT NULL, -- Max length: 500000 (some sites may allow more, but the exact number may vary) on eBay.
    `currency`                VARCHAR(3)           NOT NULL, -- ISO 4217 on eBay.
    `start_price`             FLOAT UNSIGNED       NOT NULL, -- double on eBay. [A double-precision 64-bit floating point type]
    `current_price`           FLOAT UNSIGNED       NOT NULL, -- double on eBay. [A double-precision 64-bit floating point type]
    `buy_it_now`              FLOAT UNSIGNED       NOT NULL, -- double on eBay. [A double-precision 64-bit floating point type]
    `quantity`                INT UNSIGNED DEFAULT NULL,     -- int on eBay. [A 32-bit integer value between -2147483648 and 2147483647]
    `condition_id`            INT UNSIGNED DEFAULT NULL,     -- int on eBay. [A 32-bit integer value between -2147483648 and 2147483647]
    `condition_name`          VARCHAR(50)  DEFAULT NULL,     -- Max length: 50 on eBay.
    `condition_description`   TEXT         DEFAULT NULL,     -- Max length: 1000 on eBay.
    `primary_category_id`     VARCHAR(10)  DEFAULT NULL,     -- Max length: 10 on eBay.
    `primary_category_name`   VARCHAR(128) DEFAULT NULL,     -- not information.
    `secondary_category_id`   VARCHAR(10)  DEFAULT NULL,     -- Max length: 10 on eBay.
    `secondary_category_name` VARCHAR(128) DEFAULT NULL,     -- not information.
    `store_category_id`       BIGINT       DEFAULT NULL,     -- long on eBay. [A value between -9223372036854775808 and 9223372036854775807]
    `store_category_name`     VARCHAR(128) DEFAULT NULL,     -- not information.
    `store_category2_id`      BIGINT       DEFAULT NULL,     -- long on eBay. [A value between -9223372036854775808 and 9223372036854775807]
    `store_category2_name`    VARCHAR(128) DEFAULT NULL,     -- not information.
    `weight`                  INT UNSIGNED DEFAULT NULL,     -- decimal on eBay. [A decimal value of arbitrary precision]
    `dispatch_time_max`       INT UNSIGNED DEFAULT NULL,     -- int on eBay. [A 32-bit integer value between -2147483648 and 2147483647]
    `dimensions_depth`        INT UNSIGNED DEFAULT NULL,     -- decimal on eBay. [A decimal value of arbitrary precision]
    `dimensions_length`       INT UNSIGNED DEFAULT NULL,     -- decimal on eBay. [A decimal value of arbitrary precision]
    `dimensions_weight`       INT UNSIGNED DEFAULT NULL,     -- decimal on eBay. [A decimal value of arbitrary precision]
    `updated`                 TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created`                 TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `site` (`site`),
    INDEX `item_id` (`ebay_item_id`)
) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;

DROP TABLE IF EXISTS `{$installer->getTable('m2e_e2m_ebay_item_specifics')}`;
CREATE TABLE IF NOT EXISTS `{$installer->getTable('m2e_e2m_ebay_item_specifics')}`
(
    `id`      INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `item_id` INT UNSIGNED NOT NULL,
    `name`    VARCHAR(65)  NOT NULL, -- Max length: 65 on eBay.
    `value`   VARCHAR(65)  NOT NULL, -- Max length: 65 (but longer for some instance aspects, including 800 for 'California Prop 65 Warning') on eBay.
    `updated` TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`item_id`) REFERENCES `m2e_e2m_ebay_items` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;

DROP TABLE IF EXISTS `{$installer->getTable('m2e_e2m_ebay_item_images')}`;
CREATE TABLE IF NOT EXISTS `{$installer->getTable('m2e_e2m_ebay_item_images')}`
(
    `id`      INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `item_id` INT UNSIGNED NOT NULL,
    `path`    VARCHAR(255) NOT NULL,
    `updated` TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`item_id`) REFERENCES `m2e_e2m_ebay_items` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;

DROP TABLE IF EXISTS `{$installer->getTable('m2e_e2m_ebay_item_variations')}`;
CREATE TABLE `{$installer->getTable('m2e_e2m_ebay_item_variations')}`
(
    `id`          INT UNSIGNED   NOT NULL AUTO_INCREMENT,
    `hash`        VARCHAR(53)    NOT NULL,
    `item_id`     INT UNSIGNED   NOT NULL,
    `sku`         VARCHAR(50)  DEFAULT NULL, -- Max length: 50 on eBay.
    `start_price` FLOAT UNSIGNED NOT NULL,   -- double on eBay. [A double-precision 64-bit floating point type]
    `quantity`    INT UNSIGNED DEFAULT NULL, -- int on eBay. [A 32-bit integer value between -2147483648 and 2147483647]
    `upc`         VARCHAR(12)  DEFAULT NULL, -- UPC consists of 12 numeric on Google.
    `ean`         VARCHAR(128) DEFAULT NULL, -- Max length 128 on Google.
    `isbn`        VARCHAR(13)  DEFAULT NULL, -- ISBNs were 10 digits in length up to the end of December 2006, but since 1 January 2007 they now always consist of 13 digits on Google.
    `ePID`        VARCHAR(38)  DEFAULT NULL, -- Max length: 38 on eBay.
    `updated`     TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created`     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`item_id`) REFERENCES `m2e_e2m_ebay_items` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;

DROP TABLE IF EXISTS `{$installer->getTable('m2e_e2m_ebay_item_variation_specifics')}`;
CREATE TABLE IF NOT EXISTS `{$installer->getTable('m2e_e2m_ebay_item_variation_specifics')}`
(
    `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `item_variation_id` INT UNSIGNED NOT NULL,
    `name`              VARCHAR(65)  NOT NULL, -- Max length: 65 on eBay.
    `value`             VARCHAR(65)  NOT NULL, -- Max length: 65 (but longer for some instance aspects, including 800 for 'California Prop 65 Warning') on eBay.
    `updated`           TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created`           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`item_variation_id`) REFERENCES `m2e_e2m_ebay_item_variations` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;

DROP TABLE IF EXISTS `{$installer->getTable('m2e_e2m_ebay_item_variation_images')}`;
CREATE TABLE IF NOT EXISTS `{$installer->getTable('m2e_e2m_ebay_item_variation_images')}`
(
    `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `item_variation_id` INT UNSIGNED NOT NULL,
    `path`              VARCHAR(255) NOT NULL,
    `updated`           TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created`           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`item_variation_id`) REFERENCES `m2e_e2m_ebay_item_variations` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;

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

INSERT INTO `{$installer->getTable('m2e_e2m_cron_tasks')}`
    (`instance`, `data`)
VALUES
    ('M2E_E2M_Model_Cron_Task_Completed', '{}');
SQL;

$installer->run($sql);
