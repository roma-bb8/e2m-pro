SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `m2e_e2m_ebay_item_variation_specifics`;
DROP TABLE IF EXISTS `m2e_e2m_ebay_item_variation_images`;
DROP TABLE IF EXISTS `m2e_e2m_ebay_item_variations`;
DROP TABLE IF EXISTS `m2e_e2m_ebay_item_specifics`;
DROP TABLE IF EXISTS `m2e_e2m_ebay_item_images`;
DROP TABLE IF EXISTS `m2e_e2m_ebay_items`;

SET FOREIGN_KEY_CHECKS = 1;

DELETE FROM `core_config_data` WHERE `path` LIKE '%m2e/e2m%';

DELETE FROM `core_resource` WHERE `code` = 'e2m_setup';
