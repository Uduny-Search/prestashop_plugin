<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to a trade license awared by
 * PT. Jawa Kreatif Design.
 *
 * Any use, reproduction, modification or distribution
 * of this source file without the written consent of
 * PT. Jawa Kreatif Design It Is prohibited.
 *
 *  @author    UnusualDope <info@unusualdope.com>
 *  @copyright 2022 PT. Jawa Kreatif Design
 *  @license   Commercial license
 */

class UdsearchintegratorSessionModuleFrontController extends ModuleFrontController {

    public function init()
    {
        parent::init();
        header('X-Robots-Tag: noindex, nofollow', true);
        $this->ajax = true;
    }

    public function __construct()
    {
        parent::__construct();
    }

    public function displayAjax()
    {
        //
    }

    public function postProcess()
    {
        if(!Tools::isSubmit('action') || !Tools::isSubmit('token') || !SISession::validateCSRF(Tools::getValue('token'))) exit;

        switch(Tools::getValue('action')){
            case 'sess': {
                $uduny_session = Tools::getValue('session_id');
                if(empty($uduny_session)) exit;

                $context = Context::getContext();
                $customer = SISession::getBySession($uduny_session);
                if(empty($customer)){
                    $customer = SISession::createSession($uduny_session, $context);
                }

                if(empty($customer['id_customer'])){
                    SISession::updateSession($uduny_session, $context);
                }

                exit;
            }
        }
    }
}