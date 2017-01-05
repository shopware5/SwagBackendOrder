<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

ini_set('error_reporting', E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

require __DIR__ . "/../../../../../tests/Functional/bootstrap.php";

class SwagBackendOrderTestKernel extends TestKernel
{
    public static function start()
    {
        $kernel = new \Shopware\Kernel(getenv('SHOPWARE_ENV') ?: 'testing', true);
        $kernel->boot();

        $container = $kernel->getContainer();
        $container->get('plugins')->Core()->ErrorHandler()->registerErrorHandler(E_ALL | E_STRICT);

        /** @var $repository \Shopware\Models\Shop\Repository */
        $repository = $container->get('models')->getRepository('Shopware\Models\Shop\Shop');

        $shop = $repository->getActiveDefault();
        $shop->registerResources();

        if (!self::assertPlugin('SwagBackendOrder')) {
            throw new \Exception('Plugin SwagBackendOrder must be installed and activated.');
        }
    }

    /**
     * @param string $name
     * @return boolean
     */
    private static function assertPlugin($name)
    {
        $sql = 'SELECT 1 FROM s_core_plugins WHERE name = ? AND active = 1';

        return (boolean) Shopware()->Container()->get('dbal_connection')->fetchColumn($sql, [$name]);
    }
}

SwagBackendOrderTestKernel::start();