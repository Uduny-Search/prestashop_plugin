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
        $this->toolbar_title = "Search Integrator Configuration";

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

        $url = _PS_BASE_URL_SSL_ . '/' . $this->context->language->iso_code . '/?' . Udsearchintegrator::TESTING_PARAM . '=' . Udsearchintegrator::TESTING_VALUE;
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
                        'type' => 'switch',
                        'label' => $this->trans('Production Mode', [], 'Modules.Udsearchintegrator.AdminSearchIntegratorConfig'),
                        'name' => 'IS_PRODUCTION',
                        'is_bool' => true,
                        'values' => $this->returnYesNo(),
                        'default' => 1,
                        'desc' =>  $this->trans('Testing or Production mode.', [], 'Modules.Udsearchintegrator.AdminSearchIntegratorConfig'),
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
            'SCRIPT_SRC' => $config['SCRIPT_SRC'] ?? '',
            'IMG_SIZE' => $config['IMG_SIZE'] ?? 0,
            'URL' => '',
        );

        return $form->generateForm($fields_form);
    }

    public function postProcess()
    {
        if(Tools::isSubmit('submitAdd' . $this->name)){
            $config = array(
                'SCRIPT_SRC' => Tools::getValue('SCRIPT_SRC', ''),
                'IS_PRODUCTION' => Tools::getValue('IS_PRODUCTION', 0),
                'IMG_SIZE' => Tools::getValue('IMG_SIZE', 0),
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