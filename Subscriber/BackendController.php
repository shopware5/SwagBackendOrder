<?php

namespace SwagBackendOrder\Subscriber;

use Enlight\Event\SubscriberInterface;

class BackendController implements SubscriberInterface
{
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
        Shopware()->Container()->get('template')->addTemplateDir(__DIR__ . '/../Views/');
        Shopware()->Container()->get('snippets')->addConfigDir(__DIR__ . '/../Snippets/');

        return __DIR__ . '/../Controllers/Backend/SwagBackendOrder.php';
    }
}