<?php

//Adding Upop Supporting Currency Codes
$installer = $this;

$installer->run("
	DELETE IGNORE FROM `{$this->getTable('upop/currencyCodes')}`;
");

$data = array(
    array('currency' => 'CAD',
        'currency_code' => '124'
    ),
    array('currency' => 'HKD',
        'currency_code' => '344'
    ),
    array('currency' => 'USD',
        'currency_code' => '840'
    ),
);

$installer->getConnection()->insertMultiple($installer->getTable('upop/currencyCodes'), $data);
