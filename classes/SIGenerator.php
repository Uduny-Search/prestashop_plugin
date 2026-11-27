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
    public $filters = [];

    public function __construct($id_udsearch_filters, $limit = 100, $offset = 0, $filters = [])
    {
        $this->context = Context::getContext();
        $this->limit = $limit;
        $this->offset = $offset;
        $this->config = $this->getFilters($id_udsearch_filters);
        $this->filters = $filters;
        $this->products = $this->getProducts();
    }

    public function getFilters($id_udsearch_filters)
    {
        $sql = 'SELECT *
                FROM `'._DB_PREFIX_.'udsearch_filters`
                WHERE id_udsearch_filters = '. $id_udsearch_filters;

        $result = DB::getInstance()->getRow($sql);
        if(isset($result['filter_category'])) $result['filter_category'] = json_decode($result['filter_category'], true);
        if(isset($result['filter_excl_category'])) $result['filter_excl_category'] = json_decode($result['filter_excl_category'], true);
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
                WHERE 1=1
                    AND p.id_product > 0';

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
        if(!empty($this->config['filter_excl_category'])){
            $sql .= ' AND p.id_product NOT IN (
                        SELECT DISTINCT id_product
                        FROM `'._DB_PREFIX_.'category_product`
                        WHERE id_category IN ('. implode(',', $this->config['filter_excl_category']) .')
                    )';
        }
        if(!empty($this->config['filter_brand'])){
            $sql .= ' AND p.id_manufacturer IN ('. implode(',', $this->config['filter_brand']) . ')';
        }

        if(!empty($this->filters['modified_from']) && Validate::isDateFormat($this->filters['modified_from'])) {
            $sql .= ' AND p.date_upd > "'. $this->filters['modified_from'] .'"';
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
        $img_size = '';
        $si_config = Udsearchintegrator::getConfig();
        foreach(ImageType::getImagesTypes() as $type){
            if($si_config['IMG_SIZE'] == $type['id_image_type']) $img_size = $type['name'];
        }

        $id_lang = $this->context->language->id ?? Configuration::get('PS_LANG_DEFAULT');

        // multi-currency / multi-shop price list (Uduny `prices` array, same format as Magento module)
        $price_contexts = SIPriceContext::getContexts((int) $this->context->shop->id);
        $include_prices = SIPriceContext::shouldIncludePrices($price_contexts);

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
                      	'slug' => Tools::str2url($attr_combination['group']),
                        'id_group' => $attr_combination['id_attribute_group'],
                        'id_value' => $attr_combination['id_attribute'],
                        'group' => $attr_combination['group'],
                        'value' => $attr_combination['name'],
                    );
                }
                if(empty($attr_combinations)) continue;

                $images = Image::getImages($id_lang, $product->id, $pa['id_product_attribute']);
                $product_variant = new Combination($pa['id_product_attribute']);
                $variant_url = $this->context->link->getProductLink(
                    $product->id,
                    null,
                    null,
                    null,
                    $id_lang,
                    null,
                    $pa['id_product_attribute'],
                    false,
                    false,
                    true
                );

                $variant = array(
                    'variant_id' => $pa['id_product_attribute'],
                    'name' => $product->name,
                  	'url' => $variant_url,
                    'ean' => $product_variant->ean13,
                    'reference' => $product_variant->reference,
                    'price' => Product::getPriceStatic($product->id, true, $pa['id_product_attribute'], 2, null, false, false),
                    'sale' => Product::getPriceStatic($product->id, true, $pa['id_product_attribute'], 2),
                    'quantity' => StockAvailable::getQuantityAvailableByProduct($product->id, $pa['id_product_attribute']),
                    'image' => $this->context->link->getImageLink($product->link_rewrite[$id_lang] ?? $product->link_rewrite, (int)($images[0]['id_image'] ?? ($cover['id_image'] ?? 0)), $img_size),
                    'combinations' => $attr_combinations,
                );
                if ($include_prices) {
                    $variant['prices'] = SIPriceContext::buildPricesArray($product->id, $pa['id_product_attribute'], $price_contexts);
                }
                $variants[] = $variant;
            }

            $images = array();
            foreach(Image::getImages($id_lang, $product->id) as $image){
                $images[] = $this->context->link->getImageLink($product->link_rewrite[$id_lang] ?? $product->link_rewrite, (int)($image['id_image'] ?? 0), $img_size);
            }

            $description = $product->description .
                (!empty($product->reference) ? ' REF: '.$product->reference : '').
                (!empty($product->ean13) ? ' EAN: '.$product->ean13 : '').
                (!empty($product->mpn) ? ' MPN: '.$product->mpn : '').
                (!empty($product->isbn) ? ' ISBN: '.$product->isbn : '').
                (!empty($product->upc) ? ' UPC: '.$product->upc : '');
            $arr = array(
                'id' => $product->id,
                'name' => $product->name,
                'url' => $product->getLink(),
                'ean' => $product->ean13,
                'reference' => $product->reference,
                'image' => $this->context->link->getImageLink($product->link_rewrite[$id_lang] ?? $product->link_rewrite, (int)($cover['id_image'] ?? 0), $img_size),
                'images' => $images,
                'description' => $description,
                'date_created' => $product->date_add,
                'price' => Product::getPriceStatic($product->id, true, null, 2, null, false, false),
                'sale_price' => Product::getPriceStatic($product->id, true, null, 2),
                'quantity' => StockAvailable::getQuantityAvailableByProduct($product->id),
                'categories' => $categories,
                'attributes' => $combinations,
                'variants' => $variants,
            );
            if($arr['price'] == $arr['sale_price']) $arr['sale_price'] = '';
            if ($include_prices) {
                $arr['prices'] = !empty($variants)
                    ? SIPriceContext::aggregateFromVariants($variants)
                    : SIPriceContext::buildPricesArray($product->id, 0, $price_contexts);
            }
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
        $items = array();
        foreach(($json['items'] ?? array()) as $item){
            $categories = array();
            foreach((array) ($item['categories'] ?? array()) as $category_name){
                $categories[] = array('name' => $category_name);
            }

            $row = array(
                'id' => $item['id'],
                'name' => $item['name'],
                'url' => $item['url'],
                'image' => $item['image'],
                'description' => $item['description'],
                'date_created' => $item['date_created'],
                'price' => $item['price'],
                'sale_price' => $item['sale_price'],
                'categories' => array('category' => $categories),
            );
            if (!empty($item['prices'])) {
                $row['prices'] = array('price_entry' => $item['prices']);
            }
            $items[] = $row;
        }

        $export = array('item' => $items);
        if (isset($json['total'])) $export['total'] = $json['total'];

        $xml = Array2XML::createXML('feed', $export)->saveXML();

        return $xml;
    }
}