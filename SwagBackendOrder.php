<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagBackendOrder;

use Shopware\Bundle\PluginInstallerBundle\Service\InstallerService;
use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Models\Plugin\Plugin as PluginModel;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SwagBackendOrder extends Plugin
{
    public function build(ContainerBuilder $container)
    {
        $container->setParameter('swag_backend_orders.plugin_dir', $this->getPath());
        parent::build($container);
    }

    /**
     * {@inheritdoc}
     */
    public function install(InstallContext $context)
    {
        /*
         * The following code sets the initial value of the sendMail configuration in the plugin to the core config value of "sendOrderMail".
         * If the plugin has been configured already, it will not overwrite the existing value.
         */

        /** @var array $pluginConfig */
        $pluginConfig = $this->container->get('shopware.plugin.cached_config_reader')->getByPluginName($this->getName());
        $sendMailConfigGlobal = (bool) $this->container->get('config')->get('sendOrderMail');

        /*
         * If there is a plugin configuration already, or the core value equals false anyway, it's not required to set
         * the initial config value again. Therefore it returns before executing the next part.
         */
        if (!$sendMailConfigGlobal || isset($pluginConfig['sendMail'])) {
            return;
        }

        /** @var InstallerService $pluginManager */
        $pluginManager = $this->container->get('shopware_plugininstaller.plugin_manager');

        /** @var PluginModel $plugin */
        $plugin = $pluginManager->getPluginByName($this->getName());

        //Finally set the plugin config value to the core config value.
        $pluginManager->saveConfigElement($plugin, 'sendMail', $sendMailConfigGlobal);
    }
}
