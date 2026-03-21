<?php

if (!defined('_PS_VERSION_'))
    exit;

function upgrade_module_1_2_4($module)
{
    $module->registerHook('displayAdminOrderTabContent');
    $module->registerHook('displayAdminOrderTabLink');
    
    return true;
}
