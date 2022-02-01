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

trait DatabaseTestCaseTrait
{
    /**
     * @before
     */
    protected function startTransactionBefore(): void
    {
        $connection = $this->getContainer()->get('dbal_connection');
        $connection->beginTransaction();
    }

    /**
     * @after
     */
    protected function rollbackTransactionAfter(): void
    {
        $connection = $this->getContainer()->get('dbal_connection');
        $connection->rollBack();
    }
}
