<?php
/**
 * 2007-2016 PrestaShop
 *
 * thirty bees is an extension to the PrestaShop e-commerce software developed by PrestaShop SA
 * Copyright (C) 2017-2018 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://www.thirtybees.com for more information.
 *
 * @author    thirty bees <contact@thirtybees.com>
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2017-2018 thirty bees
 * @copyright 2007-2016 PrestaShop SA
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

/**
 * Class FaviconControllerCore
 */
class FaviconControllerCore extends FrontController
{
    /** @var string $php_self */
    public $php_self = 'favicon';

    /**
     * Initialize content
     *
     * @return void
     */
    public function init()
    {
        if (Tools::getValue('icon') === 'apple-touch-icon') {
            if (Tools::getIsset('width') && Tools::getIsset('height')) {
                $width = (int)Tools::getValue('width');
                $height = (int)Tools::getValue('height');

                if (file_exists(_PS_IMG_DIR_."favicon/favicon_{$this->context->shop->id}_{$width}_{$height}.png")) {
                    header('Content-Type: image/png');
                    readfile(_PS_IMG_DIR_ . "favicon/favicon_{$this->context->shop->id}_{$width}_{$height}.png");
                    exit;
                }
            }

            if (file_exists(_PS_IMG_DIR_."favicon/favicon_{$this->context->shop->id}_180_180.png")) {
                header('Content-Type: image/png');
                readfile(_PS_IMG_DIR_ . "favicon/favicon_{$this->context->shop->id}_180_180.png");
                exit;
            }
        }

        if (file_exists(_PS_IMG_DIR_."favicon-{$this->context->shop->id}.ico")) {
            header('Content-Type: image/x-icon');
            readfile(_PS_IMG_DIR_ . "favicon-{$this->context->shop->id}.ico");
            exit;
        }

        http_response_code(404);
        exit;
    }
}
