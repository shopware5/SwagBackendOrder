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

trait FixtureImportTestCaseTrait
{
    public function importFixtures(string $file): void
    {
        $connection = Shopware()->Container()->get('dbal_connection');
        $sql = \file_get_contents($file);
        static::assertIsString($sql);
        $connection->executeQuery($sql);
    }
}
