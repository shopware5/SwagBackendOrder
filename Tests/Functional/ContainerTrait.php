<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace SwagBackendOrder\Tests\Functional;

use Shopware\Components\DependencyInjection\Container;

trait ContainerTrait
{
    public function getContainer(): Container
    {
        $container = \SwagBackendOrderTestKernel::getKernel()->getContainer();

        if (!$container instanceof Container) {
            throw new \UnexpectedValueException('Container not found');
        }

        return $container;
    }
}
