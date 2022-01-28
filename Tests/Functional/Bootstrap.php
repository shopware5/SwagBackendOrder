<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

use Shopware\Kernel;
use Shopware\Models\Shop\Shop;

require __DIR__ . '/../../../../../autoload.php';

class SwagBackendOrderTestKernel extends Kernel
{
    /**
     * @var SwagBackendOrderTestKernel
     */
    private static $kernel;

    public static function start(): void
    {
        self::$kernel = new self(\getenv('SHOPWARE_ENV') ?: 'testing', true);
        self::$kernel->boot();

        $container = self::$kernel->getContainer();
        $container->get('plugins')->Core()->ErrorHandler()->registerErrorHandler(\E_ALL | \E_STRICT);

        $shop = $container->get('models')->getRepository(Shop::class)->getActiveDefault();
        $container->get('shopware.components.shop_registration_service')->registerResources($shop);

        if (!self::assertPlugin()) {
            throw new \RuntimeException('Plugin SwagBackendOrder must be installed and activated.');
        }
    }

    public static function getKernel(): SwagBackendOrderTestKernel
    {
        return self::$kernel;
    }

    private static function assertPlugin(): bool
    {
        $sql = 'SELECT 1 FROM s_core_plugins WHERE name = ? AND active = 1';

        return (bool) Shopware()->Container()->get('dbal_connection')->fetchColumn($sql, ['SwagBackendOrder']);
    }
}

SwagBackendOrderTestKernel::start();
