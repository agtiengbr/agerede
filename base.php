<?php
use PrestaShop\PrestaShop\Core\Payment\PaymentOption;
use PrestaShop\PrestaShop\Adapter\Product\PriceFormatter;

require_once _PS_MODULE_DIR_ . 'agcliente/lib/AgPaymentModule.php';
// Removido autoload da SDK externa (erede-php). Agora usamos mini SDK interna em lib/Rede.
require_once _PS_MODULE_DIR_ . 'agerede/lib/Rede/Environment.php';
require_once _PS_MODULE_DIR_ . 'agerede/lib/Rede/Auth.php';
require_once _PS_MODULE_DIR_ . 'agerede/lib/Rede/Client.php';
require_once _PS_MODULE_DIR_ . 'agerede/lib/Rede/TransactionBuilder.php';

/**
 * O arquivo base é utilizado porque o arquivo de nome <nomedomodulo>.php não pode ser criptografado.
 * Dessa forma, todo o conteúdo que deveria estar em <nomedomodulo>.php é inserido no arquivo base.php, que 
 * será criptografado normalmente.
 */
class BaseAgERede extends AgPaymentModule
{
    protected $hooks = [
        'displayHeader',
        'displayBackOfficeHeader',
        'paymentOptions',
        'displayPaymentTop',
        'payment',
        'orderConfirmation',
        'displayOrderDetail',
        'displayAdminOrderContentOrder',
        'displayAdminOrderTabOrder',
        'displayAdminOrderSide',
        'displayAdminProductsExtra',

        //ps 1.7.7
        'displayAdminOrderTabContent',
        'displayAdminOrderTabLink'
    ];

    //menus do administrativo
    protected $main_tab = 'AdminParentModulesSf';
    protected $main_tab_ps16 = 'AdminParentModules';
   
    protected $tabs = array(
        array(
            "name"      => "E-Rede Transações",
            "className" => "AdminAgERedeTransaction",
            "active"    => 0
        ),
        array(
            "name"      => "E-Rede Requisições",
            "className" => "AdminAgEredeRequest",
            "active"    => 1
        )
    );

    public function __construct()
    {
        $this->name     = 'agerede';
        $this->tab      = 'payments_gateways';
        $this->version  = '2.0.0';
        $this->author   = 'AGTI';

        $this->bootstrap = true;
        
        parent::__construct();

        $this->displayName = 'E-Rede Transparente';
        $this->description = 'Integra a sua loja PrestaShop com o intermediador de pagamentos E-Rede.';

        $this->loadMappings();
    }

    /**
     *  Função que salva as configurações padrão do módulo na (re)instalação do mesmo
     *
     * @return null
     */
    public function resetConfig()
    {
        Configuration::updateValue('AGEREDE_CREDIT_CARD_TEXT', 'Pagar no Cartão de Crédito');
        Configuration::updateValue('AGEREDE_DEBIT_CARD_TEXT', 'Pagar no Cartão de Débito');

        Configuration::updateValue('AGEREDE_CREDIT_CARD_ACTIVE',    1);
        Configuration::updateValue('AGEREDE_DEBIT_CARD_ACTIVE',    1);
        Configuration::updateValue('AGEREDE_MAX_INSTALLMENTS', 12);
        Configuration::updateValue('AGEREDE_ANTIFRAUD_ENABLED', 1);
        Configuration::updateValue('AGEREDE_ANTIFRAUD_ENABLED_CREDIT', 1);
        Configuration::updateValue('AGEREDE_REFUNDED', 0);

        Configuration::updateValue('AGEREDE_SANDBOX_ENABLED',        1);
        Configuration::updateValue('AGEREDE_MAX_INSTALLMENTS',      12);

        if (Module::isInstalled('agcustomers') && Module::isEnabled('agcustomers')) {
            $this->cpf_mapping->mapsTo('cpf');
            $this->cnpj_mapping->mapsTo('cnpj');
            $this->social_name_mapping->mapsTo('company_name');
            $this->address_number_mapping->mapsTo('number');
        } elseif (Module::isInstalled('djtalbrazilianregister') && Module::isEnabled('djtalbrazilianregister')) {
            $this->cpf_mapping->mapsTo('djtalbrazilianregister');
            $this->cnpj_mapping->mapsTo('djtalbrazilianregister');
            $this->social_name_mapping->mapsTo('');
            $this->address_number_mapping->mapsTo('djtalbrazilianregister');
        }
    }

    public function install()
    {
        Db::getInstance()->execute('ALTER TABLE ' ._DB_PREFIX_.'product ADD COLUMN agerede_enable_installments BOOLEAN DEFAULT 1');
    	return parent::install();
    }

    public function uninstall()
    {
        Db::getInstance()->execute('ALTER TABLE ' ._DB_PREFIX_.'product DROP COLUMN agerede_enable_installments');
        return parent::uninstall();
    }

    /**
     *  Processa o formulário de configuração do módulo, salvando as configurações informadas pelo usuário.
     *
     * @return string HTML do formulário de configuração
     */
    public function getContent()
    {
        if (Tools::getIsSet('cancelTransaction') && Tools::getValue('cancelTransaction') == 1) {
            $this->cancelTransaction();
            exit();
        }

        if (Tools::getIsSet('enableAgrede') && Tools::getValue('enableAgrede') == 1) {
            $this->enableInstallment(Tools::getValue('enableValue'), Tools::getValue('id'));
            exit();
        }

        if (Tools::getIsSet('agerede-save')) {

            Configuration::updateValue('AGEREDE_SANDBOX_PV',      Tools::getValue('agerede_sandbox_pv'));
            Configuration::updateValue('AGEREDE_SANDBOX_TOKEN',   Tools::getValue('agerede_sandbox_token'));
            Configuration::updateValue('AGEREDE_SANDBOX_ENABLED', Tools::getValue('agerede_sandbox_enabled'));
            // Invalida token OAuth cacheado quando credenciais de sandbox mudam
            Configuration::updateValue('AGEREDE_OAUTH_ACCESS_TOKEN', '');
            Configuration::updateValue('AGEREDE_OAUTH_TOKEN_TS', '0');

            Configuration::updateValue('AGEREDE_PV',    Tools::getValue('agerede_pv'));
            Configuration::updateValue('AGEREDE_TOKEN', Tools::getValue('agerede_token'));
            // Invalida token OAuth cacheado quando credenciais mudam
            Configuration::updateValue('AGEREDE_OAUTH_ACCESS_TOKEN', '');
            Configuration::updateValue('AGEREDE_OAUTH_TOKEN_TS', '0');

            Configuration::updateValue('AGEREDE_CREDIT_CARD_TEXT', Tools::getValue('agerede_credit_card_text'));
            Configuration::updateValue('AGEREDE_CREDIT_CARD_ACTIVE', Tools::getValue('agerede_credit_card_active'));
            Configuration::updateValue('AGEREDE_CREDIT_CARD_MIN_INSTALLMENT_VALUE', Tools::getValue('agerede_credit_card_min_installment_value'));
            Configuration::updateValue('AGEREDE_MAX_INSTALLMENTS', Tools::getValue('agerede_max_installments'));

            for ($i=0; $i<12; $i++) {
                Configuration::updateValue("AGEREDE_CREDIT_CARD_INTEREST_RATE_$i", Tools::getValue("agerede_credit_card_interest_rate_$i"));
            }

            Configuration::updateValue('AGEREDE_ANTIFRAUD_ENABLED', Tools::getValue('agerede_antifraud_enabled'));

            Configuration::updateValue('AGEREDE_DEBIT_CARD_TEXT', Tools::getValue('agerede_debit_card_text'));
            Configuration::updateValue('AGEREDE_DEBIT_CARD_ACTIVE', Tools::getValue('agerede_debit_card_active'));
            Configuration::updateValue('AGEREDE_DEBIT_CARD_MIN_VALUE', Tools::getValue('agerede_debit_card_min_value'));

            $this->getCpfMapping()->mapsTo(Tools::getValue('agerede_cpf'));
            $this->getCnpjMapping()->mapsTo(Tools::getValue('agerede_cnpj'));
            $this->getSocialNameMapping()->mapsTo(Tools::getValue('agerede_social_name'));
            $this->getAddressNumberMapping()->mapsTo(Tools::getValue('agerede_address_number'));
        }

        return $this->renderConfigForm();
    }

    public function renderAuthForm()
    {
        if (Tools::isSubmit('agerede-config-auth')) {
            Configuration::updateValue('AGEREDE_SANDBOX_TOKEN', Tools::getValue('agerede_sandbox_token'));
            Configuration::updateValue('AGEREDE_SANDBOX_PV', Tools::getValue('agerede_sandbox_pv'));
            Configuration::updateValue('AGEREDE_SANDBOX_ENABLED', Tools::getValue('agerede_sandbox_enabled'));
            // Invalida token OAuth cacheado quando credenciais de sandbox mudam
            Configuration::updateValue('AGEREDE_OAUTH_ACCESS_TOKEN', '');
            Configuration::updateValue('AGEREDE_OAUTH_TOKEN_TS', '0');

            Configuration::updateValue('AGEREDE_TOKEN', Tools::getValue('agerede_token'));
            Configuration::updateValue('AGEREDE_PV', Tools::getValue('agerede_pv'));
            // Invalida token OAuth cacheado quando credenciais mudam
            Configuration::updateValue('AGEREDE_OAUTH_ACCESS_TOKEN', '');
            Configuration::updateValue('AGEREDE_OAUTH_TOKEN_TS', '0');
        }

        $helper = $this->generateDefaultHelperForm();
        $panels = [];

        $panels[0]['form'] = [
                'legend' => [
                    'title' => 'Produção'
                ],
                'input' => [
                    [
                        'name' => 'agerede_pv',
                        'type' => 'text',
                        'label' => 'Ponto de Venda',
                        'col' => 1
                    ],
                    [
                        'name' => 'agerede_token',
                        'type'=> 'text',
                        'label' => 'Token',
                        'col' => 2
                    ]
                ],
                'submit' => [
                    'name' => 'agerede-config-auth',
                    'title' => 'Salvar'
                ]
            ];


        $panels[1]['form'] = [
            'legend' => [
                'title' => 'Sandbox'
            ],
            'input' => [
                [
                    'type'   => 'switch',
                    'label'  => 'Ativar Sandbox',
                    'name'   => 'agerede_sandbox_enabled',
                    'id'     => 'agerede_sandbox_enabled',
                    'values' => array(
                        array(
                            'id'    => 'agerede_sandbox_enabled_on',
                            'value' => 1,
                            'label' => 'Sim',
                        ),
                        array(
                            'id'    => 'agerede_sandbox_enabled_off',
                            'value' => 0,
                            'label' => 'Não',
                        ),
                    ),
                    'readonly' => false
                ]
            ],
            'submit' => [
                'name' => 'agerede-config-auth',
                'title' => 'Salvar'
            ]
        ];


        $helper->fields_value['agerede_pv'] = Configuration::get('AGEREDE_PV');
        $helper->fields_value['agerede_token'] = Configuration::get('AGEREDE_TOKEN');

        $helper->fields_value['agerede_sandbox_pv'] = Configuration::get('AGEREDE_SANDBOX_PV');
        $helper->fields_value['agerede_sandbox_token'] = Configuration::get('AGEREDE_SANDBOX_TOKEN');
        $helper->fields_value['agerede_sandbox_enabled'] = Configuration::get('AGEREDE_SANDBOX_ENABLED');

        return $helper->generateForm($panels);
    }


    public function renderCreditCardForm()
    {
        if (Tools::isSubmit('agerede-config-credit-card')) {
            Configuration::updateValue('AGEREDE_CREDIT_CARD_ACTIVE', Tools::getValue('agerede_credit_card_enabled'));
            Configuration::updateValue('AGEREDE_CREDIT_CARD_TEXT', Tools::getValue('agerede_credit_card_text'));
            Configuration::updateValue('AGEREDE_CREDIT_CARD_MIN_INSTALLMENT_VALUE', Tools::getValue('agerede_credit_card_min_installment_value'));
            Configuration::updateValue('AGEREDE_MAX_INSTALLMENTS', Tools::getValue('agerede_max_installments'));

            if(Tools::getValue('agerede_credit_card_3ds') == 1){
                Configuration::updateValue('AGEREDE_CREDIT_CARD_3DS', 1);
                Configuration::updateValue('AGEREDE_ANTIFRAUD_ENABLED_CREDIT', 0);
            }else{
                Configuration::updateValue('AGEREDE_CREDIT_CARD_3DS', Tools::getValue('agerede_credit_card_3ds'));
                Configuration::updateValue('AGEREDE_ANTIFRAUD_ENABLED_CREDIT', Tools::getValue('antifraud_enabled'));
            }
            
            for ($i=0; $i<12; $i++) {
                Configuration::updateValue('AGEREDE_CREDIT_CARD_INTEREST_RATE_' . $i, Tools::getValue('agerede_credit_card_interest_rate_' . $i));
            }

            Tools::redirectAdmin('index.php?controller=AdminModules&configure=agerede&token=' . Tools::getAdminTokenLite('AdminModules'));
        }

        $helper = $this->generateDefaultHelperForm();
        $panels = [];


        $max_installments_select = [];
        for ($i=0; $i<12; $i++) {
            $max_installments_select[] = [
                'id' => $i+1,
                'name' => $i+1
            ];
        }

        $panels[0]['form'] = [
            'legend' => [
                'title' => 'Cartão de Crédito'
            ],
            'input' => [
                [
                    'type'   => 'switch',
                    'label'  => 'Ativar Cartão de Crédito',
                    'name'   => 'agerede_credit_card_enabled',
                    'id'     => 'agerede_credit_card_enabled',
                    'values' => array(
                        array(
                            'id'    => 'agerede_credit_card_on',
                            'value' => 1,
                            'label' => 'Sim',
                        ),
                        array(
                            'id'    => 'agerede_credit_card_off',
                            'value' => 0,
                            'label' => 'Não',
                        ),
                    ),
                ],
                [
                    'type' => 'text',
                    'label'=> 'Texto a ser Exibido no Checkout',
                    'name' => 'agerede_credit_card_text',
                    'col'  => 3
                ],
                [
                    'type' => 'text',
                    'label'=> 'Valor Mínimo da Parcela',
                    'name' => 'agerede_credit_card_min_installment_value',
                    'col'  => 1,
                    'prefix' => 'R$'
                ],
                [
                    'type' => 'select',
                    'label'=> 'Máximo de Parcelas',
                    'name' => 'agerede_max_installments',
                    'col'  => 1,
                    'options' => [
                        'name' => 'name',
                        'id' => 'id',
                        'query' => $max_installments_select
                    ]
                ],
                [
                    'type'   => 'switch',
                    'label'  => 'Autenticação 3ds',
                    'hint'   => 'Aumenta a segurança de suas transações',
                    'name'   => 'agerede_credit_card_3ds',
                    'id'     => 'agerede_credit_card_3ds',
                    'values' => array(
                        array(
                            'id'    => 'agerede_credit_card_3ds_on',
                            'value' => 1,
                            'label' => 'Sim',
                        ),
                        array(
                            'id'    => 'agerede_credit_card_3ds_off',
                            'value' => 0,
                            'label' => 'Não',
                        ),
                    ),
                ],
            ],
            'submit' => [
                'name' => 'agerede-config-credit-card',
                'title' => 'Salvar'
            ]
        ];

        for ($i=0; $i<12; $i++) {
            $panels[0]['form']['input'][] = [
                'type' => 'text',
                'label' => 'Tarifa de Parcelamento',
                'prefix' => ($i+1) . 'x',
                'suffix' => '%',
                'col' => 2,
                'name' => 'agerede_credit_card_interest_rate_' . $i
            ];
        }

        $helper->fields_value['agerede_credit_card_enabled'] = Configuration::get('AGEREDE_CREDIT_CARD_ACTIVE');
        $helper->fields_value['agerede_credit_card_text'] = Configuration::get('AGEREDE_CREDIT_CARD_TEXT');
        $helper->fields_value['agerede_credit_card_min_installment_value'] = Configuration::get('AGEREDE_CREDIT_CARD_MIN_INSTALLMENT_VALUE');
        $helper->fields_value['agerede_max_installments'] = Configuration::get('AGEREDE_MAX_INSTALLMENTS');
        $helper->fields_value['agerede_credit_card_3ds'] = Configuration::get('AGEREDE_CREDIT_CARD_3DS');

        for ($i=0; $i<12; $i++) {
            $helper->fields_value['agerede_credit_card_interest_rate_' . $i] = Configuration::get('AGEREDE_CREDIT_CARD_INTEREST_RATE_' . $i);
        }

        return $helper->generateForm($panels);
    }

    public function renderDebitCardForm()
    {
        if (Tools::isSubmit('agerede-config-debit-card')) {
            Configuration::updateValue('AGEREDE_DEBIT_CARD_ACTIVE', Tools::getValue('agerede_debit_card_enabled'));
            Configuration::updateValue('AGEREDE_DEBIT_CARD_TEXT', Tools::getValue('agerede_debit_card_text'));
            Configuration::updateValue('AGEREDE_DEBIT_CARD_DISCOUNT', Tools::getValue('agerede_debit_card_discount'));
            Configuration::updateValue('AGEREDE_DEBIT_CARD_MIN_VALUE', Tools::getValue('agerede_debit_card_min_value'));
            Configuration::updateValue('AGEREDE_DEBIT_CARD_3DS', Tools::getValue('agerede_debit_card_3ds'));
        }

        $helper = $this->generateDefaultHelperForm();
        $panels = [];


        $max_installments_select = [];
        for ($i=0; $i<12; $i++) {
            $max_installments_select[] = [
                'id' => $i+1,
                'name' => $i+1
            ];
        }

        $panels[0]['form'] = [
            'legend' => [
                'title' => 'Cartão de Débito'
            ],
            'input' => [
                [
                    'type'   => 'switch',
                    'label'  => 'Ativar Cartão de Débito',
                    'name'   => 'agerede_debit_card_enabled',
                    'id'     => 'agerede_debit_card_enabled',
                    'values' => array(
                        array(
                            'id'    => 'agerede_debit_card_on',
                            'value' => 1,
                            'label' => 'Sim',
                        ),
                        array(
                            'id'    => 'agerede_debit_card_off',
                            'value' => 0,
                            'label' => 'Não',
                        ),
                    ),
                ],
                [
                    'type' => 'text',
                    'label'=> 'Texto a ser Exibido no Checkout',
                    'name' => 'agerede_debit_card_text',
                    'col'  => 3
                ],
                [
                    'type' => 'text',
                    'label'=> 'Desconto',
                    'name' => 'agerede_debit_card_discount',
                    'col'  => 1,
                    'prefix' => '%'
                ],
                [
                    'type' => 'text',
                    'label'=> 'Valor Mínimo da Parcela',
                    'name' => 'agerede_debit_card_min_value',
                    'col'  => 1,
                    'prefix' => 'R$'
                ],
                [
                    'type'   => 'switch',
                    'label'  => 'Ativar Autenticação 3ds',
                    'name'   => 'agerede_debit_card_3ds',
                    'hint'   => 'Aumenta a segurança de suas transações',
                    'desc'   => 'Se o 3DS for desativado algumas transações no cartão de débito podem ser recusadas.',
                    'id'     => 'agerede_debit_card_3ds',
                    'values' => array(
                        array(
                            'id'    => 'agerede_debit_card_3ds',
                            'value' => 1,
                            'label' => 'Sim',
                        ),
                        array(
                            'id'    => 'agerede_debit_card_3ds',
                            'value' => 0,
                            'label' => 'Não',
                        ),
                    ),
                ],
            ],
            'submit' => [
                'name' => 'agerede-config-debit-card',
                'title' => 'Salvar'
            ]
        ];

        $helper->fields_value['agerede_debit_card_enabled'] = Configuration::get('AGEREDE_DEBIT_CARD_ACTIVE');
        $helper->fields_value['agerede_debit_card_text'] = Configuration::get('AGEREDE_DEBIT_CARD_TEXT');
        $helper->fields_value['agerede_debit_card_discount'] = Configuration::get('AGEREDE_DEBIT_CARD_DISCOUNT');
        $helper->fields_value['agerede_debit_card_min_value'] = Configuration::get('AGEREDE_DEBIT_CARD_MIN_VALUE');
        $helper->fields_value['agerede_debit_card_3ds'] = Configuration::get('AGEREDE_DEBIT_CARD_3DS');

        return $helper->generateForm($panels);
    }

    public function renderMappingsForm()
    {
        if (Tools::isSubmit('agerede-config-mappings')) {
            $this->getCpfMapping()->mapsTo(Tools::getValue('cpf_mapping'));
            $this->getCnpjMapping()->mapsTo(Tools::getValue('cnpj_mapping'));
            $this->getSocialNameMapping()->mapsTo(Tools::getValue('company_name_mapping'));
            $this->getAddressNumberMapping()->mapsTo(Tools::getValue('address_number_mapping'));
            Configuration::updateValue('AGEREDE_ANTIFRAUD_ENABLED', Tools::getValue('antifraud_enabled'));
            Configuration::updateValue('AGEREDE_REFUNDED', Tools::getValue('status_mapping'));
            Tools::redirectAdmin('index.php?controller=AdminModules&configure=agerede&token=' . Tools::getAdminTokenLite('AdminModules'));
        }


        $cpf_fields = [];
        foreach ($this->getCpfMapping()->getColumnsFromTable() as $key => $column) {
            $cpf_fields[] = [
                'id' => $key,
                'name' => $column
            ];
        }

        $cnpj_fields = [];
        foreach ($this->getCnpjMapping()->getColumnsFromTable() as $key => $column) {
            $cnpj_fields[] = [
                'id' => $key,
                'name' => $column
            ];
        }

        $company_fields = [];
        foreach ($this->getSocialNameMapping()->getColumnsFromTable() as $key => $column) {
            $company_fields[] = [
                'id' => $key,
                'name' => $column
            ];
        }

        $number_fields = [];
        foreach ($this->getAddressNumberMapping()->getColumnsFromTable() as $key => $column) {
            $number_fields[] = [
                'id' => $key,
                'name' => $column
            ];
        }


        $panels = [];
        $panels[0]['form'] = [
            'legend' => [
                'title' => 'Mapeamento de Campos',
                'icon' => 'icon-person'
            ],
            'input'  => [
                [
                    'label' => 'Ativar Antifraude',
                    'name' => 'antifraud_enabled',
                    'type' => 'switch',
                    'id'     => 'agerede_antifraud_enabled',
                    'values' => array(
                        array(
                            'id'    => 'agerede_antifraud_enabled_on',
                            'value' => 1,
                            'label' => 'Sim',
                        ),
                        array(
                            'id'    => 'agerede_antifraud_enabled_off',
                            'value' => 0,
                            'label' => 'Não',
                        ),
                    ),
                ],
                [
                    'label' => 'CPF',
                    'name' => 'cpf_mapping',
                    'type' => 'select',
                    'col' => 4,
                    'options' => [
                        'id' => 'id',
                        'name' => 'name',
                        'query' => $cpf_fields
                    ]
                ],
                [
                    'label' => 'CNPJ',
                    'name' => 'cnpj_mapping',
                    'type' => 'select',
                    'col' => 4,
                    'options' => [
                        'id' => 'id',
                        'name' => 'name',
                        'query' => $cnpj_fields
                    ]
                ],
                [
                    'label' => 'Razão Social',
                    'name' => 'company_name_mapping',
                    'type' => 'select',
                    'col' => 4,
                    'options' => [
                        'id' => 'id',
                        'name' => 'name',
                        'query' => $company_fields
                    ]
                ],
                [
                    'label' => 'Número do Endereço',
                    'name' => 'address_number_mapping',
                    'type' => 'select',
                    'col' => 4,
                    'options' => [
                        'id' => 'id',
                        'name' => 'name',
                        'query' => $number_fields
                    ]
                ],
            ],
            'submit' => [
                'name' => 'agerede-config-mappings',
                'title' => 'Salvar'
            ]
        ];

        $helper = $this->generateDefaultHelperForm();

        $helper->fields_value['cpf_mapping'] = $this->getCpfMapping()->getMappedfield();
        $helper->fields_value['cnpj_mapping'] = $this->getCnpjMapping()->getMappedfield();
        $helper->fields_value['company_name_mapping'] = $this->getSocialNameMapping()->getMappedfield();
        $helper->fields_value['address_number_mapping'] = $this->getAddressNumberMapping()->getMappedfield();
        $helper->fields_value['antifraud_enabled'] = Configuration::get('AGEREDE_ANTIFRAUD_ENABLED');

        return $helper->generateForm($panels);
    }

    /**
     *  Gera o HTML do formulário de configuração do módulo
     *
     * @return string HTML do formulário de configuração
     */
    public function renderConfigForm()
    {
        agcliente::prepareConfigHelpTab($this->name);

        $auth_tab = $this->renderAuthForm();
        $credit_card_tab = $this->renderCreditCardForm();
        $debit_card_tab = $this->renderDebitCardForm();
        $mappings_tab = $this->renderMappingsForm();

        $this->context->smarty->assign([
            'tabs' => [
                'auth' => $auth_tab,
                'credit_card' => $credit_card_tab,
                'debit_card' => $debit_card_tab,
                'mappings' => $mappings_tab
            ],
            'modules_path' => _PS_MODULE_DIR_,
        ]);

        $html = $this->display(_PS_MODULE_DIR_ . $this->name, 'views/templates/admin/configuration.tpl');
        return $html;
    }

    /************************ MAPEAMENTOS *************************/
    /**
     * Carrega os mapeamentos utilizados pelo módulo, a saber: CPF, CNPJ, Razão Social e Número do endereço
     *
     * @return null
     */
    public function loadMappings()
    {
        $this->cpf_mapping = new AgColumnMapping();
        $this->cpf_mapping->setData(array(
            'table_name' => 'customer',
            'configuration_name' => 'agerede_cpf'
        ));
        $this->cpf_mapping->addColumn('djtalbrazilianregister', 'Módulo de Cadastro Brasileiro');

        $this->cnpj_mapping = new AgColumnMapping();
        $this->cnpj_mapping->setData(array(
            'table_name' => 'customer',
            'configuration_name' => 'agerede_cnpj'
        ));
        $this->cnpj_mapping->addColumn('djtalbrazilianregister', 'Módulo de Cadastro Brasileiro');

        $this->social_name_mapping = new AgColumnMapping();
        $this->social_name_mapping->setData(array(
            'table_name' => 'customer',
            'configuration_name' => 'agerede_social_name'
        ));

        $this->address_number_mapping = new AgColumnMapping();
        $this->address_number_mapping->setData(array(
            'table_name' => 'address',
            'configuration_name' => 'agerede_address_number_mapping'
        ));
    }

    public function getCpfMapping()
    {
        return $this->cpf_mapping;
    }

    public function getCnpjMapping()
    {
        return $this->cnpj_mapping;
    }

    public function getSocialNameMapping()
    {
        return $this->social_name_mapping;
    }

    public function getAddressNumberMapping()
    {
        return $this->address_number_mapping;
    }

    public function getCustomerData(Customer $customer)
    {
        $document = AgColumnMapping::getCustomerDocument(
            $this->getCpfMapping(),
            $this->getCnpjMapping(),
            $this->getSocialNameMapping(),
            $customer
        );

        if ($document['cnpj'] && @$document['company_name']) {
            return ['name' => $document['company_name'], 'cnpj' => $document['cnpj'], 'cpf' => $document['cpf']];
        }

        return ['name' => $document['name'], 'cpf' => $document['cpf']];
    }

    private function resolveConsumerCpf(Customer $customer, Address $invoiceAddress, array $customerData): ?string
    {
        $candidates = [
            $customerData['cpf'] ?? null,
            $invoiceAddress->dni ?? null,
            $customer->dni ?? null,
            Tools::getValue('agerede_cpf'),
        ];

        foreach ($candidates as $candidate) {
            if (!$candidate) {
                continue;
            }
            $digits = preg_replace('/[^0-9]/', '', (string) $candidate);
            if ($digits !== '') {
                return $digits;
            }
        }

        return null;
    }

    public function validatePayment(Cart $cart, Customer $customer)
    {
        return [];
    }

    /**
     * Calcula o valor a ser pago para cada opção de parcelamento
     *
     * @param float $options [installment_value_min] - Valor mínimo da parcela
     * @param float $options [value] - Valor do pagamento à vista
     * @param float $options [interest_rate] - Taxa de juros
     *
     * @return array[float] valor a ser pago
     */
    public function calcInstallments($options)
    {
        $return = [];

        $max_installments = Configuration::get('AGEREDE_MAX_INSTALLMENTS');
        for ($i=0; $i<$max_installments; $i++) {
            $options['qtt_installments'] = $i+1;

            $total_value = (100 + (int)$options['interest_rate'][$i])/100 * $options['value'];
            $installment_value = $total_value / ($i+1);

            if (Tools::convertPrice($installment_value, null, false) < $options['installment_value_min'] && $i) {
                break;
            }

            $return[] = array(
                'total' => $total_value,
                'installment_value' => $installment_value
            );
        }

        return $return;
    }

    public function generateCreditCardForm()
    {
        if (!$this->active) {
            return;
        }

        $total = $this->context->cart->getOrderTotal();
        $interest_rate = [];

        for ($i=0; $i<12; $i++) {
            $interest_rate[] = Configuration::get("AGEREDE_CREDIT_CARD_INTEREST_RATE_$i");
        }

        $options = array(
            'value'                 => $total,
            'installment_value_min' => Configuration::get('AGEREDE_CREDIT_CARD_MIN_INSTALLMENT_VALUE'),
            'interest_rate'         => $interest_rate,
        );

        $installments = $this->calcInstallments($options);

        $priceFormatter = new PriceFormatter();
        $currency = Currency::getCurrencyInstance((int) $this->context->currency->id);
        foreach ($installments as $i => $installment) {
            $installments[$i]['installment_value'] = $priceFormatter->format((float)$installments[$i]['installment_value'], $currency);
            $installments[$i]['total'] = $priceFormatter->format((float)$installments[$i]['total'], $currency);
        }

        $products = Context::getContext()->cart->getProducts();
        foreach ($products as $product){
            $sql = Db::getInstance()->getRow("SELECT agerede_enable_installments FROM " . _DB_PREFIX_ . "product WHERE id_product = {$product['id_product']}");

            if($sql['agerede_enable_installments'] == 0){
               $enable_installments = 0;
               break;
            }
        }

        $this->context->smarty->assign(array(
            'total' => $priceFormatter->format((float)$total, $currency),
            'installments' => $installments,
            'enable_installments' => isset($enable_installments) ? $enable_installments : 1,
            'form_action' => $this->context->link->getModuleLink($this->name, 'validation')
        ));

        return $this->display($this->_path, 'views/templates/front/credit_card.ps17.tpl');
    }


	    public function generateDebitCardForm()
	    {
	        if (!$this->active) {
	            return;
	        }

	        $total = $this->context->cart->getOrderTotal();# + $this->context->cart->getOrderTotal(Cart::ONLY_DISCOUNTS);
	        $priceFormatter = new PriceFormatter();
	        $currency = Currency::getCurrencyInstance((int) $this->context->currency->id);
	        
	        $discount = (float) Configuration::get('AGEREDE_DEBIT_CARD_DISCOUNT');
	        $total_with_discount = $total * (100 - $discount) / 100;

	        $this->context->smarty->assign(array(
	            'total' => $priceFormatter->format((float)$total, $currency),
	            'discount' => $discount,
	            'total_with_discount' => $total_with_discount,
	            'total_with_discount_formatted' => $priceFormatter->format((float)$total_with_discount, $currency),
	            'form_action' => $this->context->link->getModuleLink($this->name, 'validation')
	        ));

        return $this->display($this->_path, 'views/templates/front/debit_card.ps17.tpl');
    }

    //-------------------------- API e SDK --------------------------------
    public function getEnvironment()
    {
        // Mantido para compatibilidade com código legado: retorna string base URL
        if (Configuration::get('AGEREDE_SANDBOX_ENABLED')) {
            return 'https://sandbox-erede.useredecloud.com.br';
        }
        return 'https://api.userede.com.br/erede';
    }


    public function getAgRedeClient()
    {
        $sandboxEnabled = (bool) Configuration::get('AGEREDE_SANDBOX_ENABLED');

        if ($sandboxEnabled) {
            $pv    = Configuration::get('AGEREDE_SANDBOX_PV');
            $token = Configuration::get('AGEREDE_SANDBOX_TOKEN');
            $env   = AgRedeEnvironment::sandbox($pv . $this->context->cart->id);
        } else {
            $pv    = Configuration::get('AGEREDE_PV');
            $token = Configuration::get('AGEREDE_TOKEN');
            $env   = AgRedeEnvironment::production($pv . $this->context->cart->id);
        }
        $auth = new AgRedeAuth($pv, $token, $sandboxEnabled);
        return new AgRedeClient($auth, $env);
    }

    public function getPhoneFromAddress(Address $address)
    {
        $phone = $address->phone_mobile ? $address->phone_mobile : $address->phone;
        $phone_numbers = preg_replace('/[^0-9]/', '', $phone);

        $ddd = 0 . Tools::substr($phone_numbers, 0, 2);
        $phone_number = Tools::substr($phone_numbers, 2);

        // Estrutura simplificada: retornamos objeto stdClass com getters simulados
        $phoneObj = new stdClass();
        $phoneObj->areaCode = $ddd;
        $phoneObj->number = $phone_number;
        $phoneObj->getAreaCode = function() use ($ddd) { return $ddd; };
        $phoneObj->getNumber = function() use ($phone_number) { return $phone_number; };
        return $phoneObj;
    }

    public function generateTransactionForCart(Cart $cart, $use_antifraud = true, $threeds=true, $payment_mode=null)
    {
        $value = $cart->getOrderTotal();

        if ($payment_mode == 0) {
            $discount = (float) Configuration::get('AGEREDE_DEBIT_CARD_DISCOUNT');
            $value = $value * (100 - $discount) / 100;
        }
        
        $reference = 'Cart' . $cart->id;

        $builder = new AgRedeTransactionBuilder($value, $reference);
        // Sempre enviar o bloco de antifraude no body, conforme especificado
        $antifraud = $this->buildAntifraudArrayForCart($cart);
        $envConsumer = isset($antifraud['environment']['consumer']) ? $antifraud['environment']['consumer'] : null;
        $builder->antifraud($antifraud['consumer'], $antifraud['billing'], $antifraud['shipping'], $antifraud['items'], $envConsumer);
        if ($threeds) {
            $builder->threeDSecure(
                $this->context->link->getModuleLink($this->name, 'threedsreturn', ['r' => 0, 'payment_mode' => $payment_mode]),
                $this->context->link->getModuleLink($this->name, 'threedsreturn', ['r' => 1, 'payment_mode' => $payment_mode])
            );
        }
        return $builder;
    }

    public function buildAntifraudArrayForCart(Cart $cart)
    {
        $ps_customer = new Customer($cart->id_customer);
        $customer_data = $this->getCustomerData($ps_customer);

        $invoice_address = new Address($cart->id_address_invoice);

        $phone = $invoice_address->phone_mobile ? $invoice_address->phone_mobile : $invoice_address->phone;
        $phone_numbers = preg_replace('/[^0-9]/', '', $phone);
        $ddd = str_pad(Tools::substr($phone_numbers, 0, 2), 3, '0', STR_PAD_LEFT);
        $phone_number = Tools::substr($phone_numbers, 2);

        $consumerCpf = $this->resolveConsumerCpf($ps_customer, $invoice_address, $customer_data);
        if (!$consumerCpf) {
            Logger::addLog('agerede - CPF não localizado para o cliente ' . $ps_customer->email . ' durante a análise antifraude.', 3, null, 'Customer', (int)$ps_customer->id, true);
            throw new RuntimeException('Não foi possível localizar o CPF do cliente. Atualize o cadastro e tente novamente.');
        }

        $consumer = [
            'name'  => $ps_customer->firstname . ' ' . $ps_customer->lastname,
            'email' => $ps_customer->email,
            'cpf'   => $consumerCpf,
            'phone' => [
                'ddd' => $ddd,
                'number' => $phone_number,
                'type' => 1,
            ]
        ];

        if (@$customer_data['cnpj']) {
            $consumer['cnpj'] = preg_replace('/[^0-9]/', '', $customer_data['cnpj']);
        }

        $mapping_number = $this->getAddressNumberMapping();
        $invoice_number = 's/n';
        if ($mapping_number->isMappingEnabled()) {
            $invoice_number = @$invoice_address->{$mapping_number->getMappedField()};
        }

        $state = new State($invoice_address->id_state);
        $billing = [
            'addresseeName' => $invoice_address->firstname . ' ' . $invoice_address->lastname,
            'address' => $invoice_address->address1,
            'number' => $invoice_number,
            'zipCode' => preg_replace('/[^0-9]/', '', ($invoice_address->postcode)),
            'neighbourhood' => $invoice_address->address2,
            'city' => $invoice_address->city,
            'state' => $state->iso_code,
        ];

        $delivery_address = new Address($cart->id_address_delivery);
        $delivery_number = 's/n';
        if ($mapping_number->isMappingEnabled()) {
            $delivery_number = @$invoice_address->{$mapping_number->getMappedField()};
        }

        $shippingOne = [
            'addresseeName' => $delivery_address->firstname . ' ' . $delivery_address->lastname,
            'address' => $delivery_address->address1,
            'number' => $delivery_number,
            'zipCode' => preg_replace('/[^0-9]/', '', ($delivery_address->postcode)),
            'neighbourhood' => $delivery_address->address2,
            'city' => $delivery_address->city,
            'state' => $state->iso_code,
        ];

        $items = [];
        foreach ($cart->getProducts() as $p) {
            $items[] = [
                'id' => $p['id_product'],
                'quantity' => (int) $p['cart_quantity'],
                'type' => $p['is_virtual'] ? 2 : 1,
                'amount' => (int) (100 * $p['price_without_reduction']),
                'discount' => (int) (100 * $p['reduction']),
                'description' => $p['name']
            ];
        }

        return [
            'consumer' => $consumer,
            'billing' => $billing,
            'shipping' => [$shippingOne],
            'items' => $items,
            'environment' => [
                'consumer' => [
                    'ip' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '',
                    'sessionId' => (string) $this->context->cart->id,
                ]
            ],
        ];
    }

    /**
     * Cria uma transação e tenta aprová-la
     *
     * @param Cart   $cart                   Carrinho de compras para o qual a transação será realizada
     * @param array  $card_data              array com os dados do cartão de crédito
     * @param string $card_data[card_number] Número do cartão de crédito
     * @param string $card_data[cvv]         Código de segurança do cartão
     * @param string $card_data[month]       Mês de vencimento do cartão
     * @param string $card_data[year]        Ano de vencimento do cartão
     * @param string $card_data[card_holder] Nome do portador do cartão
     *
     * @return mixed Dados da transação
     */
    public function createTransactionForCart(Cart $cart, $card_data, $qty_installments=1)
    {
        $use_3ds = (bool) Configuration::get('AGEREDE_CREDIT_CARD_3DS');

        $interest = (float) Configuration::get("AGEREDE_CREDIT_CARD_INTEREST_RATE_" . ($qty_installments - 1));
        if ($interest < 0) {
            $this->createDiscount(-$interest);
        }

        $builder = $this->generateTransactionForCart($cart, Configuration::get('AGEREDE_ANTIFRAUD_ENABLED_CREDIT'), $use_3ds, 1);

        $builder->creditCard(
            $card_data['card_number'],
            $card_data['cvv'],
            $card_data['month'],
            $card_data['year'],
            $card_data['card_holder']
        )->installments($qty_installments);

        $client = $this->getAgRedeClient();
        $response = $client->createTransaction($builder->build());
        if ($use_3ds && isset($response['threeDSecure']['url']) && $response['threeDSecure']['url']) {
            header('Location: ' . $response['threeDSecure']['url']);
            exit();
        }
        return $response;
    }

    /**
     * Cria uma transação no cartão de débito e tenta aprová-la
     *
     * @param Cart   $cart                   Carrinho de compras para o qual a transação será realizada
     * @param array  $card_data              array com os dados do cartão de crédito
     * @param string $card_data[card_number] Número do cartão de crédito
     * @param string $card_data[cvv]         Código de segurança do cartão
     * @param string $card_data[month]       Mês de vencimento do cartão
     * @param string $card_data[year]        Ano de vencimento do cartão
     * @param string $card_data[card_holder] Nome do portador do cartão
     *
     * @return mixed Dados da transação
     */
    public function createDebitTransactionForCart(Cart $cart, $card_data)
    {
        $use_3ds = (bool) Configuration::get('AGEREDE_DEBIT_CARD_3DS');
        
        $builder = $this->generateTransactionForCart($cart, Configuration::get('AGEREDE_ANTIFRAUD_ENABLED'), $use_3ds, 0);

        $this->removeDiscount();
        $this->createDiscount(Configuration::get('AGEREDE_DEBIT_CARD_DISCOUNT'));

        $builder->debitCard(
            $card_data['card_number'],
            $card_data['cvv'],
            $card_data['month'],
            $card_data['year'],
            $card_data['card_holder']
        );

        $client = $this->getAgRedeClient();
        $response = $client->createTransaction($builder->build());
        if ($use_3ds && isset($response['threeDSecure']['url']) && $response['threeDSecure']['url']) {
            header('Location: ' . $response['threeDSecure']['url']);
            exit();
        }

        return $response;
    }

    //-------------------------- HOOKS ************************
    public function hookDisplayHeader()
    {
        //script para criação do fingerprint
        //@todo adicionar apenas no checkout
        $this->context->controller->addJs(
            [                
                $this->_path . 'views/js/loading_overlay.js',
                $this->_path . 'views/js/card.js',
                $this->_path . 'views/js/card_setup.js',
                $this->_path . 'views/js/credit_card.ps17.js',
                $this->_path . 'views/js/debit_card.ps17.js',
            ]
        );

        $this->context->controller->addCSS(_PS_MODULE_DIR_ . $this->name . '/views/css/front.css');
        $this->context->controller->addCSS(_PS_MODULE_DIR_ . $this->name . '/views/css/card.css');

        $agerede = [
            'base_uri'  => $this->context->shop->getBaseURL(true)
        ];

        if (Configuration::get('AGEREDE_ANTIFRAUD_ENABLED')) {
            $this->context->controller->addJs(
                [
                    $this->_path . 'views/js/fingerprint.js',
                    $this->_path . 'views/js/fingerprint_call.js',
                ]
            );

            if (Configuration::get('AGEREDE_SANDBOX_ENABLED')) {
                $customer_id = 'c54b99d0-894e-11e7-adc9-77887d29e284';
                $session_id  = Configuration::get('AGEREDE_SANDBOX_PV') . $this->context->cart->id;
            } else {
                $customer_id = '75ee1000-3f7c-11e7-af0a-d705e0dbc8bb';
                $session_id  = Configuration::get('AGEREDE_PV') . $this->context->cart->id;
            }

            $agerede['antifraud'] = [
                'customer_id'      => $customer_id,
                'zone'             => 'pt',
                'event_types'      => $this->context->controller->php_self,
                'session_id'       => $session_id,
                'request_endpoint' => 'https://fingerprint.userede.com.br'
            ];
        }

        Media::addJsDef(['agerede' => $agerede]);
    }

    public function hookPaymentOptions()
    {
        if (!$this->active) {
            return;
        }

        $errors = $this->validatePayment($this->context->cart, $this->context->customer);
        if (count($errors)) {
            return;
        }

        $options = [];

        if (Configuration::get('AGEREDE_CREDIT_CARD_ACTIVE')) {
            $newOption = new PaymentOption();
            $newOption->setCallToActionText(Configuration::get('AGEREDE_CREDIT_CARD_TEXT'))
                ->setForm($this->generateCreditCardForm());

            $options[] = $newOption;
        }

        if (Configuration::get('AGEREDE_DEBIT_CARD_ACTIVE') && $this->context->cart->getOrderTotal() > Configuration::get('AGEREDE_DEBIT_CARD_MIN_VALUE')) {
            $newOption = new PaymentOption();
            $newOption->setCallToActionText(Configuration::get('AGEREDE_DEBIT_CARD_TEXT'))
                ->setForm($this->generateDebitCardForm());

            $options[] = $newOption;
        }

        return $options;
    }

    public function hookDisplayOrderConfirmation($params)
    {
        if (!$this->active) {
            return;
        }

        if (version_compare(_PS_VERSION_, '1.7', '<')) {
            $order = $params['objOrder'];
        } else {
            $order = $params['order'];
        }

        if ($order->module !== $this->name) {
            return;
        }

        $transaction = AgERedeTransaction::getByOrderId($order->id);
        if (!Validate::isLoadedObject($transaction)) {
            return;
        }

        try {
            $this->context->smarty->assign(array(
                'order' => $order
            ));
        } catch (Exception $e) {
            if (isset($e->public_message)) {
                $error = $e->public_message;
            } else {
                $error = $e->getMessage();
            }

            $this->context->smarty->assign(array(
                $error => $error
            ));
        }

        return $this->display(_PS_MODULE_DIR_ . $this->name, 'success.tpl');
    }

    public function hookDisplayBackOfficeHeader()
    {
        $this->context->controller->addCSS([
            $this->_path . 'views/css/loading_overlay.css'
        ]);
        $this->context->controller->addJs([
            $this->_path . 'views/js/admin_orders.js',
            $this->_path . 'views/js/loading_overlay.js',
            $this->_path . 'views/js/admin_products_extra.js'
        ]);

        Media::addJsDef([
            'agerede_token_products_extra' => Tools::getAdminTokenLite('AdminModules'),
        ]);

        $states = OrderState::getOrderStates(Configuration::get('PS_LANG_DEFAULT'));
        $getStates = [];
        foreach ($states as $state => $value) {

            $getStates[$state] = [
                'id_order_state' => $value['id_order_state'],
                'paid' => $value['paid'],
                'name' => $value['name']
            ];
        }

        if (Tools::getValue('controller') === 'AdminOrders' && Tools::getIsSet('vieworder')) {
            Media::addJsDef([
                'token_url' => Tools::getAdminTokenLite('AdminModules'),
                'id_rede_order' => (Tools::getIsSet('id_order')) ? Tools::getValue('id_order') : '',
                'getStates' => $getStates
            ]);

            $this->context->controller->addCSS([$this->_path . 'views/css/loading_overlay.css']);
            $this->context->controller->addJs([
                $this->_path . 'views/js/loading_overlay.js',
                $this->_path . 'views/js/e_rede.js'
            ]);
        }

        if (Tools::getValue('controller') === 'AdminModules' && Tools::getValue('configure') === 'agerede') {
            Media::addJsDef([
                'antifraud' => Configuration::get('AGEREDE_ANTIFRAUD_ENABLED_CREDIT'),
                'refunded'  => Configuration::get('AGEREDE_REFUNDED'),
                'getStates' => $getStates
            ]);

            $this->context->controller->addJs([
                $this->_path . 'views/js/configure_e_rede.js'
            ]);
        }
    }

    public function hookDisplayAdminOrderTabOrder($params)
    {
        if (version_compare(_PS_VERSION_, '1.7.7', '>=')) {
            return false;
        }

        $order = $params['order'];
        $transaction = AgERedeTransaction::getByOrderId($order->id);

        if (!Validate::isLoadedObject($transaction)) {
            return;
        }

        return $this->display(_PS_MODULE_DIR_ . $this->name, 'admin_tab_order.tpl');
    }

    public function hookDisplayAdminOrderTabLink($params)
    {
        $order = new Order($params['id_order']);
        if ($order->module !== 'agerede') {
            return false;
        }

        return '
            <li class="nav-item">
                <a class="nav-link" id="agerede" data-toggle="tab" href="#ageredeContent" role="tab" aria-controls="ageredeContent" aria-expanded="true" aria-selected="true">
                    <i class="material-icons">credit_card</i>
                    E-Rede
                </a>
            </li>';
    }

    public function hookDisplayAdminOrderTabContent()
    {
        $id_order = Tools::getValue('id_order');
        $order = new Order($id_order);
        if ($order->module !== 'agerede') {
            return;
        }

        $transaction = AgERedeTransaction::getByOrderId($id_order);

        // if (!Validate::isLoadedObject($transaction)) {
        //     return;
        // }

        $this->context->smarty->assign(['agerede_transaction' => $transaction]);

        return $this->display(_PS_MODULE_DIR_ . $this->name, 'admin_order_tab_content.tpl');
    }

    public function hookDisplayAdminOrderContentOrder($params)
    {
        if (!$this->active) {
            return;
        }

        $order = $params['order'];
        $transaction = AgERedeTransaction::getByOrderId($order->id);

        if (!Validate::isLoadedObject($transaction)) {
            return;
        }

        $this->context->smarty->assign(['agerede_transaction' => $transaction]);

        return $this->display(_PS_MODULE_DIR_ . $this->name, 'admin_content_order.tpl');
    }

    public function hookDisplayAdminOrderSide($params)
    {
        if (!$this->active) {
            return;
        }

        $order = $params['order'];
        $transaction = AgERedeTransaction::getByOrderId($order->id);

        if (!Validate::isLoadedObject($transaction)) {
            return;
        }

        $this->context->smarty->assign(['agerede_transaction' => $transaction]);

        return $this->display(_PS_MODULE_DIR_ . $this->name, 'admin_order_side.tpl');
    }

    public function hookDisplayAdminProductsExtra($params)
    {
        if(version_compare(_PS_VERSION_, '1.7', '>=')){
          global $kernel;

          $requestStack = $kernel->getContainer()->get('request_stack');
          $request      = $requestStack->getCurrentRequest();
          $id_product   = $request->get('id');
        }else {
          $id_product   = Tools::getValue('id_product');
        }

        $enable = Db::getInstance()->getRow("SELECT agerede_enable_installments FROM " . _DB_PREFIX_ . "product WHERE id_product = {$id_product}");
        $this->context->smarty->assign([
            'id_product' => $id_product,
            'enable'     => $enable['agerede_enable_installments']
        ]);

        return $this->display(_PS_MODULE_DIR_ . $this->name, 'admin_tab_products_extra.tpl');
    }

    public function createDiscount($percentage)
    {
        $rules = $this->context->cart->getCartRules();

        foreach ($rules as $rule) {
            if ($rule['description'] === 'Desconto E-Rede') {
                return;
            }
        }

        $cart_rule = new CartRule();

        foreach (Language::getLanguages() as $lang) {
            $cart_rule->name[$lang['id_lang']] = 'Desconto E-Rede';
        }

        $cart_rule->id_customer = $this->context->cart->id_customer;
        $cart_rule->date_from = date('Y-m-d H:i:s');
        $cart_rule->date_to = date('Y-m-d H:i:s', strtotime("+2 days",strtotime(date('Y-m-d'))));
        $cart_rule->description = 'discount_boleto';
        $cart_rule->quantity = 1;
        $cart_rule->quantity_per_user = 1;
        $cart_rule->priority = 1;
        $cart_rule->partial_use = 1;
        $cart_rule->code = md5('discount_boleto' .$this->context->cart->id_customer . date('Y-m-d H:i:s'));

        $cart_rule->minimum_amount = 0;
        $cart_rule->minimum_amount_tax = 0;
        $cart_rule->minimum_amount_currency = 1;
        $cart_rule->minimum_amount_shipping = 0;
        $cart_rule->country_restriction = 0;
        $cart_rule->carrier_restriction = 0;
        $cart_rule->group_restriction = 0;
        $cart_rule->cart_rule_restriction = 0;
        $cart_rule->product_restriction = 0;
        $cart_rule->shop_restriction = 0;
        $cart_rule->free_shipping = 0;

        $cart_rule->reduction_percent = $percentage;

        $cart_rule->reduction_tax = 1;
        $cart_rule->reduction_currency = $this->context->currency->id;
        $cart_rule->reduction_product = 0;

        $cart_rule->gift_product = 0;
        $cart_rule->gift_product_attribute = 0;
        $cart_rule->highlight = 0;
        $cart_rule->active = 1;

        $cart_rule->add();
        $this->context->cart->addCartRule($cart_rule->id);

        $this->context->cart->save();
    }

    public function removeDiscount()
    {
        $rules = $this->context->cart->getCartRules();

        foreach ($rules as $rule) {
            if ($rule['description'] === 'Desconto E-Rede') {
                $this->context->cart->removeCartRule($rule['id_cart_rule']);
            }
        }
    }        


    protected function enableInstallment($enableValue, $id)
    {
        $info = ($enableValue == 1) ? 'habilitado' : 'desabilitado';

        if(Db::getInstance()->execute("UPDATE " ._DB_PREFIX_."product SET agerede_enable_installments = {$enableValue} WHERE id_product = {$id}")){
            echo json_encode(['type' => 'success', 'message' => "Parcelamento {$info} com sucesso!"]);
            return;
        }else{
            echo json_encode(['type' => 'error', 'message' => "Ocorreu um erro ao realizar essa operação!"]);
            return;
        }
    }

    protected function cancelTransaction()
    {
        try {
            $orderId = (int) Tools::getValue('id_rede_order');
            $order   = new Order($orderId);
            $price   = (float) $order->total_paid;
            $amountCents = (int) round($price * 100);
            $tid = Tools::getValue('tid');

            $client = $this->getAgRedeClient();
            $response = $client->refundTransaction($tid, $amountCents);

            // Se chegamos aqui sem exceção, consideramos sucesso
            Configuration::updateValue('AGEREDE_REFUNDED', 7);
            $order->setCurrentState(Configuration::get('AGEREDE_REFUNDED'), $this->context->employee->id);

            echo json_encode(['type' => 'success', 'message' => 'A solicitação de reembolso foi bem-sucedida!']);
            return;
        } catch (Exception $e) {
            Logger::addLog("Error : {$e->getMessage()}", 2, null, null, null, true);
            echo json_encode(['type' => 'error', 'message' => 'Erro : Não foi possível cancelar essa transação!']);
            return;
        }
    }
}
