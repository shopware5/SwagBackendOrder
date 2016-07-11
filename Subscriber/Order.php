<?php

namespace SwagBackendOrder\Subscriber;

use Enlight\Event\SubscriberInterface;

class Order implements SubscriberInterface
{
    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PostDispatch_Backend_Order' => 'onOrderPostDispatch'
        ];
    }

    /**
     * adds the templates directories which expand the order module
     *
     * @param \Enlight_Controller_ActionEventArgs $args
     */
    public function onOrderPostDispatch(\Enlight_Controller_ActionEventArgs $args)
    {
        $view = $args->getSubject()->View();

        // Add view directory
        $args->getSubject()->View()->addTemplateDir(
            __DIR__ . '/../Views/'
        );

        if ($args->getRequest()->getActionName() === 'load') {
            $view->extendsTemplate(
                'backend/order/view/create_backend_order/list.js'
            );
        }
    }
}