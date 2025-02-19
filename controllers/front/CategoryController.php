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
 * Class CategoryControllerCore
 */
class CategoryControllerCore extends FrontController
{
    /** string Internal controller name */
    public $php_self = 'category';
    /** @var bool If set to false, customer cannot view the current category. */
    public $customer_access = true;
    /** @var Category Current category object */
    protected $category;
    /** @var int Number of products in the current page. */
    protected $nbProducts;
    /** @var array Products to be displayed in the current page . */
    protected $cat_products;

    /**
     * Sets default media for this controller
     *
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function setMedia()
    {
        parent::setMedia();

        if (!$this->useMobileTheme()) {
            //TODO : check why cluetip css is include without js file
            $this->addCSS(
                [
                    _THEME_CSS_DIR_.'scenes.css'       => 'all',
                    _THEME_CSS_DIR_.'category.css'     => 'all',
                    _THEME_CSS_DIR_.'product_list.css' => 'all',
                ]
            );
        }

        $scenes = Scene::getScenes($this->category->id, $this->context->language->id, true, false);
        if ($scenes && count($scenes)) {
            $this->addJS(_THEME_JS_DIR_.'scenes.js');
            $this->addJqueryPlugin(['scrollTo', 'serialScroll']);
        }

        $this->addJS(_THEME_JS_DIR_.'category.js');
    }

    /**
     * Redirects to canonical or "Not Found" URL
     *
     * @param string $canonicalUrl
     *
     * @throws PrestaShopException
     */
    public function canonicalRedirection($canonicalUrl = '')
    {
        if (Tools::getValue('live_edit')) {
            return;
        }

        if (!Validate::isLoadedObject($this->category) ||
            !$this->category->inShop() ||
            !$this->category->isAssociatedToShop() ||
            (int)$this->category->id === (int)Configuration::get('PS_ROOT_CATEGORY')
        ) {
            $this->redirect_after = '404';
            $this->redirect();
        }

        if (!Tools::getValue('noredirect') && Validate::isLoadedObject($this->category)) {
            parent::canonicalRedirection($this->context->link->getCategoryLink($this->category));
        }
    }

    /**
     * Initializes controller
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     * @see FrontController::init()
     */
    public function init()
    {
        // Get category ID
        $idCategory = (int) Tools::getValue('id_category');
        if (!$idCategory || !Validate::isUnsignedId($idCategory)) {
            $this->errors[] = Tools::displayError('Missing category ID');
        }

        // Instantiate category
        $this->category = new Category($idCategory, $this->context->language->id);

        parent::init();

        // Check if the category is active and return 404 error if is disable.
        if (!$this->category->active) {
            header('HTTP/1.1 404 Not Found');
            header('Status: 404 Not Found');
        }

        // Check if category can be accessible by current customer and return 403 if not
        if (!$this->category->checkAccess($this->context->customer->id)) {
            header('HTTP/1.1 403 Forbidden');
            header('Status: 403 Forbidden');
            $this->errors[] = Tools::displayError('You do not have access to this category.');
            $this->customer_access = false;
        }
    }

    /**
     * Initializes page content variables
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function initContent()
    {
        parent::initContent();

        $this->setTemplate(_PS_THEME_DIR_.'category.tpl');

        if (!$this->customer_access) {
            return;
        }

        if (isset($this->context->cookie->id_compare)) {
            $this->context->smarty->assign('compareProducts', CompareProduct::getCompareProducts((int) $this->context->cookie->id_compare));
        }

        // Product sort must be called before assignProductList()
        $this->productSort();

        $this->assignScenes();
        $this->assignSubcategories();
        $this->assignProductList();

        $this->context->smarty->assign(
            [
                'category'             => $this->category,
                'description_short'    => Tools::truncateString($this->category->description, 350),
                'products'             => (isset($this->cat_products) && $this->cat_products) ? $this->cat_products : null,
                'id_category'          => (int) $this->category->id,
                'id_category_parent'   => (int) $this->category->id_parent,
                'return_category_name' => Tools::safeOutput($this->category->name),
                'path'                 => Tools::getPath($this->category->id),
                'add_prod_display'     => Configuration::get('PS_ATTRIBUTE_CATEGORY_DISPLAY'),
                'categorySize'         => Image::getSize(ImageType::getFormatedName('category')),
                'mediumSize'           => Image::getSize(ImageType::getFormatedName('medium')),
                'thumbSceneSize'       => Image::getSize(ImageType::getFormatedName('m_scene')),
                'homeSize'             => Image::getSize(ImageType::getFormatedName('home')),
                'allow_oosp'           => (int) Configuration::get('PS_ORDER_OUT_OF_STOCK'),
                'comparator_max_item'  => (int) Configuration::get('PS_COMPARATOR_MAX_ITEM'),
                'body_classes'         => [$this->php_self.'-'.$this->category->id, $this->php_self.'-'.$this->category->link_rewrite],
            ]
        );
    }

    /**
     * Assigns scenes template variables
     *
     * @throws PrestaShopException
     */
    protected function assignScenes()
    {
        // Scenes (could be externalised to another controller if you need them)
        $scenes = Scene::getScenes($this->category->id, $this->context->language->id, true, false);
        $this->context->smarty->assign('scenes', $scenes);

        // Scenes images formats
        if ($scenes && ($sceneImageTypes = ImageType::getImagesTypes('scenes'))) {
            foreach ($sceneImageTypes as $sceneImageType) {
                if ($sceneImageType['name'] == ImageType::getFormatedName('m_scene')) {
                    $thumbSceneImageType = $sceneImageType;
                } elseif ($sceneImageType['name'] == ImageType::getFormatedName('scene')) {
                    $largeSceneImageType = $sceneImageType;
                }
            }

            $this->context->smarty->assign(
                [
                    'thumbSceneImageType' => $thumbSceneImageType ?? null,
                    'largeSceneImageType' => $largeSceneImageType ?? null,
                ]
            );
        }
    }

    /**
     * Assigns subcategory templates variables
     *
     * @return void
     *
     * @throws PrestaShopException
     */
    protected function assignSubcategories()
    {
        if ($subCategories = $this->category->getSubCategories($this->context->language->id)) {
            $this->context->smarty->assign(
                [
                    'subcategories'          => $subCategories,
                    'subcategories_nb_total' => count($subCategories),
                    'subcategories_nb_half'  => ceil(count($subCategories) / 2),
                ]
            );
        }
    }

    /**
     * Assigns product list template variables
     *
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function assignProductList()
    {
        $hookExecuted = false;
        Hook::exec(
            'actionProductListOverride',
            [
                'nbProducts'   => &$this->nbProducts,
                'catProducts'  => &$this->cat_products,
                'hookExecuted' => &$hookExecuted,
            ]
        );

        // The hook was not executed, standard working
        if (!$hookExecuted) {
            $this->context->smarty->assign('categoryNameComplement', '');
            $this->nbProducts = $this->category->getProducts(null, null, null, $this->orderBy, $this->orderWay, true);
            $this->pagination((int) $this->nbProducts); // Pagination must be call after "getProducts"
            $this->cat_products = $this->category->getProducts($this->context->language->id, (int) $this->p, (int) $this->n, $this->orderBy, $this->orderWay);
        } // Hook executed, use the override
        else {
            // Pagination must be call after "getProducts"
            $this->pagination($this->nbProducts);
        }

        $this->addColorsToProductList($this->cat_products);

        Hook::exec(
            'actionProductListModifier',
            [
                'nb_products'  => &$this->nbProducts,
                'cat_products' => &$this->cat_products,
            ]
        );

        foreach ($this->cat_products as &$product) {
            if (isset($product['id_product_attribute']) && $product['id_product_attribute'] && isset($product['product_attribute_minimal_quantity'])) {
                $product['minimal_quantity'] = $product['product_attribute_minimal_quantity'];
            }
        }

        $this->context->smarty->assign('nb_products', $this->nbProducts);
    }

    /**
     * Returns an instance of the current category
     *
     * @return Category
     */
    public function getCategory()
    {
        return $this->category;
    }
}
