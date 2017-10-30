<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagBackendOrder\Components\ProductSearch;

interface ProductSearchInterface
{
    /**
     * @return int
     */
    public function getLastResultTotalCount();

    /**
     * @param string $searchTerm
     * @param int    $shopId
     * @param int    $limit
     * @param int    $offset
     *
     * @return array
     */
    public function findProducts($searchTerm, $shopId, $limit, $offset);

    /**
     * @param string $orderNumber
     * @param array  $params
     * @param int    $shopId
     * @param string $customerGroupKey
     *
     * @return array
     */
    public function getProduct($orderNumber, $params, $shopId, $customerGroupKey);
}
