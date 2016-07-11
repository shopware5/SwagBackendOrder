<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagBackendOrder;

use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context\UpdateContext;
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

    /**
     * @param UpdateContext $context
     */
    public function update(UpdateContext $context)
    {
        if (version_compare($context->getUpdateVersion(), '1.0.1', '>=')) {
            $this->updateTo101();
        }

        return parent::update($context);
    }

    /**
     * Update Backend Orders to version 1.0.1
     */
    private function updateTo101()
    {
        $orderDetailIds = $this->container->get('db')->fetchCol(
            'SELECT id FROM s_order_details WHERE id NOT IN (SELECT detailID FROM s_order_details_attributes);'
        );
        foreach ($orderDetailIds as $orderDetailId) {
            $sql = 'INSERT INTO `s_order_details_attributes` (detailID) VALUES (?)';
            $this->container->get('db')->query($sql, [$orderDetailId]);
        }
    }
}
