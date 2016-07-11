<?php

namespace SwagBackendOrder\Subscriber;

use Enlight\Event\SubscriberInterface;

class Customer implements SubscriberInterface
{
    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PostDispatchSecure_Backend_Customer' => 'onCustomerPostDispatchSecure',
            'Enlight_Controller_Action_PostDispatch_Backend_Customer' => 'onPostDispatchCustomer'
        ];
    }

    /**
     * checks if the fake email was used to create accounts with the same email
     *
     * @param \Enlight_Controller_ActionEventArgs $arguments
     * @return bool
     */
    public function onPostDispatchCustomer(\Enlight_Controller_ActionEventArgs $arguments)
    {
        $mail = $arguments->getSubject()->Request()->getParam('value');
        $action = $arguments->getSubject()->Request()->getParam('action');

        if (!empty($mail) && $action !== 'validateEmail') {
            if (Shopware()->Config()->get('validationMail') == $mail) {
                Shopware()->Plugins()->Controller()->ViewRenderer()->setNoRender();
                echo true;

                return true;
            }
        }
    }

    /**
     * adds the templates directories which expand the customer module
     *
     * @param \Enlight_Controller_ActionEventArgs $args
     */
    public function onCustomerPostDispatchSecure(\Enlight_Controller_ActionEventArgs $args)
    {
        $view = $args->getSubject()->View();

        $args->getSubject()->View()->addTemplateDir(__DIR__ . '/../Views/');

        if ($args->getRequest()->getActionName() === 'load') {
            $view->extendsTemplate('backend/customer/controller/create_backend_order/detail.js');
            $view->extendsTemplate('backend/customer/controller/create_backend_order/main.js');
            $view->extendsTemplate('backend/customer/view/create_backend_order/detail/base.js');
            $view->extendsTemplate('backend/customer/view/create_backend_order/detail/additional.js');
            $view->extendsTemplate('backend/customer/view/create_backend_order/detail/window.js');
        }
    }
}