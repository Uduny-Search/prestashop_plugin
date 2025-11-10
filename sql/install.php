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

$sql = array();

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'udsearch_filters` (
    `id_udsearch_filters` int(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255),
    `slug` VARCHAR(255) UNIQUE,
    `filter_active` TINYINT(1) DEFAULT 2,
    `filter_stock` TINYINT(1) DEFAULT 1,
    `filter_brand` TEXT,
    `filter_category` TEXT,
    PRIMARY KEY  (`id_udsearch_filters`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'udsearch_sessions` (
    `id_udsearch_sessions` int(11) NOT NULL AUTO_INCREMENT,
    `id_guest` INT(11) NOT NULL,
    `id_customer` INT(11),
    `uduny_sess` VARCHAR(255) NOT NULL,
    `date_add` DATETIME,
    PRIMARY KEY  (`id_udsearch_sessions`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

foreach ($sql as $query) {
    if (Db::getInstance()->execute($query) == false) {
        return false;
    }
}
