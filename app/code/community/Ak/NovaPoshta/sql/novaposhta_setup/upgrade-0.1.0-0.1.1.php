<?php
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();

$installer->run("
CREATE TABLE {$this->getTable('novaposhta_city')} (
  `id` int(10) unsigned NOT NULL,
  `name_ru` varchar(100),
  `name_ua` varchar(100),
  `updated_at` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `name_ru` (`name_ru`),
  INDEX `name_ua` (`name_ua`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE {$this->getTable('novaposhta_warehouse')} (
  `id` int(10) unsigned NOT NULL,
  `city_id` int(10) unsigned NOT NULL,
  `address_ru` varchar(200),
  `address_ua` varchar(200),
  `phone` varchar(100),
  `weekday_work_hours` varchar(20),
  `weekday_reseiving_hours` varchar(20),
  `weekday_delivery_hours` varchar(20),
  `saturday_work_hours` varchar(20),
  `saturday_reseiving_hours` varchar(20),
  `saturday_delivery_hours` varchar(20),
  `max_weight_allowed` int(4),
  `longitude` float(10,6),
  `latitude` float(10,6),
  `number_in_city` int(3) unsigned NOT NULL,
  `updated_at` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT FOREIGN KEY (`city_id`) REFERENCES `{$this->getTable('novaposhta_city')}` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

$installer->endSetup();