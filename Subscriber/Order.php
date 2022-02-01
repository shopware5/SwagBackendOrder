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

class Order implements SubscriberInterface
{
    /**
     * @var string
     */
    private $pluginDir;

    public function __construct(string $pluginDir)
    {
        $this->pluginDir = $pluginDir;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'Enlight_Controller_Action_PostDispatch_Backend_Order' => 'onOrderPostDispatch',
        ];
    }

    /**
     * adds the templates directories which expand the order module
     */
    public function onOrderPostDispatch(\Enlight_Controller_ActionEventArgs $args): void
    {
        $view = $args->getSubject()->View();

        // Add view directory
        $args->getSubject()->View()->addTemplateDir(
            $this->pluginDir . '/Resources/views/'
        );

        if ($args->getRequest()->getActionName() === 'load') {
            $view->extendsTemplate(
                'backend/order/view/create_backend_order/list.js'
            );
        }
    }
}
