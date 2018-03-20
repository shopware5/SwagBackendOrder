<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagBackendOrder;

use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context\ActivateContext;
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
     * @param ActivateContext $context
     */
    public function activate(ActivateContext $context)
    {
        $shop = Shopware()->Models()->getRepository('Shopware\Models\Shop\Shop')->findOneBy(['default' => true]);
        $pluginManager = Shopware()->Container()->get('shopware_plugininstaller.plugin_manager');
        $plugin = $pluginManager->getPluginByName($this->getName());
        $sendMailConfigGlobal = Shopware()->Config()->get('sendOrderMail');
        if ($sendMailConfigGlobal == 1) {
            $pluginManager->saveConfigElement($plugin, 'sendMail', '1', $shop);
        } else {
            $pluginManager->saveConfigElement($plugin, 'sendMail', '0', $shop);
        }

        parent::activate($context);
    }
}
