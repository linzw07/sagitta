<?php

$installer = $this;

$installer->startSetup();

//Create the table for module
//This is optional
$installer->run("

$installer->getConnection()
    ->addColumn($tableName, 'test', array(
        'nullable' => false,
        'length' => 9,
        'type' => Varien_Db_Ddl_Table::TYPE_INTEGER,
        'comment' => 'Test Field'
    )
)

$installer->endSetup();
?>