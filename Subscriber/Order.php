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

class Order implements SubscriberInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

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
            'Enlight_Controller_Action_PostDispatch_Backend_Order' => 'onOrderPostDispatch',
        ];
    }

    /**
     * adds the templates directories which expand the order module
     */
    public function onOrderPostDispatch(\Enlight_Controller_ActionEventArgs $args)
    {
        $view = $args->getSubject()->View();

        // Add view directory
        $args->getSubject()->View()->addTemplateDir(
            $this->getPluginPath() . '/Resources/views/'
        );

        if ($args->getRequest()->getActionName() === 'load') {
            $view->extendsTemplate(
                'backend/order/view/create_backend_order/list.js'
            );
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
