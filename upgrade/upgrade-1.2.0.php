<?php

if (!defined('_PS_VERSION_'))
    exit;

function upgrade_module_1_2_0()
{
    $dbprefix = _DB_PREFIX_;

    $sqls = [
        "ALTER TABLE {$dbprefix}product ADD COLUMN agerede_enable_installments BOOLEAN DEFAULT 1"
    ];

    foreach ($sqls as $sql) {
        try {
            Db::getInstance()->execute($sql);
        } catch (Exception $e){}
    }
    
    return true;
}
