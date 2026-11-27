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

/**
 * 1.0.8: multi-currency / multi-location pricelist + storefront search settings.
 * Merges new config keys into the existing JSON configuration.
 */
function upgrade_module_1_0_8($module)
{
    $current = json_decode((string) Configuration::get('UDSEARCHINTEGRATOR'), true);
    if (!is_array($current)) $current = array();

    $defaults = array(
        'PRICELIST_MODE' => 'auto',
        'CURRENCY_LOCATION_MAP' => '',
        'SEARCH_URL' => '',
        'TRIGGER_CLICKED' => '',
        'TRIGGER_TYPING' => '',
    );

    return Configuration::updateValue('UDSEARCHINTEGRATOR', json_encode(array_merge($defaults, $current)));
}
