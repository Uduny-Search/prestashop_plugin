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

class UdsearchintegratorFeedModuleFrontController extends ModuleFrontController {

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

    public function postProcess()
    {
        $sql = 'SELECT id_udsearch_filters 
                FROM `'._DB_PREFIX_.'udsearch_filters` 
                WHERE slug = "'.Tools::getValue('link_rewrite').'"';

        $id = DB::getInstance()->getValue($sql);
    	$data = new SIGenerator(!empty($id) ? $id : 0, (int)Tools::getValue('limit', 100), (int)Tools::getValue('offset', 0));
        
        if(Tools::getValue('request_total') == 1){
            $data->show_total = true;
        }
        
        if(Tools::isSubmit('xml') && Tools::getValue('xml') == 1){
            header("content-type: text/xml");
            echo exit(json_encode($data->getXML()));
        }

        header('Content-Type: application/json');
        echo exit(json_encode($data->getJson()));
    }
}