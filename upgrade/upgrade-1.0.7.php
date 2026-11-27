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
 * This function updates your module from previous versions to the version 1.1,
 * usefull when you modify your database, or register a new hook ...
 * Don't forget to create one file per version.
 */
function upgrade_module_1_0_7($module)
{
    $col = Db::getInstance()->executeS("SHOW COLUMNS FROM `" . _DB_PREFIX_ .  "udsearch_filters` LIKE 'filter_excl_category'");

    if ($col) {
        $sql = "ALTER TABLE `" . _DB_PREFIX_ .  "udsearch_filters`
                ADD COLUMN `filter_excl_category` TEXT
                AFTER `filter_category`";

        Db::getInstance()->execute($sql);
    }

    return true;
}
