<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagBackendOrder\Components\ConfirmationMail;

use Doctrine\DBAL\Connection;

class ConfirmationMailRepository
{
    /**
     * @var Connection
     */
    private $connection;

    public function __construct(
        Connection $connection
    ) {
        $this->connection = $connection;
    }

    /**
     * @param int $orderId
     *
     * @return array
     */
    public function getOrderDetailsByOrderId($orderId)
    {
        return $this->connection->fetchAll(
            'SELECT * FROM s_order_details WHERE orderID = ?',
            [$orderId]
        );
    }

    /**
     * @param string $ordernumber
     *
     * @return array
     */
    public function getArticleDetailsByOrderNumber($ordernumber)
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
        $select = implode(',', $selectedColumns);

        $sql = "SELECT {$select} FROM s_articles_details details LEFT JOIN s_articles article ON article.id=details.articleID WHERE details.ordernumber=?";
        $articleDetail = $this->connection->executeQuery($sql, [$ordernumber])->fetchAll();

        return $articleDetail[0];
    }

    /**
     * @param int $orderId
     *
     * @return array
     */
    public function getBillingAddressByOrderId($orderId)
    {
        $billingAddress = $this->connection->fetchAll(
            'SELECT *, userID AS customerBillingId FROM s_order_billingaddress WHERE orderID = ?',
            [$orderId]
        )[0];

        $billingAddressAttributes = $this->connection->fetchAll(
            'SELECT * FROM s_order_billingaddress_attributes WHERE billingID = ?',
            [$billingAddress['id']]
        )[0];

        if (!empty($billingAddressAttributes)) {
            $billingAddress = array_merge($billingAddress, $billingAddressAttributes);
        }

        return $billingAddress;
    }

    /**
     * @param int $orderId
     *
     * @return array
     */
    public function getShippingAddressByOrderId($orderId)
    {
        $shippingAddress = $this->connection->fetchAll(
            'SELECT *, userID AS customerBillingId FROM s_order_shippingaddress WHERE orderID = ?',
            [$orderId]
        )[0];

        $shippingAddressAttributes = $this->connection->fetchAll(
            'SELECT * FROM s_order_shippingaddress_attributes WHERE shippingID = ?',
            [$shippingAddress['id']]
        )[0];

        if (!empty($shippingAddressAttributes)) {
            $shippingAddress = array_merge($shippingAddress, $shippingAddressAttributes);
        }

        return $shippingAddress;
    }

    /**
     * @param int $orderId
     *
     * @return array
     */
    public function getOrderAttributesByOrderId($orderId)
    {
        return $this->connection->fetchAll(
            'SELECT * FROM s_order_attributes WHERE orderID = ?',
            [$orderId]
        )[0];
    }

    /**
     * @param int $dispatchId
     *
     * @return array
     */
    public function getDispatchByDispatchId($dispatchId)
    {
        return $this->connection->fetchAll(
            'SELECT * FROM s_premium_dispatch WHERE id = ?',
            [$dispatchId]
        )[0];
    }

    /**
     * @param int $userId
     *
     * @return array
     */
    public function getCustomerByUserId($userId)
    {
        return $this->connection->fetchAll(
            'SELECT * FROM s_user WHERE id = ?',
            [$userId]
        )[0];
    }

    /**
     * @param int $countryId
     *
     * @return array
     */
    public function getCountryByCountryId($countryId)
    {
        return $this->connection->fetchAll(
            'SELECT * FROM s_core_countries WHERE id = ?',
            [$countryId]
        )[0];
    }

    /**
     * @param int $stateId
     *
     * @return array
     */
    public function getStateByStateId($stateId)
    {
        return $this->connection->fetchAll(
            'SELECT * FROM s_core_countries_states WHERE id = ?',
            [$stateId]
        )[0];
    }

    /**
     * @param int $paymentmeanId
     *
     * @return array
     */
    public function getPaymentmeanByPaymentmeanId($paymentmeanId)
    {
        return $this->connection->fetchAll(
            'SELECT * FROM s_core_paymentmeans WHERE id = ?',
            [$paymentmeanId]
        )[0];
    }
}
