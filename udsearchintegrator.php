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
        $this->version = '1.0.8';
        $this->author = 'Unusual Dope';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->trans('Uduny - Search Integrator', [], 'Modules.Udsearchintegrator.Udsearchintegrator');
        $this->description = $this->trans('Exporting all of your product for search and SEO optimization', [], 'Modules.Udsearchintegrator.Udsearchintegrator');

        $this->confirmUninstall = $this->trans('Please do not regret your decision!', [], 'Modules.Udsearchintegrator.Udsearchintegrator');

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
            $this->registerHook(version_compare(_PS_VERSION_, '8.0.0', '>=') ? 'actionValidateOrderAfter' : 'ActionValidateOrder') &&
            $this->registerHook('actionCartUpdateQuantityBefore') &&
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
        require_once($this->getLocalPath() . 'classes/SISession.php');
        require_once($this->getLocalPath() . 'classes/SIPriceContext.php');
    }

    public static function initConfig()
    {
        $array = array(
            'SCRIPT_SRC' => '',
            'IS_PRODUCTION' => 0,
            'IMG_SIZE' => 1,
            'SERVER_WHITELIST' => '',
            'MASTER_TOKEN' => '',
            'PRICELIST_MODE' => 'auto',
            'CURRENCY_LOCATION_MAP' => '',
            'SEARCH_URL' => '',
            'TRIGGER_CLICKED' => '',
            'TRIGGER_TYPING' => '',
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
                    'desc' => $this->trans('Dashboard', [], 'Modules.Udsearchintegrator.Udsearchintegrator'),
                    'icon' => 'process-icon-home icon-home',
                    'disabled' => ($controller_name == 'si_dashboard') ? true : false,
                ),
                array(
                    'href' => $this->context->link->getAdminLink('AdminSearchIntegratorConfig'),
                    'desc' => $this->trans('Configuration', [], 'Modules.Udsearchintegrator.Udsearchintegrator'),
                    'icon' => 'process-icon-cog icon-cog',
                    'disabled' => ($controller_name == 'si_config') ? true : false,
                ),
                array(
                    'href' => $this->context->link->getAdminLink('AdminSearchIntegratorDebug'),
                    'desc' => $this->trans('Debug', [], 'Modules.Udsearchintegrator.Udsearchintegrator'),
                    'icon' => 'process-icon-book icon-book',
                    'disabled' => ($controller_name == 'si_debug') ? true : false,
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
            'IS_DEBUG' => (isset($config['IS_DEBUG']) ? $config['IS_DEBUG'] : $current['IS_DEBUG']),
            'IS_PRODUCTION' => (isset($config['IS_PRODUCTION']) ? $config['IS_PRODUCTION'] : $current['IS_PRODUCTION']),
            'IMG_SIZE' => (isset($config['IMG_SIZE']) ? $config['IMG_SIZE'] : $current['IMG_SIZE']),
            'SERVER_WHITELIST' => (isset($config['SERVER_WHITELIST']) ? $config['SERVER_WHITELIST'] : $current['SERVER_WHITELIST']),
            'MASTER_TOKEN' => (isset($config['MASTER_TOKEN']) ? $config['MASTER_TOKEN'] : ($current['MASTER_TOKEN'] ?? '')),
            'PRICELIST_MODE' => (isset($config['PRICELIST_MODE']) ? $config['PRICELIST_MODE'] : ($current['PRICELIST_MODE'] ?? 'auto')),
            'CURRENCY_LOCATION_MAP' => (isset($config['CURRENCY_LOCATION_MAP']) ? $config['CURRENCY_LOCATION_MAP'] : ($current['CURRENCY_LOCATION_MAP'] ?? '')),
            'SEARCH_URL' => (isset($config['SEARCH_URL']) ? $config['SEARCH_URL'] : ($current['SEARCH_URL'] ?? '')),
            'TRIGGER_CLICKED' => (isset($config['TRIGGER_CLICKED']) ? $config['TRIGGER_CLICKED'] : ($current['TRIGGER_CLICKED'] ?? '')),
            'TRIGGER_TYPING' => (isset($config['TRIGGER_TYPING']) ? $config['TRIGGER_TYPING'] : ($current['TRIGGER_TYPING'] ?? '')),
        );

        if(empty($updatedConfig['MASTER_TOKEN'])) $updatedConfig['MASTER_TOKEN'] = Tools::passwdGen(16);

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

    public function hookActionValidateOrderAfter($params)
    {
        $order = $params['order'];
        $products = array();
        $product_count = 0;
        foreach($order->getOrderDetailList() as $product){
            $product_count += $product['product_quantity'];
            $products[] = array(
                'product_id' => $product['product_id'],
                'product_name' => $product['product_name'],
                'quantity' => $product['product_quantity'],
                'price' => $product['product_price'],
            );
        }

        $token = SISession::getTokenByCustomer($order->id_customer);
        $data = array(
            'event' => 'purchase',
            'order_id' => $order->id,
            'total_amount' => $order->total_paid_tax_incl,
            'total_quantity' => $product_count,
            'data' => $products,
            'user_session' => $token,
        );

        // send to API
        self::sendAPI($data);
    }

    public function hookactionValidateOrder($params)
    {
        $this->hookActionValidateOrderAfter($params);
    }

    public function hookActionCartUpdateQuantityBefore($params)
    {
        if($params['operator'] != 'up') return;

        $cartProductQuantity = $params['cart']->getProductQuantity(
            $params['product']->id,
            $params['id_product_attribute'],
            (int) $params['id_customization'],
            (int) $params['id_address_delivery']
        );

        if(!empty($cartProductQuantity['quantity'])){
            $productQuantity = Product::getQuantity($params['product']->id, $params['id_product_attribute'], null, $params['cart'], false);
            $availableOutOfStock = Product::isAvailableWhenOutOfStock(StockAvailable::outOfStock($params['product']->id));
            $updateQuantity = '+ ' . $params['quantity'];
            $newProductQuantity = $productQuantity - $params['quantity'];

            if ($newProductQuantity < 0 && !$availableOutOfStock) {
                return;
            }
        } else {

            $sql = 'SELECT stock.out_of_stock, IFNULL(stock.quantity, 0) as quantity
                        FROM ' . _DB_PREFIX_ . 'product p
                        ' . Product::sqlStock('p', $params['id_product_attribute'], true, $params['shop']) . '
                        WHERE p.id_product = ' . $params['product']->id;

            $result2 = Db::getInstance()->getRow($sql);

            // Quantity for product pack
            if (Pack::isPack($params['product']->id)) {
                $result2['quantity'] = Pack::getQuantity($params['product']->id, $params['id_product_attribute'], null, $params['cart'], false);
            }

            if (isset($result2['out_of_stock']) && !Product::isAvailableWhenOutOfStock((int) $result2['out_of_stock'])) {
                if ((int) $params['quantity'] > $result2['quantity']) {
                    return;
                }
            }

            if (!empty($params['id_product_attribute'])) {
                if(version_compare(_PS_VERSION_, '8.0.0', '>=')){
                    $minimal_quantity = (int) ProductAttribute::getAttributeMinimalQty($params['id_product_attribute']);
                } else {
                    $minimal_quantity = (int) Attribute::getAttributeMinimalQty($params['id_product_attribute']);
                }
            } else {
                $minimal_quantity = (int) $params['product']->minimal_quantity;
            }

            if ((int) $params['quantity'] < $minimal_quantity) {
                return;
            }
        }

        $specific_price_output = null;
        $price = Product::getPriceStatic($params['product']->id, true, $params['id_product_attribute'], 2, null, false, true, 1, false, null, null, null, $specific_price_output, true, true, null, true, $params['id_customization']);

        $token = '';
        if($params['cart']->id_customer){
            $token = SISession::getTokenByCustomer($params['cart']->id_customer);
        } else {
            $token = SISession::getTokenByGuest($params['cart']->id_guest);
        }

        $data = array(
            'event' => 'add_to_cart',
            'product_id' => $params['product']->id,
            'product_name' => $params['product']->name,
            'quantity' => $params['quantity'],
            'price' => $price,
            'user_session' => $token,
        );

        // send to API
        self::sendAPI($data);
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
                //$parsedUrl = parse_url($config['SCRIPT_SRC']);

                //$queryParams = [];
                //if (isset($parsedUrl['query'])) {
                    //parse_str($parsedUrl['query'], $queryParams);
                //}

                //$queryParams['host'] = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
                //$queryParams['lang'] = $this->context->language->iso_code;

                //$newUrl = (isset($parsedUrl['scheme']) ? $parsedUrl['scheme'] . '://' : '') .
                        //(isset($parsedUrl['host']) ? $parsedUrl['host'] : '') .
                        //(isset($parsedUrl['path']) ? $parsedUrl['path'] : '') .
                        //'?' . http_build_query($queryParams);
                $token = SISession::getCSRF();
                $js_defs = [
                    'udsi_cart_url' => $this->context->link->getPageLink('cart', true),
                    'udsi_options' => array(
                        'ajax_url' => $this->context->link->getModuleLink($this->name, 'session'),
                        'token' => $token,
                    ),
                    // merged into window.UdunySearch by views/js/front.js (keeps user_session intact)
                    'udsi_search_config' => array_filter(array(
                        'url' => $config['SEARCH_URL'] ?? '',
                        'lang' => $this->context->language->iso_code,
                        'trigger_elements' => array_filter(array(
                            'clicked' => $config['TRIGGER_CLICKED'] ?? '',
                            'typing' => $config['TRIGGER_TYPING'] ?? '',
                        )),
                    )),
                ];
                if (SIPriceContext::shouldIncludePrices(SIPriceContext::getContexts((int) $this->context->shop->id))) {
                    $js_defs['UdunyStoreContext'] = SIPriceContext::resolveVisitorContext($this->context);
                }
				Media::addJsDef($js_defs);
                $this->context->controller->registerJavascript('searchintegrator', $config['SCRIPT_SRC'] . '?lang=' . $this->context->language->iso_code, [
                    'server' => 'remote',
                    'position' => 'bottom', // or 'head'
                    'priority' => 100, // optional
                ]);

                $this->context->controller->registerJavascript('udsi_front_js', $this->_path . 'views/js/front.js');
                // $this->context->controller->registerJavascript('udsi_gtag_js', $this->_path . 'views/js/gtag-custom.js');

            }
        }
    }

    public static function sendAPI($data)
    {
        $config = self::getConfig();
        if(!Validate::isAbsoluteUrl($config['SCRIPT_SRC'])) return false;

        $url = parse_url($config['SCRIPT_SRC']);
        $api_url = $url['scheme']."://".$url['host']."/api/store-event-cms";
		$current_scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $current_host = $_SERVER['HTTP_HOST'];
		$current_origin = $current_scheme . '://' . $current_host;

        self::logger('DEBUG - ' . $data['event'] . ': ' . json_encode($data));
        try {
            $curl = curl_init();

            if ($curl === false) {
                self::logger('ERROR - cURL initialization failed.');
                throw new Exception('cURL initialization failed.');
            }

            curl_setopt($curl, CURLOPT_URL, $api_url);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
            	'Origin: ' . $current_origin
            ));

            $response = curl_exec($curl);

            if ($response === false) {
                self::logger('ERROR - cURL request failed: ' . curl_error($curl));
                throw new Exception('cURL request failed: ' . curl_error($curl));
            }

            if (empty($response)) {
                self::logger('ERROR - Empty response.');
                return false;
            }

            $result = json_decode($response, true);
            self::logger('DEBUG - Response: ' . json_encode($result));

            if ($data === null) {
                self::logger('ERROR - Error decoding JSON: ' . json_last_error_msg());
                throw new Exception('Error decoding JSON: ' . json_last_error_msg());
            }

        } catch (Exception $e) {
            $error[] = $e->getMessage();
            self::logger('ERROR - '. $e->getMessage());
        } finally {
            if (isset($curl)) {
                curl_close($curl);
            }

            if(empty($error)){
                return true;
            }

            return false;
        }
    }

    public static function logger($message, $force_log = false)
    {
        $config = self::getConfig();
        if(!$config['IS_DEBUG'] || $force_log) return;

        $logFile = dirname(__FILE__) . '/debug/' . date('Y-m-d') . '_uduny.log';
        $logEntry = date('[Y-m-d H:i:s]') . ' ' . trim($message) . PHP_EOL;

        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
}
