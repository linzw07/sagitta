<?php

$installer = $this;
/* @var $installer Mage_Customer_Model_Entity_Setup */

$installer->startSetup();

// Sales Quote & Order entities
$installer->getConnection()->addColumn($installer->getTable('sales/quote'), 'upop_order_number', 'varchar(32)');
$installer->getConnection()->addColumn($installer->getTable('sales/quote'), 'upop_exchange_rate', 'float(15,6)');
$installer->getConnection()->addColumn($installer->getTable('sales/order'), 'upop_order_number', 'varchar(32)');

// Sales Quote & Order Payment entities
$installer->getConnection()->addColumn($installer->getTable('sales/quote_payment'), 'upop_order_number', 'varchar(32)');
$installer->getConnection()->addColumn($installer->getTable('sales/quote_payment'), 'upop_currency_code', 'varchar(10)');
$installer->getConnection()->addColumn($installer->getTable('sales/order_payment'), 'upop_order_number', 'varchar(32)');
$installer->getConnection()->addColumn($installer->getTable('sales/order_payment'), 'upop_currency_code', 'varchar(10)');
$installer->getConnection()->addColumn($installer->getTable('sales/order_payment'), 'upop_exchange_rate', 'float(15,6)');


$installer->run("

DROP TABLE IF EXISTS `{$this->getTable('upop/currencyCodes')}`;
CREATE TABLE `{$this->getTable('upop/currencyCodes')}` (
  `currency` varchar(10) NOT NULL,
  `currency_code` varchar(5) NOT NULL,
  PRIMARY KEY (`currency`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `{$this->getTable('upop/log')}`;
CREATE TABLE `{$this->getTable('upop/log')}` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `request` text NULL,
  `response` text NULL,
  `create_date` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

$installer->endSetup();
