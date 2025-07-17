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

if (!defined('_PS_VERSION_')) {
    exit;
}

class Udsearchintegrator extends Module
{
    protected $config_form = false;
    public const MODULE_ROUTE = 'search-export';
    public const TESTING_PARAM = 'udsi_test';
    public const TESTING_VALUE = 'testing';

    public function __construct()
    {
        $this->name = 'udsearchintegrator';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Unusual Dope';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = Translate::getModuleTranslation($this, 'UD Search Integrator', $this->name);
        $this->description = Translate::getModuleTranslation($this, 'Exporting all of your product for search and SEO optimization', $this->name);

        $this->confirmUninstall = Translate::getModuleTranslation($this, 'Please do not regret your decision!', $this->name);

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
        $this->loadModuleClasses();
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        include(dirname(__FILE__).'/sql/install.php');

        if (!Configuration::hasKey('UDSEARCHINTEGRATOR')) {
            Configuration::updateValue('UDSEARCHINTEGRATOR', $this->initConfig());
        }

        return parent::install() &&
            $this->registerHook('actionFrontControllerSetMedia') &&
            // $this->registerHook('displayOrderConfirmation') &&
            $this->registerHook('moduleRoutes');
    }

    public function uninstall()
    {
        include(dirname(__FILE__).'/sql/uninstall.php');

        return parent::uninstall();
    }

    /**
     * Includes classes which are used in module
     */
    private function loadModuleClasses()
    {
        require_once($this->getLocalPath() . 'classes/SIGenerator.php');
    }

    public static function initConfig()
    {
        $array = array(
            'SCRIPT_SRC' => '',
            'IS_PRODUCTION' => 0,
            'IMG_SIZE' => 1,
        );

        return json_encode($array);
    }

    public static function getConfig()
    {
        return json_decode(Configuration::get('UDSEARCHINTEGRATOR'), true);
    }

    public function renderNavbarMenu(string $controller_name, array $custom_link = array(), array $alert = array())
    {
        Media::addJsDef([
            'ud_nav' => $this->context->link->getAdminLink('AdminModules', true, [], array('configure' => $this->name)),
        ]);
        $this->context->controller->addJS($this->getLocalPath() . 'views/src/toastr/js/toastr.min.js');
        $this->context->controller->addJS($this->getLocalPath() . 'views/js/nav.js');

        $this->context->controller->addCSS($this->getLocalPath() . 'views/src/toastr/css/toastr.min.css');
        return array(
            'left' => array(
                array(
                    'href' => $this->context->link->getAdminLink('AdminSearchIntegratorDashboard'),
                    'desc' => Translate::getModuleTranslation($this, 'Dashboard', $this->name),
                    'icon' => 'process-icon-home icon-home',
                    'disabled' => ($controller_name == 'si_dashboard') ? true : false,
                ),
                array(
                    'href' => $this->context->link->getAdminLink('AdminSearchIntegratorConfig'),
                    'desc' => Translate::getModuleTranslation($this, 'Configuration', $this->name),
                    'icon' => 'process-icon-cog icon-cog',
                    'disabled' => ($controller_name == 'si_config') ? true : false,
                ),
            ),
            'right' => $custom_link,
            'alert' => $alert,
        );
    }
    public static function updateConfig($config)
    {
        // configuration check before update
        $current = self::getConfig();
        $updatedConfig = array(
            'SCRIPT_SRC' => (isset($config['SCRIPT_SRC']) ? $config['SCRIPT_SRC'] : $current['SCRIPT_SRC']),
            'IS_PRODUCTION' => (isset($config['IS_PRODUCTION']) ? $config['IS_PRODUCTION'] : $current['IS_PRODUCTION']),
            'IMG_SIZE' => (isset($config['IMG_SIZE']) ? $config['IMG_SIZE'] : $current['IMG_SIZE']),
        );

        return Configuration::updateValue('UDSEARCHINTEGRATOR', json_encode($updatedConfig));
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        return Tools::redirectAdmin($this->context->link->getAdminLink('AdminSearchIntegratorDashboard'));
    }

    public function hookModuleRoutes($params)
    {
        $route = array(
            'module-udsearchintegrator-feed' => array(
                'controller' => 'feed',
                'rule' => self::MODULE_ROUTE . '/{link_rewrite}',
                'keywords' => array(
                    'link_rewrite' => array('regexp' => '[_a-zA-Z0-9-\pL]*', 'param' => 'link_rewrite'),
                ),
                'params' => array(
                    'fc' => 'module',
                    'module' => 'udsearchintegrator',
                ),
            ),
        );

        return $route;
    }

    // public function hookDisplayOrderConfirmation($params)
    // {
    //     $order = $params['orders'];
    //     $products = array();
    //     foreach($order->getProductsDetail() as $detail){
    //         $products[] = array(
    //             'product_id' => $detail['product_id'],
    //             'product_name' => $detail['product_name'],
    //             'quantity' => $detail['product_quantity'],
    //         );
    //     }
    //     $data = array(
    //         'order_id' => $order->id,
    //         'product_data' => $products,
    //         'order_amount' => $order->total_paid_tax_incl,
    //         'order_amount_wt' => $order->total_paid_tax_excl,
    //     );

    //     var_dump($data);die();
    // }

    public function hookActionFrontControllerSetMedia(){
        $config = self::getConfig();
        if($config['IS_PRODUCTION'] || Tools::isSubmit(self::TESTING_PARAM) || Tools::getValue(self::TESTING_PARAM) == self::TESTING_VALUE){
            if(!empty($config['SCRIPT_SRC']) && Validate::isAbsoluteUrl($config['SCRIPT_SRC'])){
                $parsedUrl = parse_url($config['SCRIPT_SRC']);

                $queryParams = [];
                if (isset($parsedUrl['query'])) {
                    parse_str($parsedUrl['query'], $queryParams);
                }

                $queryParams['host'] = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
                $queryParams['lang'] = $this->context->language->iso_code;

                $newUrl = (isset($parsedUrl['scheme']) ? $parsedUrl['scheme'] . '://' : '') .
                        (isset($parsedUrl['host']) ? $parsedUrl['host'] : '') .
                        (isset($parsedUrl['path']) ? $parsedUrl['path'] : '') .
                        '?' . http_build_query($queryParams);

                $this->context->controller->registerJavascript('searchintegrator', $newUrl, [
                    'server' => 'remote',
                    'position' => 'bottom', // or 'head'
                    'priority' => 100, // optional
                ]);

                $this->context->controller->registerJavascript('udsi_front_js', $this->_path . 'views/js/front.js');
                // $this->context->controller->registerJavascript('udsi_gtag_js', $this->_path . 'views/js/gtag-custom.js');

                Media::addJsDef([
                    'udsi_cart_url' => $this->context->link->getPageLink('cart', true),
                ]);
            }
        }
    }
}
