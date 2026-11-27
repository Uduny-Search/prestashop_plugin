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

/**
 * Resolves multi-shop (location) / multi-currency price contexts.
 * PrestaShop port of the Magento UnusualDope\Uduny\Model\PriceContextResolver,
 * producing the same `prices` array: [{ location, currency, price, sale }].
 */
class SIPriceContext
{
    public const MODE_AUTO = 'auto';
    public const MODE_ALWAYS = 'always';
    public const MODE_NEVER = 'never';

    /** ISO currency -> primary country fallback (same table as Magento module) */
    public const CURRENCY_COUNTRY_FALLBACK = array(
        'USD' => 'US', 'GBP' => 'GB', 'CHF' => 'CH', 'JPY' => 'JP', 'CAD' => 'CA',
        'AUD' => 'AU', 'NZD' => 'NZ', 'SEK' => 'SE', 'NOK' => 'NO', 'DKK' => 'DK',
        'PLN' => 'PL', 'CZK' => 'CZ', 'HUF' => 'HU', 'RON' => 'RO', 'BGN' => 'BG',
        'BRL' => 'BR', 'MXN' => 'MX', 'CNY' => 'CN', 'HKD' => 'HK', 'SGD' => 'SG',
        'KRW' => 'KR', 'INR' => 'IN', 'TRY' => 'TR', 'ZAR' => 'ZA', 'AED' => 'AE',
        'SAR' => 'SA', 'ILS' => 'IL', 'EUR' => 'EU',
    );

    protected static $contextsCache = array();

    /**
     * Parse "EUR=IT" lines (newline or comma separated) into [CUR => CC].
     */
    public static function getCurrencyLocationMap()
    {
        $config = Udsearchintegrator::getConfig();
        $raw = (string) ($config['CURRENCY_LOCATION_MAP'] ?? '');
        $map = array();
        foreach (preg_split('/[\r\n,]+/', $raw) as $line) {
            $parts = explode('=', trim($line));
            if (count($parts) !== 2) continue;
            $cur = strtoupper(trim($parts[0]));
            $cc = strtoupper(trim($parts[1]));
            if (preg_match('/^[A-Z]{3}$/', $cur) && preg_match('/^[A-Z]{2}$/', $cc)) {
                $map[$cur] = $cc;
            }
        }

        return $map;
    }

    public static function getMode()
    {
        $config = Udsearchintegrator::getConfig();
        $mode = $config['PRICELIST_MODE'] ?? self::MODE_AUTO;

        return in_array($mode, array(self::MODE_AUTO, self::MODE_ALWAYS, self::MODE_NEVER), true) ? $mode : self::MODE_AUTO;
    }

    protected static function getShopCountryIso($id_shop)
    {
        $id_country = (int) Configuration::get('PS_COUNTRY_DEFAULT', null, null, $id_shop);
        $iso = $id_country ? Country::getIsoById($id_country) : '';

        return $iso ? strtoupper($iso) : 'US';
    }

    /**
     * Discover all (location, currency) contexts; primary shop first.
     *
     * @return array<int, array{id_shop:int, id_country:int, id_currency:int, location:string, currency:string, is_primary:bool}>
     */
    public static function getContexts($primary_shop_id)
    {
        $primary_shop_id = (int) $primary_shop_id;
        if (isset(self::$contextsCache[$primary_shop_id])) {
            return self::$contextsCache[$primary_shop_id];
        }

        $shop_ids = Shop::isFeatureActive() ? Shop::getShops(true, null, true) : array($primary_shop_id);
        $shop_ids = array_map('intval', array_values($shop_ids));
        if (in_array($primary_shop_id, $shop_ids, true)) {
            $shop_ids = array_merge(array($primary_shop_id), array_diff($shop_ids, array($primary_shop_id)));
        }

        $custom_map = self::getCurrencyLocationMap();
        $contexts = array();
        $seen = array();

        // Pass 1: each shop's default currency + default country
        foreach ($shop_ids as $id_shop) {
            $id_currency = (int) Configuration::get('PS_CURRENCY_DEFAULT', null, null, $id_shop);
            $currency = new Currency($id_currency);
            if (!Validate::isLoadedObject($currency)) continue;

            $location = self::getShopCountryIso($id_shop);
            $key = $location . ':' . strtoupper($currency->iso_code);
            if (isset($seen[$key])) continue;
            $seen[$key] = true;
            $contexts[] = self::buildContext($id_shop, $location, $currency, $id_shop === $primary_shop_id);
        }

        // Pass 2: additional active currencies of each shop
        foreach ($shop_ids as $id_shop) {
            $default_currency = (int) Configuration::get('PS_CURRENCY_DEFAULT', null, null, $id_shop);
            $shop_location = self::getShopCountryIso($id_shop);
            foreach (Currency::getCurrenciesByIdShop($id_shop) as $row) {
                if ((int) $row['id_currency'] === $default_currency || empty($row['active']) || !empty($row['deleted'])) continue;

                $currency = new Currency((int) $row['id_currency']);
                if (!Validate::isLoadedObject($currency) || (float) $currency->conversion_rate <= 0) continue;

                $iso = strtoupper($currency->iso_code);
                $location = $custom_map[$iso] ?? (self::CURRENCY_COUNTRY_FALLBACK[$iso] ?? $shop_location);
                $key = $location . ':' . $iso;
                if (isset($seen[$key])) continue;
                $seen[$key] = true;
                $contexts[] = self::buildContext($id_shop, $location, $currency, false);
            }
        }

        return self::$contextsCache[$primary_shop_id] = $contexts;
    }

    protected static function buildContext($id_shop, $location, Currency $currency, $is_primary)
    {
        // Country used for tax / country-specific prices; pseudo locations (e.g. EU) fall back to shop country
        $id_country = (int) Country::getByIso($location);
        if (!$id_country) {
            $id_country = (int) Configuration::get('PS_COUNTRY_DEFAULT', null, null, $id_shop);
        }

        return array(
            'id_shop' => (int) $id_shop,
            'id_country' => $id_country,
            'id_currency' => (int) $currency->id,
            'location' => (string) $location,
            'currency' => strtoupper($currency->iso_code),
            'is_primary' => (bool) $is_primary,
        );
    }

    public static function shouldIncludePrices(array $contexts)
    {
        $mode = self::getMode();
        if ($mode === self::MODE_NEVER) return false;
        if ($mode === self::MODE_ALWAYS) return !empty($contexts);

        return count($contexts) > 1;
    }

    /**
     * Regular + sale price (tax incl.) for one context. sale = 0 when not discounted.
     * Product::priceCalculation applies currency conversion, specific prices
     * (per shop/currency/country/group) and catalog price rules.
     */
    public static function resolvePrice($id_product, $id_product_attribute, array $ctx)
    {
        $id_group = (int) Configuration::get('PS_UNIDENTIFIED_GROUP', null, null, $ctx['id_shop']);
        if (!$id_group) $id_group = 1;

        $calc = function ($use_reduction) use ($id_product, $id_product_attribute, $ctx, $id_group) {
            $specific_price = null;

            return (float) Product::priceCalculation(
                (int) $ctx['id_shop'],
                (int) $id_product,
                $id_product_attribute ? (int) $id_product_attribute : null,
                (int) $ctx['id_country'],
                0,
                '',
                (int) $ctx['id_currency'],
                $id_group,
                1,
                true,
                2,
                false,
                $use_reduction,
                true,
                $specific_price,
                true
            );
        };

        $price = round($calc(false), 2);
        $final = round($calc(true), 2);
        $sale = ($final > 0 && $final < $price) ? $final : 0.0;

        return array('price' => $price, 'sale' => $sale);
    }

    /**
     * Build `prices` array for a product/combination; skips shops the product is not associated with.
     */
    public static function buildPricesArray($id_product, $id_product_attribute, array $contexts)
    {
        static $shopAssoc = array();
        $prices = array();
        foreach ($contexts as $ctx) {
            $key = (int) $id_product . '-' . (int) $ctx['id_shop'];
            if (!isset($shopAssoc[$key])) {
                $shopAssoc[$key] = (bool) Db::getInstance()->getValue(
                    'SELECT 1 FROM `' . _DB_PREFIX_ . 'product_shop` WHERE id_product = ' . (int) $id_product . ' AND id_shop = ' . (int) $ctx['id_shop']
                );
            }
            if (!$shopAssoc[$key]) continue;

            $resolved = self::resolvePrice($id_product, $id_product_attribute, $ctx);
            $prices[] = array(
                'location' => $ctx['location'],
                'currency' => $ctx['currency'],
                'price' => $resolved['price'],
                'sale' => $resolved['sale'],
            );
        }

        return array_values($prices);
    }

    /**
     * Per (location, currency) cheapest effective variant price for the parent product.
     */
    public static function aggregateFromVariants(array $variants)
    {
        $byKey = array();
        foreach ($variants as $variant) {
            foreach ((array) ($variant['prices'] ?? array()) as $entry) {
                $key = $entry['location'] . ':' . $entry['currency'];
                $effective = ($entry['sale'] > 0 && $entry['sale'] < $entry['price']) ? $entry['sale'] : $entry['price'];
                if (!isset($byKey[$key]) || $effective < $byKey[$key]['_effective']) {
                    $byKey[$key] = $entry;
                    $byKey[$key]['_effective'] = $effective;
                }
            }
        }

        $result = array();
        foreach ($byKey as $row) {
            unset($row['_effective']);
            $result[] = $row;
        }

        return $result;
    }

    /**
     * Visitor { country, currency } matching an exported feed context.
     */
    public static function resolveVisitorContext(Context $context)
    {
        $id_shop = (int) $context->shop->id;
        $shop_location = self::getShopCountryIso($id_shop);
        $default_currency = new Currency((int) Configuration::get('PS_CURRENCY_DEFAULT', null, null, $id_shop));
        $default_iso = strtoupper((string) $default_currency->iso_code);
        $currency = (isset($context->currency) && Validate::isLoadedObject($context->currency))
            ? strtoupper($context->currency->iso_code)
            : $default_iso;

        if ($currency === $default_iso) {
            return array('country' => $shop_location, 'currency' => $currency);
        }

        foreach (self::getContexts($id_shop) as $ctx) {
            if ($ctx['currency'] === $currency) {
                return array('country' => $ctx['location'], 'currency' => $currency);
            }
        }

        $map = self::getCurrencyLocationMap();

        return array(
            'country' => $map[$currency] ?? (self::CURRENCY_COUNTRY_FALLBACK[$currency] ?? $shop_location),
            'currency' => $currency,
        );
    }
}

