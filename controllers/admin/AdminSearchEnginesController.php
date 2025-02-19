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
 * Class AdminSearchEnginesControllerCore
 *
 * @property SearchEngine|null $object
 */
class AdminSearchEnginesControllerCore extends AdminController
{
    /**
     * AdminSearchEnginesControllerCore constructor.
     *
     * @throws PrestaShopException
     */
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'search_engine';
        $this->className = 'SearchEngine';
        $this->lang = false;

        $this->addRowAction('edit');
        $this->addRowAction('delete');

        $this->context = Context::getContext();

        if (!Tools::getValue('realedit')) {
            $this->deleted = false;
        }

        $this->bulk_actions = [
            'delete' => [
                'text'    => $this->l('Delete selected'),
                'confirm' => $this->l('Delete selected items?'),
                'icon'    => 'icon-trash',
            ],
        ];

        $this->fields_list = [
            'id_search_engine' => ['title' => $this->l('ID'), 'width' => 25],
            'server'           => ['title' => $this->l('Server')],
            'getvar'           => ['title' => $this->l('GET variable'), 'width' => 100],
        ];

        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Referrer'),
            ],
            'input'  => [
                [
                    'type'     => 'text',
                    'label'    => $this->l('Server'),
                    'name'     => 'server',
                    'size'     => 20,
                    'required' => true,
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('$_GET variable'),
                    'name'     => 'getvar',
                    'size'     => 40,
                    'required' => true,
                ],
            ],
            'submit' => [
                'title' => $this->l('Save'),
            ],
        ];

        parent::__construct();
    }

    /**
     * Initialize page header toolbar
     *
     * @throws PrestaShopException
     */
    public function initPageHeaderToolbar()
    {
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['new_search_engine'] = [
                'href' => static::$currentIndex.'&addsearch_engine&token='.$this->token,
                'desc' => $this->l('Add new search engine', null, null, false),
                'icon' => 'process-icon-new',
            ];
        }

        $this->identifier_name = 'server';

        parent::initPageHeaderToolbar();
    }
}
