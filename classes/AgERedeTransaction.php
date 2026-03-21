<?php

class AgERedeTransaction extends AgObjectModel
{
    public static $definition = array(
        'table'     => 'agerede_transaction',
        'primary'   => 'id_agerede_transaction',
        'multilang' => false,
        'fields'    => array(
            'id_agerede_transaction' => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'id_order'               => array('type' => self::TYPE_INT, 'validate' => 'isInt', 'db_type' => 'int'),
            'tid'                    => array('type' => self::TYPE_STRING, 'db_type' => 'varchar(20)'),
            'nsu'                    => array('type' => self::TYPE_STRING, 'db_type' => 'varchar(12)'),
            'authorization_code'         => array('type' => self::TYPE_STRING, 'db_type' => 'varchar(6)'),
            'card_bin'         => array('type' => self::TYPE_STRING, 'db_type' => 'varchar(6)'),
            'last4' => array('type' => self::TYPE_STRING, 'db_type' => 'varchar(4)'),   
            'ip_address' => array('type' => self::TYPE_STRING, 'db_type' => 'varchar(255)'),


            'installments'           => array('type' => self::TYPE_INT, 'db_type' => 'int unsigned', 'default' => 1),
            'antifraud_score'         => array('type' => self::TYPE_FLOAT, 'db_type' => 'float'),
            'antifraud_recommendation' => array('type' => self::TYPE_STRING, 'db_type' => 'varchar(256)'),
            'antifraud_risk_level'   => array('type' => self::TYPE_STRING, 'db_type' => 'varchar(256)'),
            'date_add'               => array('type' => self::TYPE_DATE, 'db_type' => 'datetime'),
            'date_upd'               => array('type' => self::TYPE_DATE, 'db_type' => 'datetime')
        ),
        'indexes' => array(
            array(
                'name' => 'unique_tid',
                'prefix' => 'unique',
                'fields' => array('tid')
            ),
            array(
                'name' => 'unique_id_order',
                'prefix' => 'unique',
                'fields' => array('id_order')
            )
        )
    );

    public $id_agerede_transaction;
    public $id_order;
    public $tid;
    public $nsu;
    public $authorization_code;
    public $card_bin;
    public $last4;
    public $ip_address;
    public $installments;
    public $antifraud_score;
    public $antifraud_recommendation;
    public $antifraud_risk_level;
    public $date_add;
    public $date_upd;

    public static function getByOrderId($id_order)
    {
        $cache_key = get_called_class() . __FUNCTION__ . $id_order;

        if (!Cache::isStored($cache_key)) {
            $order = new Order($id_order);

            $sql = new DbQuery();
            $sql->select('t.*');
            $sql->from('agerede_transaction', 't');      
            $sql->where('id_order=' . (int) $id_order);

            $db_data = Db::getInstance()->getRow($sql);

            if (!$db_data) {
                $db_data = array();
            }
            
            $return = new AgERedeTransaction();
            $return->hydrate($db_data);

            Cache::store($cache_key, $return);
        }
        

        return Cache::retrieve($cache_key);
    }
}
