<?php

namespace SwagBackendOrder\Subscriber;

use Enlight\Event\SubscriberInterface;
use Shopware\Components\DependencyInjection\Container;

class BackendController implements SubscriberInterface
{
    /**
     * @var Container
     */
    private $container;

    /**
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_SwagBackendOrder' => 'onGetBackendController'
        ];
    }

    /**
     * adds the templates and snippets dir
     *
     * @return string
     */
    public function onGetBackendController()
    {
        $this->container->get('template')->addTemplateDir($this->getPluginPath() . '/Views/');
        $this->container->get('snippets')->addConfigDir($this->getPluginPath() . '/Snippets/');

        return $this->getPluginPath() . '/Controllers/Backend/SwagBackendOrder.php';
    }

    /**
     * @return string
     */
    private function getPluginPath()
    {
        return $this->container->getParameter('swag_backend_orders.plugin_dir');
    }
}