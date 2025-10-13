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

class UdsearchintegratorCartinfoModuleFrontController extends ModuleFrontController {

    public function init()
    {
        parent::init();
        header('X-Robots-Tag: noindex, nofollow', true);
        $this->ajax = true;
    }

    public function __construct()
    {
        parent::__construct();
    }

    public function postProcess()
    {
        //
    }
}