<?php

if (!defined('_PS_VERSION_'))
    exit;

function upgrade_module_1_2_2()
{
    unlink(_PS_MODULE_DIR_ . "agerede/controllers/front/threedsReturn.php");
    return true;
}
