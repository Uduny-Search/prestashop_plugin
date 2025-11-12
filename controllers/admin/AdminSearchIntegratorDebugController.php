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

class AdminSearchIntegratorDebugController extends ModuleAdminController
{
    protected $name;

    public function __construct()
    {
        $this->name = 'si_debug';
        $this->bootstrap = true;
        $this->toolbar_title = "Search Integrator Debug";

        parent::__construct();
    }

    public function initContent()
    {
        if(Tools::isSubmit('ajax')) return $this->displayAjax();

        $this->renderWarning();

        $custom_link = array();

        $this->context->smarty->assign('nav_menu', $this->module->renderNavbarMenu($this->name, $custom_link));

        $this->context->controller->addJS($this->module->getLocalPath() . 'views/js/debug.js');

        Media::addJsDef([
            'debug_url' => $this->context->link->getAdminLink('AdminSearchIntegratorDebug'),
        ]);

        $this->context->smarty->assign([
            'content' => $this->context->smarty->fetch($this->module->getLocalPath() . 'views/templates/admin/nav.tpl') . $this->renderContent(),
        ]);
    }

    public function renderContent(){
        if(Tools::isSubmit('ajax')) return;

        $this->context->smarty->assign([
            'debug' => array(
                'dates' => $this->getAvailableDebugDates(),
            )
        ]);

        return $this->context->smarty->fetch($this->module->getLocalPath() . 'views/templates/admin/debug.tpl');
    }

    public function postProcess()
    {
        if(Tools::isSubmit('submitAdd' . $this->name)){
            $ips = explode(',', Tools::getValue('SERVER_WHITELIST', ''));
            foreach($ips as $key => $ip){
                if(empty($ip) || (!preg_match('/^((25[0-5]|(2[0-4]|1\d|[1-9]|)\d)\.?\b){4}$/', (string) $ip) && !preg_match('/(([0-9a-fA-F]{1,4}:){7,7}[0-9a-fA-F]{1,4}|([0-9a-fA-F]{1,4}:){1,7}:|([0-9a-fA-F]{1,4}:){1,6}:[0-9a-fA-F]{1,4}|([0-9a-fA-F]{1,4}:){1,5}(:[0-9a-fA-F]{1,4}){1,2}|([0-9a-fA-F]{1,4}:){1,4}(:[0-9a-fA-F]{1,4}){1,3}|([0-9a-fA-F]{1,4}:){1,3}(:[0-9a-fA-F]{1,4}){1,4}|([0-9a-fA-F]{1,4}:){1,2}(:[0-9a-fA-F]{1,4}){1,5}|[0-9a-fA-F]{1,4}:((:[0-9a-fA-F]{1,4}){1,6})|:((:[0-9a-fA-F]{1,4}){1,7}|:)|fe80:(:[0-9a-fA-F]{0,4}){0,4}%[0-9a-zA-Z]{1,}|::(ffff(:0{1,4}){0,1}:){0,1}((25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])\.){3,3}(25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])|([0-9a-fA-F]{1,4}:){1,4}:((25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])\.){3,3}(25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9]))/', (string) $ip))) unset($ips[$key]);
            }
            $config = array(
                'SCRIPT_SRC' => Tools::getValue('SCRIPT_SRC', ''),
                'IS_PRODUCTION' => Tools::getValue('IS_PRODUCTION', 0),
                'IMG_SIZE' => Tools::getValue('IMG_SIZE', 0),
                'SERVER_WHITELIST' => implode(',', $ips),
            );

            // call server instance to verify
            $result = $this->module->updateConfig($config);
            if ($result) {
                $this->context->cookie->__set($this->name, json_encode(array('success' => true, 'message' => $this->trans('Search Config Saved!', [], 'Modules.Udsearchintegrator.AdminSearchIntegratorConfig'))));
            } else {
                $this->context->cookie->__set($this->name, json_encode(array('success' => false, 'message' => $this->trans('There is something wrong when saving Search Configuration!', [], 'Modules.Udsearchintegrator.AdminSearchIntegratorConfig'))));
            }

            Tools::redirectAdmin($this->context->link->getAdminLink('AdminSearchIntegratorConfig'));
        }
    }

    public function displayAjax()
    {
        if(!Tools::isSubmit('method')) exit;

        switch(Tools::getValue('method')){
            case 'getFile': {
                $date = Tools::getValue('debug');
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                    echo exit(json_encode(array('success' => false)));
                }

                $logFile = $this->module->getLocalPath() . 'debug/' . $date . '_uduny.log';

                if (!file_exists($logFile)) {
                    echo exit(json_encode(array('success' => false)));
                }

                $content = file_get_contents($logFile);
                if ($content === false) {
                    echo exit(json_encode(array('success' => false)));
                }

                echo exit(json_encode(array('success' => true, 'debug' => $content)));
                break;
            }
            case 'removeFile': {
                $date = Tools::getValue('debug');
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                    echo exit(json_encode(array('success' => false)));
                }

                $logFile = $this->module->getLocalPath() . 'debug/' . $date . '_uduny.log';

                if (!file_exists($logFile)) {
                    echo exit(json_encode(array('success' => false)));
                }

                if(unlink($logFile)){
                    echo exit(json_encode(array('success' => true)));
                }
                break;
            }
            case 'removeAll': {
                $debugDir = $this->module->getLocalPath() . 'debug/';
                if (!is_dir($debugDir)) {
                    echo exit(json_encode(array('success' => false)));
                }

                foreach (glob($debugDir . '*.log') as $filePath) {
                    $filename = basename($filePath);
                    if (preg_match('/^\d{4}-\d{2}-\d{2}_uduny\.log$/', $filename)) {
                        if (@unlink($filePath)) {
                        }
                    }
                }

                echo exit(json_encode(array('success' => true)));
                break;
            }
        }
    }

    public function returnYesNo()
    {
        return array(
            array(
                'id' => 'active_on',
                'value' => 1,
                'label' => $this->trans('Enabled', [], 'Modules.Udsearchintegrator.AdminSearchIntegratorConfig')
            ),
            array(
                'id' => 'active_off',
                'value' => 0,
                'label' => $this->trans('Disabled', [], 'Modules.Udsearchintegrator.AdminSearchIntegratorConfig')
            )
        );
    }

    private function renderWarning()
    {
        if ($this->context->cookie->__isset($this->name)) {
            $custom = json_decode($this->context->cookie->__get($this->name), true);
            $this->context->cookie->__unset($this->name);
            if ($custom['success']) {
                $this->confirmations[] = $custom['message'];
            } else {
                $this->errors[] = $custom['message'];
            }
        }
    }

    public function getAvailableDebugDates()
    {
        $debugDir = $this->module->getLocalPath() . 'debug/';
        if (!is_dir($debugDir)) {
            return [];
        }

        $files = glob($debugDir . '*.log');

        $dates = [];
        foreach ($files as $filePath) {
            $filename = basename($filePath);

            if (preg_match('/^(\d{4}-\d{2}-\d{2})_uduny\.log$/', $filename, $matches)) {
                $dates[] = $matches[1];
            }
        }

        rsort($dates);

        return $dates;
    }
}