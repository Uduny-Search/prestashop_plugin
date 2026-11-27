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

class AdminSearchIntegratorConfigController extends ModuleAdminController
{
    protected $name;

    public function __construct()
    {
        $this->name = 'si_config';
        $this->bootstrap = true;
        $this->toolbar_title = "Uduny - Search Integrator Configuration";

        parent::__construct();
    }

    public function initContent()
    {
        if(Tools::isSubmit('ajax')) return $this->displayAjax();

        $this->renderWarning();

        $custom_link = array();

        $this->context->smarty->assign('nav_menu', $this->module->renderNavbarMenu($this->name, $custom_link));

        $this->context->smarty->assign([
            'content' => $this->context->smarty->fetch($this->module->getLocalPath() . 'views/templates/admin/nav.tpl') . $this->renderContent(),
        ]);
    }

    public function renderContent(){
        if(Tools::isSubmit('ajax')) return;

        $langs = Language::getLanguages();
        $url = _PS_BASE_URL_SSL_ . ((count($langs) > 1) ? '/' . $this->context->language->iso_code : '') . '/?' . Udsearchintegrator::TESTING_PARAM . '=' . Udsearchintegrator::TESTING_VALUE;
        $config = $this->module->getConfig();
        $fields_form = array([
            'form' => array(
                'legend' => array(
                    'title' => $this->trans('Configuration', [], 'Modules.Udsearchintegrator.AdminSearchIntegratorConfig'),
                    'icon' => 'icon-cog',
                ),
                // 'description' => $this->trans('Testing URL:', [], 'Modules.Udsearchintegrator.AdminSearchIntegratorConfig') . '<br><a href="'. $url .'" target="_blank">'. $url .'</a>',
                'input' => array(
                    array(
                        'type' => 'text',
                        'label' => $this->trans('Master Token', [], 'Modules.Udsearchintegrator.AdminSearchIntegratorConfig'),
                        'name' => 'MASTER_TOKEN',
                        'desc' => $this->trans('Token to debug and bypass whitelist', [], 'Modules.Udsearchintegrator.AdminSearchIntegratorConfig'),
                        'col' => 2,
                        'disabled' => true,
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->trans('Server Whitelist', [], 'Modules.Udsearchintegrator.AdminSearchIntegratorConfig'),
                        'name' => 'SERVER_WHITELIST',
                        'desc' => $this->trans('List of ip address separated by comma, that can call this module\'s api, leaving empty will make it public', [], 'Modules.Udsearchintegrator.AdminSearchIntegratorConfig'),
                        'col' => 7,
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->trans('Image Size', [], 'Modules.Udsearchintegrator.AdminSearchIntegratorConfig'),
                        'name' => 'IMG_SIZE',
                        'desc' => $this->trans('Export image size', [], 'Modules.Udsearchintegrator.AdminSearchIntegratorConfig'),
                        'options' => array(
                            'query' => ImageType::getImagesTypes(),
                            'id' => 'id_image_type',
                            'name' => 'name',
                        ),
                        'col' => 5,
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->trans('Script URL', [], 'Modules.Udsearchintegrator.AdminSearchIntegratorConfig'),
                        'name' => 'SCRIPT_SRC',
                        'desc' => $this->trans('Embedded JS Script.', [], 'Modules.Udsearchintegrator.AdminSearchIntegratorConfig'),
                        'col' => 7,
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->trans('Pricelist Mode', [], 'Modules.Udsearchintegrator.AdminSearchIntegratorConfig'),
                        'name' => 'PRICELIST_MODE',
                        'desc' => $this->trans('Export per location/currency "prices". Auto: only when more than one location/currency pair exists.', [], 'Modules.Udsearchintegrator.AdminSearchIntegratorConfig'),
                        'options' => array(
                            'query' => array(
                                array('id' => 'auto', 'name' => $this->trans('Auto', [], 'Modules.Udsearchintegrator.AdminSearchIntegratorConfig')),
                                array('id' => 'always', 'name' => $this->trans('Always', [], 'Modules.Udsearchintegrator.AdminSearchIntegratorConfig')),
                                array('id' => 'never', 'name' => $this->trans('Never', [], 'Modules.Udsearchintegrator.AdminSearchIntegratorConfig')),
                            ),
                            'id' => 'id',
                            'name' => 'name',
                        ),
                        'col' => 3,
                    ),
                    array(
                        'type' => 'textarea',
                        'label' => $this->trans('Currency Location Map', [], 'Modules.Udsearchintegrator.AdminSearchIntegratorConfig'),
                        'name' => 'CURRENCY_LOCATION_MAP',
                        'desc' => $this->trans('One per line, CURRENCY=COUNTRY ISO (e.g. USD=US). Overrides the default country for non-default currencies.', [], 'Modules.Udsearchintegrator.AdminSearchIntegratorConfig'),
                        'col' => 4,
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->trans('Search URL', [], 'Modules.Udsearchintegrator.AdminSearchIntegratorConfig'),
                        'name' => 'SEARCH_URL',
                        'desc' => $this->trans('UdunySearch "url" option.', [], 'Modules.Udsearchintegrator.AdminSearchIntegratorConfig'),
                        'col' => 7,
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->trans('Trigger (clicked)', [], 'Modules.Udsearchintegrator.AdminSearchIntegratorConfig'),
                        'name' => 'TRIGGER_CLICKED',
                        'desc' => $this->trans('CSS selector(s) that open search on click.', [], 'Modules.Udsearchintegrator.AdminSearchIntegratorConfig'),
                        'col' => 5,
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->trans('Trigger (typing)', [], 'Modules.Udsearchintegrator.AdminSearchIntegratorConfig'),
                        'name' => 'TRIGGER_TYPING',
                        'desc' => $this->trans('CSS selector(s) that open search on typing.', [], 'Modules.Udsearchintegrator.AdminSearchIntegratorConfig'),
                        'col' => 5,
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->trans('Production Mode', [], 'Modules.Udsearchintegrator.AdminSearchIntegratorConfig'),
                        'name' => 'IS_PRODUCTION',
                        'is_bool' => true,
                        'values' => $this->returnYesNo(),
                        'default' => 1,
                        'desc' =>  $this->trans('Testing or Production mode.', [], 'Modules.Udsearchintegrator.AdminSearchIntegratorConfig'),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->trans('Debug mode', [], 'Modules.Udsearchintegrator.AdminSearchIntegratorConfig'),
                        'name' => 'IS_DEBUG',
                        'is_bool' => true,
                        'values' => $this->returnYesNo(),
                        'default' => 0,
                        'desc' =>  $this->trans('Debug will write logs on every call.', [], 'Modules.Udsearchintegrator.AdminSearchIntegratorConfig'),
                    ),
                    array(
                        'type' => 'html',
                        'label' => $this->trans('Testing Url', [], 'Modules.Udsearchintegrator.AdminSearchIntegratorConfig'),
                        'name' => 'URL',
                        'html_content' => '<div style="display:flex;">
                                <style>#si_config_form .form-wrapper:has(input#IS_PRODUCTION_on[name="IS_PRODUCTION"]:checked) > .form-group:last-child {display:none;}</style>
                                <div class="input-group col-lg-6">
                                    <a href="'.$url.'" target="_blank" class="btn btn-primary">Testing Mode</a>
                                </div>
                            </div>',
                        'col' => 5,
                    ),
                ),
                'submit' => array(
                    'title' => $this->trans('Save', [], 'Modules.Udsearchintegrator.AdminSearchIntegratorConfig'),
                ),
            ),
        ]);

        $form = new HelperForm();
        $form->table = $this->name;
        $form->fields_value = array(
            'IS_PRODUCTION' => $config['IS_PRODUCTION'] ?? 0,
            'IS_DEBUG' => $config['IS_DEBUG'] ?? 0,
            'SCRIPT_SRC' => $config['SCRIPT_SRC'] ?? '',
            'IMG_SIZE' => $config['IMG_SIZE'] ?? 0,
            'SERVER_WHITELIST' => $config['SERVER_WHITELIST'] ?? '',
            'MASTER_TOKEN' => $config['MASTER_TOKEN'] ?? '',
            'PRICELIST_MODE' => $config['PRICELIST_MODE'] ?? 'auto',
            'CURRENCY_LOCATION_MAP' => $config['CURRENCY_LOCATION_MAP'] ?? '',
            'SEARCH_URL' => $config['SEARCH_URL'] ?? '',
            'TRIGGER_CLICKED' => $config['TRIGGER_CLICKED'] ?? '',
            'TRIGGER_TYPING' => $config['TRIGGER_TYPING'] ?? '',
            'URL' => '',
        );

        return $form->generateForm($fields_form);
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
                'IS_DEBUG' => Tools::getValue('IS_DEBUG', 0),
                'IMG_SIZE' => Tools::getValue('IMG_SIZE', 0),
                'SERVER_WHITELIST' => implode(',', $ips),
                'PRICELIST_MODE' => in_array(Tools::getValue('PRICELIST_MODE'), array('auto', 'always', 'never'), true) ? Tools::getValue('PRICELIST_MODE') : 'auto',
                'CURRENCY_LOCATION_MAP' => trim((string) Tools::getValue('CURRENCY_LOCATION_MAP', '')),
                'SEARCH_URL' => trim((string) Tools::getValue('SEARCH_URL', '')),
                'TRIGGER_CLICKED' => trim((string) Tools::getValue('TRIGGER_CLICKED', '')),
                'TRIGGER_TYPING' => trim((string) Tools::getValue('TRIGGER_TYPING', '')),
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
}