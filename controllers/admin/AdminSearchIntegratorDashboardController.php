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

class AdminSearchIntegratorDashboardController extends ModuleAdminController
{
    public $className = 'AdminSearchIntegratorDashboardController';
    public $name;

    public function __construct()
    {
        $this->name = 'si_dashboard';
        $this->bootstrap = true;
        $this->toolbar_title = "Search Integrator Filters";

        parent::__construct();
    }

    public function initContent()
    {
        if(Tools::isSubmit('ajax')) return $this->displayAjax();

        if(Tools::isSubmit('add'.$this->name) || Tools::isSubmit('update'.$this->name)){
            $this->context->smarty->assign([
                'content' => $this->renderForm(),
            ]);
            return;
        }

        if(Tools::isSubmit('delete'.$this->name)){
            if(DB::getInstance()->delete('udsearch_filters', 'id_udsearch_filters = '. Tools::getValue('id_udsearch_filters', 0))){
                $this->context->cookie->__set($this->name, json_encode(array('success' => true, 'message' => $this->trans('Search Filters Deleted!', [], 'Modules.Udsearchintegrator.AdminSearchIntegratorDashboard'))));
            } else {
                $this->context->cookie->__set($this->name, json_encode(array('success' => false, 'message' => $this->trans('There is something wrong when deleting Search Filters!', [], 'Modules.Udsearchintegrator.AdminSearchIntegratorDashboard'))));
            }
            
            return Tools::redirectAdmin($this->context->link->getAdminLink('AdminSearchIntegratorDashboard'));
        }
        
        $this->renderWarning();

        $custom_link = array();

        $this->context->smarty->assign('nav_menu', $this->module->renderNavbarMenu($this->name, $custom_link));

        $this->context->controller->addJS($this->module->getLocalPath() . 'views/js/back.js');

        // $this->context->controller->addCSS($this->module->getLocalPath() . 'views/css/config.css');

        $custom_link = array(
            array(
                'href' => self::$currentIndex . '&add' . $this->name . '&token=' . $this->token,
                'desc' => $this->trans('New Filter', [], 'Modules.Udsearchintegrator.AdminSearchIntegratorDashboard'),
                'icon' => 'process-icon-plus',
                'disabled' => false,
            ),
        );
        $alert = array();

        $this->context->smarty->assign('nav_menu', $this->module->renderNavbarMenu($this->name, $custom_link, $alert));

        $this->context->smarty->assign([
            'content' => $this->context->smarty->fetch($this->module->getLocalPath() . 'views/templates/admin/nav.tpl') . $this->renderContent(),
        ]);
    }

    public function renderContent(){
        if(Tools::isSubmit('ajax')) return;

        $sql = 'SELECT *, slug as `endpoint_url`
                FROM `'._DB_PREFIX_.'udsearch_filters`
                WHERE 1=1 ';

        $orderby = " ORDER BY id_udsearch_filters asc";

        $filters = DB::getInstance()->executeS($sql . $orderby);

        $helper = new HelperList();
        $helper->identifier = 'id_udsearch_filters';
        $helper->title = $this->trans('Search Integrator Filters', [], 'Modules.Udsearchintegrator.AdminSearchIntegratorDashboard');
        $helper->toolbar_btn['new'] = [
            'href' => self::$currentIndex . '&add' . $this->name . '&token=' . $this->token,
            'desc' => $this->trans('New', [], 'Admin.Actions'),
            'icon' => 'process-icon-new',
        ];
        $helper->bootstrap = true;
        $helper->shopLinkType = '';
        $helper->table = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminSearchIntegratorDashboard');
        $helper->currentIndex = AdminController::$currentIndex;
        if($orderby == ' ORDER BY id_udsearch_filters asc'){
            $helper->position_identifier = 'id_udsearch_filters';
            $helper->orderBy = 'position';
            $helper->orderWay = 'asc';
        }
        $helper->actions = ['edit','delete'];
        $helper->_default_pagination = 10;
        $helper->_pagination = array(10, 20, 50);

        $pagination = (int) Tools::getValue($this->name . '_pagination',10);

        $helper->tpl_vars['icon'] = 'icon-money';
        $helper->listTotal = count($filters);
        $page = Tools::getValue('submitFilter' . $this->name,1);
        $total_page = ceil($helper->listTotal / $pagination);
        $page = max($page, 1);
        $page = min($page, $total_page);
        $helper->page = $page;
        $offset = ($page - 1) * $pagination;
        if ($offset < 0) $offset = 0;
        $filters = array_slice($filters, $offset, $pagination);

        $fields_list = array(
            'id_udsearch_filters' => array(
                'title' => $this->trans('ID', [], 'Modules.Udsearchintegrator.AdminSearchIntegratorDashboard'),
                'class' => 'fixed-width-xs',
                'align' => 'center',
                'search' => false,
                'remove_onclick' => true,
            ),
            'name' => array(
                'title' => $this->trans('Name', [], 'Modules.Udsearchintegrator.AdminSearchIntegratorDashboard'),
                'class' => 'fixed-width-lg',
                'search' => false,
                'remove_onclick' => true,
            ),
            'filter_active' => array(
                'title' => $this->trans('Active Filter', [], 'Modules.Udsearchintegrator.AdminSearchIntegratorDashboard'),
                'align' => 'center',
                'callback' => 'renderActiveFilter',
                'class' => 'fixed-width-md',
            ),
            'filter_stock' => array(
                'title' => $this->trans('Stock Filter', [], 'Modules.Udsearchintegrator.AdminSearchIntegratorDashboard'),
                'align' => 'center',
                'callback' => 'renderStockFilter',
                'class' => 'fixed-width-md',
            ),
            'filter_brand' => array(
                'title' => $this->trans('Brand Filter', [], 'Modules.Udsearchintegrator.AdminSearchIntegratorDashboard'),
                'callback' => 'renderBrandFilter',
                'search' => false,
                'class' => 'fixed-width-lg',
            ),
            'filter_category' => array(
                'title' => $this->trans('Category Filter', [], 'Modules.Udsearchintegrator.AdminSearchIntegratorDashboard'),
                'class' => 'fixed-width-lg',
                'search' => false,
                'callback' => 'renderCategoryFilter',
            ),
            'endpoint_url' => array(
                'title' => $this->trans('Endpoint', [], 'Modules.Udsearchintegrator.AdminSearchIntegratorDashboard'),
                'class' => 'fixed-width-md',
                'search' => false,
                'remove_onclick' => true,
                'callback' => 'renderEndpoint',
            ),
        );

        return $helper->generateList($filters,$fields_list);
    }

    public function renderForm(){
        $id_udsearch_filters = Tools::getValue('id_udsearch_filters', 0);

        $config = DB::getInstance()->getRow('SELECT * FROM `'._DB_PREFIX_.'udsearch_filters` WHERE id_udsearch_filters = '. $id_udsearch_filters);
        $fields_form = array([
            'form' => array(
                'legend' => array(
                    'title' => $this->trans('Configure Search Filters', [], 'Modules.Udsearchintegrator.AdminSearchIntegratorDashboard'),
                    'icon' => 'icon-cog',
                ),
                'input' => array(
                    array(
                        'type' => 'hidden',
                        'name' => 'id_udsearch_filters',
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->trans('Endpoint Slug', [], 'Modules.Udsearchintegrator.AdminSearchIntegratorDashboard'),
                        'name' => 'SLUG',
                        'desc' => $this->trans('Endpoint for running this filter', [], 'Modules.Udsearchintegrator.AdminSearchIntegratorDashboard'),
                        'required' => true,
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->trans('Name', [], 'Modules.Udsearchintegrator.AdminSearchIntegratorDashboard'),
                        'name' => 'NAME',
                        'desc' => $this->trans('Filter name for backoffice', [], 'Modules.Udsearchintegrator.AdminSearchIntegratorDashboard'),
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->trans('Product Status', [], 'Modules.Udsearchintegrator.AdminSearchIntegratorDashboard'),
                        'name' => 'FILTER_ACTIVE',
                        'desc' =>  $this->trans('Filter by product status.', [], 'Modules.Udsearchintegrator.AdminSearchIntegratorDashboard'),
                        'options' => array(
                            'query' => array([
                                'key' => SIGenerator::PRODUCT_ALL,
                                'name' => 'All'
                            ],[
                                'key' => SIGenerator::PRODUCT_ACTIVE,
                                'name' => 'Active'
                            ],[
                                'key' => SIGenerator::PRODUCT_NONACTIVE,
                                'name' => 'Not Active'
                            ]),
                            'id' => 'key',
                            'name' => 'name'
                        ),
                        'col' => '5',
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->trans('Product Stock Availability', [], 'Modules.Udsearchintegrator.AdminSearchIntegratorDashboard'),
                        'name' => 'FILTER_STOCK',
                        'desc' =>  $this->trans('Filter by product stock.', [], 'Modules.Udsearchintegrator.AdminSearchIntegratorDashboard'),
                        'options' => array(
                            'query' => array([
                                'key' => SIGenerator::PRODUCT_ON_STOCK_AVAILABLE,
                                'name' => 'Only in stock products including out of stock but available for backorder'
                            ],[
                                'key' => SIGenerator::PRODUCT_ON_STOCK,
                                'name' => 'Only in stock product'
                            ],[
                                'key' => SIGenerator::PRODUCT_ALL_STOCK,
                                'name' => 'All products including out of stock'
                            ]),
                            'id' => 'key',
                            'name' => 'name'
                        ),
                        'col' => '5',
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->trans('Brand', [], 'Modules.Udsearchintegrator.AdminSearchIntegratorDashboard'),
                        'name' => 'FILTER_BRAND[]',
                        'desc' => $this->trans('Filter by brand.', [], 'Modules.Udsearchintegrator.AdminSearchIntegratorDashboard'),
                        'options' => array(
                            'query' => Manufacturer::getManufacturers(),
                            'id' => 'id_manufacturer',
                            'name' => 'name',
                        ),
                        'multiple' => true,
                        'col' => 7,
                    ),
                    array(
                        'type' => 'categories_select',
                        'label' => $this->trans('Category', [], 'Modules.Udsearchintegrator.AdminSearchIntegratorDashboard'),
                        'name' => 'FILTER_CATEGORY',
                        'category_tree' => self::renderTargetTree(!empty($config['filter_category']) ? ((is_array($config['filter_category'])) ? $config['filter_category'] : json_decode($config['filter_category'], true)) : array()),
                        'desc' => $this->trans('Filter by category.', [], 'Modules.Udsearchintegrator.AdminSearchIntegratorDashboard'),
                        'col' => 8,
                    ),
                ),
                'submit' => array(
                    'title' => $this->trans('Save', [], 'Modules.Udsearchintegrator.AdminSearchIntegratorDashboard'),
                ),
            ),
        ]);

        $form = new HelperForm();
        $form->table = $this->name;
        $form->show_cancel_button = true;
        $form->fields_value = array(
            'id_udsearch_filters' => $config['id_udsearch_filters'] ?? 0,
            'NAME' => $config['name'] ?? '',
            'SLUG' => $config['slug'] ?? '',
            'FILTER_BRAND[]' => json_decode($config['filter_brand'] ?? '[]', true),
            'FILTER_CATEGORY' => $config['filter_category'] ?? array(),
            'FILTER_STOCK' => $config['filter_stock'] ?? 1,
            'FILTER_ACTIVE' => $config['filter_active'] ?? SIGenerator::PRODUCT_ACTIVE,
        );

        return $form->generateForm($fields_form);
    }

    public function postProcess()
    {
        if(Tools::isSubmit('submitAdd' . $this->name)){
            $config = array(
                'name' => Tools::getValue('NAME'),
                'slug' => Tools::str2url(Tools::getValue('SLUG')),
                'filter_brand' => json_encode(Tools::getValue('FILTER_BRAND', [])),
                'filter_category' => json_encode(Tools::getValue('FILTER_CATEGORY', [])),
                'filter_stock' => Tools::getValue('FILTER_STOCK', SIGenerator::PRODUCT_ON_STOCK_AVAILABLE),
                'filter_active' => Tools::getValue('FILTER_ACTIVE', SIGenerator::PRODUCT_ALL),
            );

            $result = false;
            if(Tools::getValue('id_udsearch_filters') > 0){
                $result = DB::getInstance()->update('udsearch_filters', $config, 'id_udsearch_filters = ' . Tools::getValue('id_udsearch_filters'));
            } else {
                $result = DB::getInstance()->insert('udsearch_filters', $config);
            }
            
            if ($result) {
                $this->context->cookie->__set($this->name, json_encode(array('success' => true, 'message' => $this->trans('Search Filters Saved!', [], 'Modules.Udsearchintegrator.AdminSearchIntegratorDashboard'))));
            } else {
                $this->context->cookie->__set($this->name, json_encode(array('success' => false, 'message' => $this->trans('There is something wrong when saving Search Filters!', [], 'Modules.Udsearchintegrator.AdminSearchIntegratorDashboard'))));
            }

            Tools::redirectAdmin($this->context->link->getAdminLink('AdminSearchIntegratorDashboard'));
        }
    }

    public function displayAjax()
    {
        if(!Tools::isSubmit('method')) exit;
    }

    public function renderActiveFilter($param){
        switch($param){
            case SIGenerator::PRODUCT_NONACTIVE: {
                return 'Only nonactive';
            }
            case SIGenerator::PRODUCT_ACTIVE: {
                return 'Only active';
            }
            case SIGenerator::PRODUCT_ALL: {
                return 'All product';
            }
            default: {
                return '';
            }
        }
    }

    public function renderStockFilter($param){
        switch($param){
            case SIGenerator::PRODUCT_ON_STOCK_AVAILABLE: {
                return 'In Stock with Backorder';
            }
            case SIGenerator::PRODUCT_ON_STOCK: {
                return 'In Stock';
            }
            case SIGenerator::PRODUCT_ALL_STOCK: {
                return 'All Product';
            }
            default: {
                return '';
            }
        }
    }

    public function renderBrandFilter($param){
        $param = is_array($param) ? $param : json_decode($param, true);
        if(empty($param)) return 'All';

        $context = Context::getContext();
        $brand = array();
        foreach($param as $id){
            $m = new Manufacturer($id, $context->language->id ?? Configuration::get('PS_LANG_DEFAULT'));
            $brand[] = $m->name;
        }

        $html = implode(', ', $brand);
        return $html;
    }

    public function renderCategoryFilter($param){
        $param = is_array($param) ? $param : json_decode($param, true);
        if(empty($param)) return 'All';

        $context = Context::getContext();
        $categories = array();
        foreach($param as $id){
            $c = new Category($id, $context->language->id ?? Configuration::get('PS_LANG_DEFAULT'));
            $categories[] = $c->name;
        }

        $html = implode(', ', $categories);
        return $html;
    }

    public function renderEndpoint($param){
        $btn = array();
        foreach(Language::getLanguages() as $lang){
            $url = _PS_BASE_URL_SSL_ . '/' . $lang['iso_code'] . '/' . Udsearchintegrator::MODULE_ROUTE . '/' . $param;
            $btn[] = '<a href="javascript:void(0)" class="btn clickCopy" data-url="'.$url.'">Copy '.strtoupper($lang['iso_code']).'</a>';
        }
        return implode('</br>', $btn);
    }


    public function renderTargetTree($selected = array()){
        if(!is_array($selected)){
            $selected = [$selected];
        }

        $root = Category::getRootCategory();

        $tree = new HelperTreeCategories('FILTER_CATEGORY'); //The string in param is the ID used by the generated tree
        $tree->setUseCheckBox(true)
            ->setUseSearch(true)
            ->setRootCategory($root->id)
            ->setSelectedCategories($selected) //if you wanted to be pre-carged
            ->setInputName('FILTER_CATEGORY')
            ->setDisabledCategories([]); //Set the name of input. The option "name" of $fields_form doesn't seem to work with "categories_select" type

        return $tree->render();
    }

    public function returnYesNo()
    {
        return array(
            array(
                'id' => 'active_on',
                'value' => 1,
                'label' => $this->trans('Enabled', [], 'Modules.Udsearchintegrator.AdminSearchIntegratorDashboard')
            ),
            array(
                'id' => 'active_off',
                'value' => 0,
                'label' => $this->trans('Disabled', [], 'Modules.Udsearchintegrator.AdminSearchIntegratorDashboard')
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