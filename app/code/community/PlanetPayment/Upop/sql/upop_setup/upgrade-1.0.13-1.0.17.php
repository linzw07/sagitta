<?php

$installer = $this;
$installer->startSetup();

$installer->getConnection()->addColumn($installer->getTable('sales/order'), 'dbt_status',
    'tinyint DEFAULT NULL');

$installer->endSetup();