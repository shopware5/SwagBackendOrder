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

class BackendController implements SubscriberInterface
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
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_SwagBackendOrder' => 'onGetBackendController',
        ];
    }

    /**
     * adds the templates and snippets dir
     *
     * @return string
     */
    public function onGetBackendController()
    {
        $this->container->get('template')->addTemplateDir($this->getPluginPath() . '/Resources/views/');
        $this->container->get('snippets')->addConfigDir($this->getPluginPath() . '/Resources/snippets/');

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
