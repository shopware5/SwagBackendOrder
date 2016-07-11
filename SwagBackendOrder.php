<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagBackendOrder;

use Shopware\Components\Plugin;
use SwagBackendOrder\Subscriber\BackendController;
use SwagBackendOrder\Subscriber\Customer;
use SwagBackendOrder\Subscriber\Order;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SwagBackendOrder extends Plugin
{
    /**
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container)
    {
        $container->setParameter('swag_backend_orders.plugin_dir', $this->getPath());

        parent::build($container);
    }
//
//    /**
//     * @param string $oldVersion
//     * @return bool
//     * @throws Exception
//     */
//    public function update($oldVersion)
//    {
//        if (!$this->assertMinimumVersion($this->getMinimumVersion())) {
//            throw new Exception(
//                sprintf('This plugin requires Shopware %s or a later version', $this->getMinimumVersion())
//            );
//        }
//
//        if (version_compare($oldVersion, '1.0.1', '<')) {
//            $orderDetailIds = Shopware()->Db()->fetchCol(
//                'SELECT id FROM s_order_details WHERE id NOT IN (SELECT detailID FROM s_order_details_attributes);'
//            );
//            foreach ($orderDetailIds as $orderDetailId) {
//                $sql = 'INSERT INTO `s_order_details_attributes` (detailID) VALUES (?)';
//                Shopware()->Db()->query($sql, [$orderDetailId]);
//            }
//        }
//
//        return true;
//    }
}
