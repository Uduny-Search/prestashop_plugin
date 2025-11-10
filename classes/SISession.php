<?php

class SISession extends ObjectModel
{
    public $id_guest;
    public $id_customer;
    public $uduny_sess;

    public static $definition = [
        'table' => 'udsearch_sessions',
        'primary' => 'id_udsearch_sessions',
        'fields' => [
            'id_guest' => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'required' => true],
            'id_customer' => ['type' => self::TYPE_INT, 'validate' => 'isInt'],
            'uduny_sess' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
        ],
    ];

    public function __construct()
    {
        parent::__construct();

        //
    }

    public static function getBySession($session)
    {
        $sql = 'SELECT *
                FROM '. _DB_PREFIX_ . self::$definition['table'] .'
                WHERE uduny_sess = "'. $session .'"
                ORDER BY id_udsearch_sessions DESC';

        return DB::getInstance()->getRow($sql);
    }

    public static function getTokenByCustomer($id_customer)
    {
        $sql = 'SELECT uduny_sess
                FROM '. _DB_PREFIX_ . self::$definition['table'] .'
                WHERE id_customer = "'. $id_customer .'"
                ORDER BY id_udsearch_sessions DESC';

        return DB::getInstance()->getValue($sql);
    }

    public static function getTokenByGuest($id_guest)
    {
        $sql = 'SELECT uduny_sess
                FROM '. _DB_PREFIX_ . self::$definition['table'] .'
                WHERE id_guest = "'. $id_guest .'"
                ORDER BY id_udsearch_sessions DESC';

        return DB::getInstance()->getValue($sql);
    }

    public static function createSession($session, Context $context)
    {
        if(empty($session)) return false;

        $is_customer = $context->customer->isLogged();
        $data = array(
            'id_guest' => (!$is_customer) ? $context->customer->id_guest : 0,
            'id_customer' => ($is_customer) ? $context->customer->id : 0,
            'uduny_sess' => $session,
            'date_add' => date('Y-m-d H:i:s'),
        );

        return DB::getInstance()->insert(self::$definition['table'], $data, false, true, DB::INSERT_IGNORE);
    }

    public static function updateSession($session, Context $context)
    {
        if(empty($session) || !$context->customer->isLogged()) return false;

        return DB::getInstance()->update(self::$definition['table'], ['id_customer' => $context->customer->id], ' uduny_sess = "'. $session .'"');
    }

    public static function removeOldSession()
    {
        return DB::getInstance()->delete('udsearch_sessions', 'date_add < (CURDATE() - INTERVAL 30 DAY)');
    }

    public static function getCSRF()
    {
        $csrf = Tools::hash(Tools::getToken(false) . time());
        Context::getContext()->cookie->__set('udsi_csrf', $csrf);

        return $csrf;
    }

    public static function validateCSRF($token)
    {
        $csrf = Context::getContext()->cookie->__get('udsi_csrf');
        Context::getContext()->cookie->__unset('udsi_csrf');

        if(empty($csrf) || $csrf != $token) return false;

        return true;
    }
}