<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagBackendOrder\Components\Order\Struct;

class OrderStruct
{
    /**
     * @var int
     */
    private $number;

    /**
     * @var int
     */
    private $customerId;

    /**
     * @var int
     */
    private $billingAddressId;

    /**
     * @var int
     */
    private $shippingAddressId;

    /**
     * @var float
     */
    private $shippingCosts;

    /**
     * @var float
     */
    private $shippingCostsNet;

    /**
     * @var float
     */
    private $shippingCostsTaxRate;

    /**
     * @var int
     */
    private $paymentId;

    /**
     * @var int
     */
    private $dispatchId;

    /**
     * @var int
     */
    private $languageShopId;

    /**
     * @var string
     */
    private $currency;

    /**
     * @var float
     */
    private $total;

    /**
     * @var string
     */
    private $deviceType;

    /**
     * @var bool
     */
    private $netOrder;

    /**
     * @var bool
     */
    private $sendMail;

    /**
     * @var PositionStruct[]
     */
    private $positions;

    /**
     * @var float
     */
    private $totalWithoutTax;

    /**
     * @var int
     */
    private $currencyId;

    /**
     * @var bool
     */
    private $taxFree;

    /**
     * @var array
     */
    private $attributes;

    /**
     * @return int
     */
    public function getCustomerId()
    {
        return $this->customerId;
    }

    /**
     * @param int $customerId
     */
    public function setCustomerId($customerId)
    {
        $this->customerId = $customerId;
    }

    /**
     * @return int
     */
    public function getBillingAddressId()
    {
        return $this->billingAddressId;
    }

    /**
     * @param int $billingAddressId
     */
    public function setBillingAddressId($billingAddressId)
    {
        $this->billingAddressId = $billingAddressId;
    }

    /**
     * @return int
     */
    public function getShippingAddressId()
    {
        return $this->shippingAddressId;
    }

    /**
     * @param int $shippingAddressId
     */
    public function setShippingAddressId($shippingAddressId)
    {
        $this->shippingAddressId = $shippingAddressId;
    }

    /**
     * @return float
     */
    public function getShippingCosts()
    {
        return $this->shippingCosts;
    }

    /**
     * @param float $shippingCosts
     */
    public function setShippingCosts($shippingCosts)
    {
        $this->shippingCosts = $shippingCosts;
    }

    /**
     * @return float
     */
    public function getShippingCostsNet()
    {
        return $this->shippingCostsNet;
    }

    /**
     * @param float $shippingCostsNet
     */
    public function setShippingCostsNet($shippingCostsNet)
    {
        $this->shippingCostsNet = $shippingCostsNet;
    }

    /**
     * @return float
     */
    public function getShippingCostsTaxRate()
    {
        return $this->shippingCostsTaxRate;
    }

    /**
     * @param float $shippingCostsTaxRate
     */
    public function setShippingCostsTaxRate($shippingCostsTaxRate)
    {
        $this->shippingCostsTaxRate = $shippingCostsTaxRate;
    }

    /**
     * @return int
     */
    public function getPaymentId()
    {
        return $this->paymentId;
    }

    /**
     * @param int $paymentId
     */
    public function setPaymentId($paymentId)
    {
        $this->paymentId = $paymentId;
    }

    /**
     * @return int
     */
    public function getDispatchId()
    {
        return $this->dispatchId;
    }

    /**
     * @param int $dispatchId
     */
    public function setDispatchId($dispatchId)
    {
        $this->dispatchId = $dispatchId;
    }

    /**
     * @return int
     */
    public function getLanguageShopId()
    {
        return $this->languageShopId;
    }

    /**
     * @param int $languageShopId
     */
    public function setLanguageShopId($languageShopId)
    {
        $this->languageShopId = $languageShopId;
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @param string $currency
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
    }

    /**
     * @return float
     */
    public function getTotal()
    {
        return $this->total;
    }

    /**
     * @param float $total
     */
    public function setTotal($total)
    {
        $this->total = $total;
    }

    /**
     * @return string
     */
    public function getDeviceType()
    {
        return $this->deviceType;
    }

    /**
     * @param string $deviceType
     */
    public function setDeviceType($deviceType)
    {
        $this->deviceType = $deviceType;
    }

    /**
     * @return bool
     */
    public function getNetOrder()
    {
        return $this->netOrder;
    }

    /**
     * @param bool $netOrder
     */
    public function setNetOrder($netOrder)
    {
        $this->netOrder = $netOrder;
    }

    /**
     * @return bool
     */
    public function getSendMail()
    {
        return $this->sendMail;
    }

    /**
     * @param bool $sendMail
     */
    public function setSendMail($sendMail)
    {
        $this->sendMail = $sendMail;
    }

    /**
     * @return PositionStruct[]
     */
    public function getPositions()
    {
        return $this->positions;
    }

    /**
     * @param PositionStruct[] $positions
     */
    public function setPositions(array $positions)
    {
        $this->positions = $positions;
    }

    public function addPosition(PositionStruct $position)
    {
        $this->positions[] = $position;
    }

    /**
     * @param float $totalWithoutTax
     */
    public function setTotalWithoutTax($totalWithoutTax)
    {
        $this->totalWithoutTax = $totalWithoutTax;
    }

    /**
     * @return float
     */
    public function getTotalWithoutTax()
    {
        return $this->totalWithoutTax;
    }

    /**
     * @return int
     */
    public function getCurrencyId()
    {
        return $this->currencyId;
    }

    /**
     * @param int $currencyId
     */
    public function setCurrencyId($currencyId)
    {
        $this->currencyId = $currencyId;
    }

    /**
     * @return int
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * @param int $number
     */
    public function setNumber($number)
    {
        $this->number = $number;
    }

    /**
     * @return bool
     */
    public function isTaxFree()
    {
        return $this->taxFree;
    }

    /**
     * @param bool $taxFree
     */
    public function setTaxFree($taxFree)
    {
        $this->taxFree = $taxFree;
    }

    /**
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * @param array $attributes
     */
    public function setAttributes($attributes)
    {
        $this->attributes = $attributes;
    }
}
