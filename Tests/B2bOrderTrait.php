<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagBackendOrder\Tests;

trait B2bOrderTrait
{
    protected function getB2bOrder(string $ordernumber = '20001'): ?array
    {
        $result = Shopware()->Container()->get('dbal_connection')->createQueryBuilder()
            ->select('*')
            ->from('b2b_order_context')
            ->where('ordernumber = :ordernumber')
            ->setParameter('ordernumber', $ordernumber)
            ->execute()
            ->fetchAll(\PDO::FETCH_ASSOC);

        if (!$result) {
            return null;
        }

        return $result;
    }

    protected function b2bUserIsDebitor(): void
    {
        $sql = 'INSERT IGNORE INTO `s_user_attributes` (`id`, `userID`, `b2b_is_debtor`, `b2b_is_sales_representative`) VALUES (1, 2, 1, 1);';

        Shopware()->Container()->get('dbal_connection')->exec($sql);
    }

    protected function isB2bPluginInstalled(): bool
    {
        return (bool) Shopware()->Container()->get('dbal_connection')->fetchColumn(
            'SELECT 1 FROM s_core_plugins WHERE name = "SwagB2bPlugin" AND active = 1'
        );
    }
}
