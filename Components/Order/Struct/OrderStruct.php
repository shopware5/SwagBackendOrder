<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
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
    private $shippingCostsTaxRate = 0.0;

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
    private $taxFree = false;

    /**
     * @var array
     */
    private $attributes;

    public function getCustomerId(): int
    {
        return $this->customerId;
    }

    public function setCustomerId(int $customerId): void
    {
        $this->customerId = $customerId;
    }

    public function getBillingAddressId(): int
    {
        return $this->billingAddressId;
    }

    public function setBillingAddressId(int $billingAddressId): void
    {
        $this->billingAddressId = $billingAddressId;
    }

    public function getShippingAddressId(): int
    {
        return $this->shippingAddressId;
    }

    public function setShippingAddressId(int $shippingAddressId): void
    {
        $this->shippingAddressId = $shippingAddressId;
    }

    public function getShippingCosts(): float
    {
        return $this->shippingCosts;
    }

    public function setShippingCosts(float $shippingCosts): void
    {
        $this->shippingCosts = $shippingCosts;
    }

    public function getShippingCostsNet(): float
    {
        return $this->shippingCostsNet;
    }

    public function setShippingCostsNet(float $shippingCostsNet): void
    {
        $this->shippingCostsNet = $shippingCostsNet;
    }

    public function getShippingCostsTaxRate(): float
    {
        return $this->shippingCostsTaxRate;
    }

    public function setShippingCostsTaxRate(float $shippingCostsTaxRate): void
    {
        $this->shippingCostsTaxRate = $shippingCostsTaxRate;
    }

    public function getPaymentId(): int
    {
        return $this->paymentId;
    }

    public function setPaymentId(int $paymentId): void
    {
        $this->paymentId = $paymentId;
    }

    public function getDispatchId(): int
    {
        return $this->dispatchId;
    }

    public function setDispatchId(int $dispatchId): void
    {
        $this->dispatchId = $dispatchId;
    }

    public function getLanguageShopId(): int
    {
        return $this->languageShopId;
    }

    public function setLanguageShopId(int $languageShopId): void
    {
        $this->languageShopId = $languageShopId;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): void
    {
        $this->currency = $currency;
    }

    public function getTotal(): float
    {
        return $this->total;
    }

    public function setTotal(float $total): void
    {
        $this->total = $total;
    }

    public function getDeviceType(): string
    {
        return $this->deviceType;
    }

    public function setDeviceType(string $deviceType): void
    {
        $this->deviceType = $deviceType;
    }

    public function getNetOrder(): bool
    {
        return $this->netOrder;
    }

    public function setNetOrder(bool $netOrder): void
    {
        $this->netOrder = $netOrder;
    }

    public function getSendMail(): bool
    {
        return $this->sendMail;
    }

    public function setSendMail(bool $sendMail): void
    {
        $this->sendMail = $sendMail;
    }

    /**
     * @return PositionStruct[]
     */
    public function getPositions(): array
    {
        return $this->positions;
    }

    /**
     * @param PositionStruct[] $positions
     */
    public function setPositions(array $positions): void
    {
        $this->positions = $positions;
    }

    public function addPosition(PositionStruct $position): void
    {
        $this->positions[] = $position;
    }

    public function setTotalWithoutTax(float $totalWithoutTax): void
    {
        $this->totalWithoutTax = $totalWithoutTax;
    }

    public function getTotalWithoutTax(): float
    {
        return $this->totalWithoutTax;
    }

    public function getCurrencyId(): int
    {
        return $this->currencyId;
    }

    public function setCurrencyId(int $currencyId): void
    {
        $this->currencyId = $currencyId;
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function setNumber(int $number): void
    {
        $this->number = $number;
    }

    public function isTaxFree(): bool
    {
        return $this->taxFree;
    }

    public function setTaxFree(bool $taxFree): void
    {
        $this->taxFree = $taxFree;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function setAttributes(array $attributes): void
    {
        $this->attributes = $attributes;
    }
}
