<?php

class AgERedeRequest extends AgObjectModel
{
    public static $definition = [
        'table'   => 'agerede_request',
        'primary' => 'id_agerede_request',
        'multilang' => false,
        'fields'  => [
            'id_agerede_request' => ['type' => self::TYPE_INT,'validate' => 'isInt'],
            'endpoint' => ['type' => self::TYPE_STRING,'db_type' => 'varchar(255)','required' => true],
            'headers' => ['type' => self::TYPE_STRING,'db_type' => 'text'],
            'method' => ['type' => self::TYPE_STRING,'db_type' => 'varchar(15)','required' => true],
            'body' => ['type' => self::TYPE_STRING,'db_type' => 'text'],
            'http_code' => ['type' => self::TYPE_INT,'db_type' => 'int unsigned'],
            'response' => ['type' => self::TYPE_HTML,'db_type' => 'text'],
            'date_add' => ['type'     => self::TYPE_DATE, 'validate' => 'isDate', 'db_type'  => 'datetime'],
        ]
    ];


    public $id_agerede_request;
    public $endpoint;
    public $headers;
    public $method;
    public $body;
    public $http_code;
    public $response;
    public $date_add;  
}