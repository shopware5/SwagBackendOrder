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

class PositionStruct
{
    public const DISCOUNT_ORDER_NUMBER_PREFIX = 'DISCOUNT.';

    /**
     * @var int
     */
    private $orderId;

    /**
     * @var int
     */
    private $mode = 0;

    /**
     * @var int
     */
    private $productId;

    /**
     * @var int
     */
    private $variantId;

    /**
     * @var string
     */
    private $number = '';

    /**
     * @var string
     */
    private $name;

    /**
     * @var int
     */
    private $quantity = 1;

    /**
     * @var int
     */
    private $statusId;

    /**
     * @var float
     */
    private $taxRate = 0.0;

    /**
     * @var int
     */
    private $taxId;

    /**
     * @var float
     */
    private $price = 0.0;

    /**
     * @var float
     */
    private $total = 0.0;

    /**
     * @var string|null
     */
    private $ean;

    public function getOrderId(): int
    {
        return $this->orderId;
    }

    public function setOrderId(int $orderId): void
    {
        $this->orderId = $orderId;
    }

    public function getMode(): int
    {
        return $this->mode;
    }

    public function setMode(int $mode): void
    {
        $this->mode = $mode;
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function setProductId(int $productId): void
    {
        $this->productId = $productId;
    }

    public function getVariantId(): int
    {
        return $this->variantId;
    }

    public function setVariantId(int $variantId): void
    {
        $this->variantId = $variantId;
    }

    public function getNumber(): string
    {
        return $this->number;
    }

    public function setNumber(string $number): void
    {
        $this->number = $number;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): void
    {
        $this->quantity = $quantity;
    }

    public function getStatusId(): int
    {
        return $this->statusId;
    }

    public function setStatusId(int $statusId): void
    {
        $this->statusId = $statusId;
    }

    public function getTaxRate(): float
    {
        return $this->taxRate;
    }

    public function setTaxRate(float $taxRate): void
    {
        $this->taxRate = $taxRate;
    }

    public function getTaxId(): int
    {
        return $this->taxId;
    }

    public function setTaxId(int $taxId): void
    {
        $this->taxId = $taxId;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function setPrice(float $price): void
    {
        $this->price = $price;
    }

    public function getTotal(): float
    {
        return $this->total;
    }

    public function setTotal(float $total): void
    {
        $this->total = $total;
    }

    public function isDiscount(): bool
    {
        return \strpos($this->getNumber(), self::DISCOUNT_ORDER_NUMBER_PREFIX) === 0;
    }

    public function getDiscountType(): int
    {
        return (int) \explode('.', $this->getNumber())[1];
    }

    public function getEan(): ?string
    {
        return $this->ean;
    }

    public function setEan(?string $ean): void
    {
        $this->ean = $ean;
    }
}
