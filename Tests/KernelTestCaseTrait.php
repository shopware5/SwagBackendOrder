<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagBackendOrder\Tests;

use Shopware\Kernel;
use Shopware\Models\Shop\Shop;

trait KernelTestCaseTrait
{
    /**
     * @var Kernel
     */
    private static $kernel;

    /**
     * @return Kernel
     */
    protected static function getKernel()
    {
        if (!self::$kernel) {
            self::bootKernelBefore();
        }

        return self::$kernel;
    }

    /**
     * @before
     */
    protected static function bootKernelBefore()
    {
        if (self::$kernel instanceof Kernel) {
            return;
        }
        self::$kernel = new Kernel(getenv('SHOPWARE_ENV') ?: 'testing', true);
        self::$kernel->boot();

        self::$kernel->getContainer()->get('dbal_connection')->beginTransaction();

        /** @var \Shopware\Models\Shop\Repository $repository */
        $repository = Shopware()->Container()->get('models')->getRepository(Shop::class);

        self::$kernel->getContainer()->get('shopware.components.shop_registration_service')->registerResources(
            $repository->getActiveDefault()
        );
    }

    /**
     * @after
     */
    protected static function destroyKernelAfter()
    {
        self::$kernel->getContainer()->get('dbal_connection')->rollBack();

        self::$kernel = null;
        gc_collect_cycles();
        Shopware(new EmptyShopwareApplication());
    }

    /**
     * @return \Shopware\Components\DependencyInjection\Container
     */
    protected static function getContainer()
    {
        if (self::$kernel === null) {
            self::bootKernelBefore();
        }

        return self::$kernel->getContainer();
    }

    /**
     * @param string $sql
     */
    protected function execSql($sql)
    {
        self::getContainer()->get('dbal_connection')->exec($sql);
    }
}

class EmptyShopwareApplication
{
    public function __call($name, $arguments)
    {
        throw new \RuntimeException('Restricted to call ' . $name . ' because you should not have a test kernel in this test case.');
    }
}
