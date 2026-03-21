<?php

class AdminAgEredeRequestController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap        = true;
        $this->table            = 'agerede_request';
        $this->className        = 'AgERedeRequest';
        $this->identifier       = 'id_agerede_request';
        $this->list_no_link     = true;
        $this->_defaultOrderBy  = 'id_agerede_request';
        $this->_defaultOrderWay = 'DESC';


        parent::__construct();

        $this->fields_list = [
            'id_agerede_request' => [
                'title' => 'ID',
                'align' => 'center',
                'type' => 'int',
                'class' => 'fixed-width-xs',
            ],
            'http_code' => [
                'title' => 'Código HTTP',
                'type' => 'int',
                'class' => 'fixed-width-md'
            ],
            'method' => [
                'title' => 'Método',
                'type' => 'text',
                'class' => 'fixed-width-md'
            ],
            'endpoint' => [
                'title' => 'URL',
                'type' => 'text'
            ],
            'date_add' => [
                'title' => 'Data',
                'type' => 'datetime'
            ]
        ];

        $this->actions = ['view'];
    }

    public function initContent()
    {
        parent::initContent();

        if (Tools::getIsSet('view' . $this->table)) {
            $request = $this->loadObject();

            $html  = $this->content;

            //contéudo geral da ação VER
            $tpl = $this->createTemplate('view.tpl');
            $tpl->assign(['obj' => $request]);
            $html .= $tpl->fetch();

            $this->content = $html;
            $this->context->smarty->assign(['content' => $html]);

            return;
        }
    }
}