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
     * @inheritdoc
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Front_StartDispatch' => 'onStartDispatch'
        ];
    }

    /**
     * Add subscriber to the event system.
     *
     * @param \Enlight_Event_EventArgs $args
     */
    public function onStartDispatch(\Enlight_Event_EventArgs $args)
    {
        $subscribers = [
            new BackendController($this->container),
            new Customer($this->container),
            new Order($this->container)
        ];

        foreach ($subscribers as $subscriber) {
            $this->container->get('events')->addSubscriber($subscriber);
        }
    }

    /**
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container)
    {
        $container->setParameter('swag_backend_orders.plugin_dir', $this->getPath());

        parent::build($container);
    }

    /**
     * function to register events and hooks
     */
    private function registerEvents()
    {
//
//        // Register CreateBackendOrder-Resource
//        $this->subscribeEvent(
//            'Enlight_Bootstrap_InitResource_CreateBackendOrder',
//            'onInitCreateBackendOrderResource'
//        );
//
//        // Register CustomerInformationHandler-Resource
//        $this->subscribeEvent(
//            'Enlight_Bootstrap_InitResource_CustomerInformationHandler',
//            'onInitCustomerInformationHandlerResource'
//        );
    }
//
//    /**
//     * Event listener function of the Enlight_Bootstrap_InitResource_CreateBackendOrder event.
//     * Fired on $this->Application->CreateBackendOrder();
//     *
//     * @return Shopware_Components_CreateBackendOrder
//     */
//    public function onInitCreateBackendOrderResource()
//    {
//        $this->Application()->Loader()->registerNamespace('Shopware_Components', $this->Path() . 'Components/');

//        $createBackendOrder = Enlight_Class::Instance('Shopware_Components_CreateBackendOrder');
//        $this->getShopwareBootstrap()->registerResource('CreateBackendOrder', $createBackendOrder);
//
//        return $createBackendOrder;
//    }
//
//    /**
//     * @return Shopware_Components_CustomerInformationHandler
//     */
//    public function onInitCustomerInformationHandlerResource()
//    {
//        $this->Application()->Loader()->registerNamespace('Shopware_Components', $this->Path() . 'Components/');
//
//        $customerInformationHandler = Enlight_Class::Instance('Shopware_Components_CustomerInformationHandler');
//        $this->getShopwareBootstrap()->registerResource('CustomerInformationHandler', $customerInformationHandler);
//
//        return $customerInformationHandler;
//    }
//
//    /**
//     * Shopware application bootstrap class.
//     *
//     * Used to register plugin components.
//     *
//     * @return Enlight_Bootstrap
//     */
//    public function getShopwareBootstrap()
//    {
//        return $this->Application()->Bootstrap();
//    }
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
