<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagBackendOrder\Subscriber;

use Enlight\Event\SubscriberInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Customer implements SubscriberInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PostDispatchSecure_Backend_Customer' => 'onCustomerPostDispatchSecure',
        ];
    }

    /**
     * adds the templates directories which expand the customer module
     *
     * @param \Enlight_Controller_ActionEventArgs $args
     */
    public function onCustomerPostDispatchSecure(\Enlight_Controller_ActionEventArgs $args)
    {
        $view = $args->getSubject()->View();

        $args->getSubject()->View()->addTemplateDir($this->getPluginPath() . '/Resources/views/');

        if ($args->getRequest()->getActionName() === 'load') {
            $view->extendsTemplate('backend/customer/controller/create_backend_order/detail.js');
            $view->extendsTemplate('backend/customer/view/create_backend_order/detail/base.js');
            $view->extendsTemplate('backend/customer/view/create_backend_order/detail/additional.js');
            $view->extendsTemplate('backend/customer/view/create_backend_order/detail/window.js');
        }
    }

    /**
     * @return string
     */
    private function getPluginPath()
    {
        return $this->container->getParameter('swag_backend_orders.plugin_dir');
    }
}
