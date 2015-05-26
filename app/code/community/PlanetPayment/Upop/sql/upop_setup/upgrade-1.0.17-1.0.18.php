<?php
$installer = $this;
$installer->startSetup();

$installer->getConnection()->addColumn($installer->getTable('sales/quote'), 'first_pass',
    'longtext');
$installer->getConnection()->addColumn($installer->getTable('sales/quote'), 'second_pass',
    'longtext');

$installer->endSetup();