<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace SwagBackendOrder\Subscriber;

use Enlight\Event\SubscriberInterface;
use Enlight_Plugin_PluginManager as PluginManager;
use Shopware\Components\Validator\EmailValidator;

class Customer implements SubscriberInterface
{
    /**
     * @var string
     */
    private $pluginDir;

    /**
     * @var EmailValidator
     */
    private $emailValidator;

    /**
     * @var PluginManager
     */
    private $pluginManager;

    public function __construct(string $pluginDir, EmailValidator $emailValidator, PluginManager $pluginManager)
    {
        $this->pluginDir = $pluginDir;
        $this->emailValidator = $emailValidator;
        $this->pluginManager = $pluginManager;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'Enlight_Controller_Action_PostDispatchSecure_Backend_Customer' => 'onCustomerPostDispatchSecure',
            'Enlight_Controller_Action_Backend_Customer_validateEmail' => 'onPostDispatchCustomer',
        ];
    }

    public function onPostDispatchCustomer(\Enlight_Event_EventArgs $arguments): ?bool
    {
        $controller = $arguments->get('subject');
        $request = $controller->Request();
        $mail = $request->getParam('value');

        if (!$request->getParam('isBackendOrder', false)) {
            return null;
        }

        $this->pluginManager->Controller()->ViewRenderer()->setNoRender();

        $controller->Response()->setBody(
            $this->emailValidator->isValid($mail)
        );

        return true;
    }

    /**
     * adds the templates directories which expand the customer module
     */
    public function onCustomerPostDispatchSecure(\Enlight_Controller_ActionEventArgs $args): void
    {
        $view = $args->getSubject()->View();

        $args->getSubject()->View()->addTemplateDir($this->pluginDir . '/Resources/views/');

        if ($args->getRequest()->getActionName() === 'load') {
            $view->extendsTemplate('backend/customer/controller/create_backend_order/detail.js');
            $view->extendsTemplate('backend/customer/view/create_backend_order/detail/base.js');
            $view->extendsTemplate('backend/customer/view/create_backend_order/detail/additional.js');
            $view->extendsTemplate('backend/customer/view/create_backend_order/detail/window.js');
        }
    }
}
