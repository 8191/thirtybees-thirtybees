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

namespace Thirtybees\Core\Smarty\Cache;

use Db;
use Encryptor;
use PrestaShopDatabaseException;
use PrestaShopException;
use Smarty_CacheResource_Custom;

/**
 * Class CacheResourceMysqlCore
 */
class CacheResourceMysqlCore extends Smarty_CacheResource_Custom
{
    /**
     * @var Encryptor
     */
    protected $encryptor;

    /**
     * @param Encryptor $encryptor
     */
    public function __construct(Encryptor $encryptor)
    {
        $this->encryptor = $encryptor;
    }

    /**
     * fetch cached content and its modification time from data source
     *
     * @param string $id unique cache content identifier
     * @param string $name template name
     * @param string $cacheId cache id
     * @param string $compileId compile id
     * @param string $content cached content
     * @param int $mtime cache modification timestamp (epoch)
     *
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function fetch($id, $name, $cacheId, $compileId, &$content, &$mtime)
    {
        $row = Db::getInstance()->getRow('SELECT modified, content FROM '._DB_PREFIX_.'smarty_cache WHERE id_smarty_cache = "'.pSQL($id, true).'"');
        if ($row) {
            $encoded = $row['content'];
            if ($encoded) {
                $encrypted = base64_decode($encoded);
                if ($encrypted !== false) {
                    $content = $this->encryptor->decrypt($encrypted);
                    $mtime = strtotime($row['modified']);
                    return;
                }
            }
        }

        $content = null;
        $mtime = null;
    }

    /**
     * Fetch cached content's modification timestamp from data source
     *
     * @param string $id unique cache content identifier
     * @param string $name template name
     * @param string $cacheId cache id
     * @param string $compileId compile id
     *
     * @return int|boolean timestamp (epoch) the template was modified, or false if not found
     *
     * @throws PrestaShopException
     */
    protected function fetchTimestamp($id, $name, $cacheId, $compileId)
    {
        $value = Db::getInstance()->getValue('SELECT modified FROM '._DB_PREFIX_.'smarty_cache WHERE id_smarty_cache = "'.pSQL($id, true).'"');
        $mtime = strtotime($value);

        return $mtime;
    }

    /**
     * Save content to cache
     *
     * @param string $id unique cache content identifier
     * @param string $name template name
     * @param string $cacheId cache id
     * @param string $compileId compile id
     * @param int|null $expTime seconds till expiration time in seconds or null
     * @param string $content content to cache
     *
     * @return bool success
     *
     * @throws PrestaShopException
     */
    protected function save($id, $name, $cacheId, $compileId, $expTime, $content)
    {
        Db::getInstance()->execute(
            '
		REPLACE INTO '._DB_PREFIX_.'smarty_cache (id_smarty_cache, name, cache_id, content)
		VALUES (
			"'.pSQL($id, true).'",
			"'.pSQL(sha1($name)).'",
			"'.pSQL($cacheId, true).'",
			"'.base64_encode($this->encryptor->encrypt($content)).'"
		)'
        );

        return (bool) Db::getInstance()->Affected_Rows();
    }

    /**
     * Delete content from cache
     *
     * @param string $name template name
     * @param string $cacheId cache id
     * @param string $compileId compile id
     * @param int|null $expTime seconds till expiration or null
     *
     * @return int number of deleted caches
     *
     * @throws PrestaShopException
     */
    protected function delete($name, $cacheId, $compileId, $expTime)
    {
        // delete the whole cache
        if ($name === null && $cacheId === null && $compileId === null && $expTime === null) {
            // returning the number of deleted caches would require a second query to count them
            Db::getInstance()->execute('TRUNCATE TABLE '._DB_PREFIX_.'smarty_cache');

            return -1;
        }

        $where = [];
        if ($name !== null) {
            $where[] = 'name = "'.pSQL(sha1($name)).'"';
        }
        if ($expTime !== null) {
            $where[] = 'modified < DATE_SUB(NOW(), INTERVAL '.(int) $expTime.' SECOND)';
        }
        if ($cacheId !== null) {
            $where[] = '(cache_id  = "'.pSQL($cacheId, true).'" OR cache_id LIKE "'.pSQL($cacheId.'|%', true).'")';
        }

        Db::getInstance()->execute('DELETE FROM '._DB_PREFIX_.'smarty_cache WHERE '.implode(' AND ', $where));

        return Db::getInstance()->Affected_Rows();
    }
}
