<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace SwagBackendOrder\Components\ConfirmationMail;

use Doctrine\DBAL\Connection;

class ConfirmationMailRepository
{
    /**
     * @var Connection
     */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function getOrderDetailsByOrderId(int $orderId): array
    {
        return $this->connection->fetchAll(
            'SELECT * FROM s_order_details WHERE orderID = ?',
            [$orderId]
        );
    }

    public function getArticleDetailsByOrderNumber(string $ordernumber): array
    {
        $selectedColumns = [
            'details.id AS articleDetailId',
            'details.ordernumber',
            'details.instock',
            'details.maxpurchase',
            'details.minpurchase',
            'details.purchasesteps',
            'details.stockmin',
            'details.suppliernumber',
            'details.purchaseunit',
            'details.releasedate',
            'details.unitID',
            'details.laststock',
            'article.mode as modus',
            'article.main_detail_id as mainDetailId',
            'article.taxID',
        ];
        $select = \implode(',', $selectedColumns);

        $sql = sprintf('SELECT %s FROM s_articles_details details LEFT JOIN s_articles article ON article.id=details.articleID WHERE details.ordernumber=?', $select);

        return $this->connection->executeQuery($sql, [$ordernumber])->fetch();
    }

    public function getBillingAddressByOrderId(int $orderId): array
    {
        $billingAddress = $this->connection->executeQuery(
            'SELECT *, userID AS customerBillingId FROM s_order_billingaddress WHERE orderID = ?',
            [$orderId]
        )->fetch();

        $billingAddressAttributes = $this->connection->executeQuery(
            'SELECT * FROM s_order_billingaddress_attributes WHERE billingID = ?',
            [$billingAddress['id']]
        )->fetch();

        if (!empty($billingAddressAttributes)) {
            $billingAddress = \array_merge($billingAddress, $billingAddressAttributes);
        }

        return $billingAddress;
    }

    public function getShippingAddressByOrderId(int $orderId): array
    {
        $shippingAddress = $this->connection->executeQuery(
            'SELECT *, userID AS customerBillingId FROM s_order_shippingaddress WHERE orderID = ?',
            [$orderId]
        )->fetch();

        $shippingAddressAttributes = $this->connection->executeQuery(
            'SELECT * FROM s_order_shippingaddress_attributes WHERE shippingID = ?',
            [$shippingAddress['id']]
        )->fetch();

        if (!empty($shippingAddressAttributes)) {
            $shippingAddress = \array_merge($shippingAddress, $shippingAddressAttributes);
        }

        return $shippingAddress;
    }

    public function getOrderAttributesByOrderId(int $orderId): ?array
    {
        $orderAttributes = $this->connection->executeQuery(
            'SELECT * FROM s_order_attributes WHERE orderID = ?',
            [$orderId]
        )->fetch();

        if (!\is_array($orderAttributes)) {
            return null;
        }

        return $orderAttributes;
    }

    public function getDispatchByDispatchId(int $dispatchId): array
    {
        return $this->connection->executeQuery(
            'SELECT * FROM s_premium_dispatch WHERE id = ?',
            [$dispatchId]
        )->fetch();
    }

    public function getCustomerByUserId(int $userId): array
    {
        return $this->connection->executeQuery(
            'SELECT * FROM s_user WHERE id = ?',
            [$userId]
        )->fetch();
    }

    public function getCountryByCountryId(int $countryId): array
    {
        return $this->connection->executeQuery(
            'SELECT * FROM s_core_countries WHERE id = ?',
            [$countryId]
        )->fetch();
    }

    public function getStateByStateId(int $stateId): array
    {
        return $this->connection->executeQuery(
            'SELECT * FROM s_core_countries_states WHERE id = ?',
            [$stateId]
        )->fetch();
    }

    public function getPaymentmeanByPaymentmeanId(int $paymentmeanId): array
    {
        return $this->connection->executeQuery(
            'SELECT * FROM s_core_paymentmeans WHERE id = ?',
            [$paymentmeanId]
        )->fetch();
    }
}
