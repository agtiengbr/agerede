<?php

if (!defined('_PS_VERSION_'))
    exit;

function upgrade_module_1_2_1($module)
{
    $tab = new Tab(Tab::getIdFromClassName('AdminAgEredeRequest'));
    $tab->delete();

    $tabModel             = new Tab();
    $tabModel->module     = 'agerede';
    $tabModel->active     = true;
    $tabModel->class_name = 'AdminAgEredeRequest';
    $tabModel->id_parent  = Tab::getIdFromClassName('AdminParentModulesSf');

    foreach (\Language::getLanguages(true) as $lang) {
        $tabModel->name[$lang['id_lang']] = 'E-Rede Requisições';
    }

    $tabModel->add();


    require_once _PS_MODULE_DIR_ . 'agerede/classes/AgERedeRequest.php';

    $obj = new AgERedeRequest;
    $obj->createDatabase();

    return true;
}
