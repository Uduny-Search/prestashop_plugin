<?php
require_once(__DIR__ . '/Array2XML/src/Constants.php');
require_once(__DIR__ . '/Array2XML/src/InitTrait.php');
require_once(__DIR__ . '/Array2XML/src/Array2XML.php');

use LaLit\Array2XML;

class SIGenerator
{
    public const PRODUCT_NONACTIVE = 0;
    public const PRODUCT_ACTIVE = 1;
    public const PRODUCT_ALL = 2;

    public const PRODUCT_ON_STOCK_AVAILABLE = 1;
    public const PRODUCT_ON_STOCK = 2;
    public const PRODUCT_ALL_STOCK = 3;

    protected $config;
    protected $products;
    protected $context;

    public $show_total = false;
    public $limit = 100;
    public $offset = 0;

    public function __construct($id_udsearch_filters, $limit = 100, $offset = 0)
    {
        $this->context = Context::getContext();
        $this->limit = $limit;
        $this->offset = $offset;
        $this->config = $this->getFilters($id_udsearch_filters);
        $this->products = $this->getProducts();
    }

    public function getFilters($id_udsearch_filters)
    {
        $sql = 'SELECT * 
                FROM `'._DB_PREFIX_.'udsearch_filters`
                WHERE id_udsearch_filters = '. $id_udsearch_filters;

        $result = DB::getInstance()->getRow($sql);
        if(isset($result['filter_category'])) $result['filter_category'] = json_decode($result['filter_category'], true);
        if(isset($result['filter_brand'])) $result['filter_brand'] = json_decode($result['filter_brand'], true);
        
        return $result;
    }

    public function getProducts($all = false)
    {
        if(empty($this->config)) return array();

        $sql = 'SELECT p.id_product
                FROM `'._DB_PREFIX_.'category_product` cp
                    LEFT JOIN `'._DB_PREFIX_.'product` p ON cp.id_product = p.id_product
                    LEFT JOIN `'._DB_PREFIX_.'stock_available` sa ON sa.id_product = p.id_product
                        AND sa.id_product_attribute = 0
                WHERE 1=1';
        
        switch($this->config['filter_active']){
            case self::PRODUCT_ALL: {
                break;
            }
            case self::PRODUCT_ACTIVE: {
                $sql .= ' AND p.active = 1';
                break;
            }
            case self::PRODUCT_NONACTIVE: {
                $sql .= ' AND p.active = 0';
                break;
            }
        }

        switch($this->config['filter_stock']){
            case self::PRODUCT_ON_STOCK_AVAILABLE: {
                if(Configuration::get('PS_ORDER_OUT_OF_STOCK') == 0){
                    $sql .= ' AND (sa.quantity > 0 OR sa.out_of_stock = 1)';
                } // if set as can always order, skip this filter
                break;
            }
            case self::PRODUCT_ON_STOCK: {
                $sql .= ' AND sa.quantity > 0';
                break;
            }
            case self::PRODUCT_ALL_STOCK: {
                break;
            }
        }

        if(!empty($this->config['filter_category'])){
            $sql .= ' AND cp.id_category IN ('. implode(',', $this->config['filter_category']) .')';
        }
        if(!empty($this->config['filter_brand'])){
            $sql .= ' AND p.id_manufacturer IN ('. implode(',', $this->config['filter_brand']) . ')';
        }

        $sql .= ' GROUP BY p.id_product';

        if(!$all){
            $sql .= ' LIMIT '. $this->offset . ',' . $this->limit;
        } else {
            return count(DB::getInstance()->executeS($sql));
        }

        $products = [];
        foreach(DB::getInstance()->executeS($sql) as $product){
            $products[] = new Product($product['id_product'], false, $this->context->language->id ?? Configuration::get('PS_LANG_DEFAULT'));
        }

        return $products;
    }

    public function getJson()
    {
        $id_lang = $this->context->language->id ?? Configuration::get('PS_LANG_DEFAULT');
        $json = array();
        foreach($this->products as $product){
            $cover = Image::getCover($product->id);
            $categories = array();
            foreach(Product::getProductCategoriesFull($product->id, $id_lang) as $category){
                $categories[] = $category['name'];
            }

            $combinations = array();
            foreach(Product::getAttributesInformationsByProduct($product->id) as $attribute){
                $group = Tools::str2url($attribute['group']);
                if(empty($combinations[$group])){
                    $combinations[$group] = array(
                        'name' => $attribute['group'],
                        'options' => array(),
                    );
                }

                $combinations[$group]['options'][] = $attribute['attribute'];
            }

            foreach(Product::getFrontFeaturesStatic($id_lang, $product->id) as $feature){
                $name = Tools::str2url($feature['name']);
                if(empty($combinations[$name])){
                    $combinations[$name] = array(
                        'name' => $feature['name'],
                        'options' => array(),
                    );
                }
                
                $combinations[$name]['options'][] = $feature['value'];
            }

            $variants = array();
            foreach(Product::getProductAttributesIds($product->id) as $pa){
                $attr_combinations = array();
                foreach(Product::getAttributesParams($product->id, $pa['id_product_attribute']) as $attr_combination){
                    $attr_combinations[] = array(
                        'group' => $attr_combination['id_attribute_group'],
                        'value' => $attr_combination['id_attribute'],
                    );
                }
                $variants[] = array(
                    'id_variant' => $pa['id_product_attribute'],
                    'combinations' => $attr_combinations,
                );
            }

            $arr = array(
                'id' => $product->id,
                'name' => $product->name,
                'url' => $product->getLink(),
                'image' => $this->context->link->getImageLink($product->link_rewrite[$id_lang] ?? $product->link_rewrite, (int)($cover['id_image'] ?? 0), ImageType::getImageTypeById($this->config['IMG_SIZE'])),
                'description' => $product->description,
                'date_created' => $product->date_add,
                'price' => Product::getPriceStatic($product->id, true, null, 2, null, false, false),
                'sale_price' => Product::getPriceStatic($product->id, true, null, 2),
                'quantity' => StockAvailable::getQuantityAvailableByProduct($product->id),
                'categories' => $categories,
                'attributes' => $combinations,
                'variants' => $variants,
            );
            $json[] = $arr;
        }

        $result = array();
        if($this->show_total){
            $result['total'] = $this->getProducts(true);
        }
        $result['items'] = $json;

        return $result;
    }

    public function getXML()
    {
        $json = $this->getJson();
        $export = array();
        foreach($json as $item){
            $categories = array();
            if(!empty($item['categories'])){
                $categories[] = array(
                    'category' => array(
                        'name' => $item['categories']['name'],
                        'slug' => $item['categories']['slug'],
                    )
                );
            }

            $export[] = array(
                'item' => array(
                    'id' => $item['id'],
                    'name' => $item['name'],
                    'url' => $item['url'],
                    'image' => $item['image'],
                    'description' => $item['description'],
                    'date_created' => $item['date_created'],
                    'price' => $item['price'],
                    'sale_price' => $item['sale_price'],
                    'categories' => $categories,
                ),
            );
        }

        $xml = Array2XML::createXML('feed', $export, array('name' => 'feed'))->saveXML();

        return $xml;
    }
}