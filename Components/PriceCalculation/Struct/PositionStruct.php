<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace SwagBackendOrder\Components\PriceCalculation\Struct;

class PositionStruct
{
    /**
     * @var float
     */
    private $price;

    /**
     * @var int
     */
    private $quantity;

    /**
     * @var float
     */
    private $total;

    /**
     * @var float
     */
    private $taxRate;

    /**
     * @var bool
     */
    private $isDiscount;

    /**
     * @var int
     */
    private $discountType;

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getNetPrice(): float
    {
        return (float) ($this->price / (1.0 + ($this->taxRate / 100)));
    }

    public function setPrice(float $price): void
    {
        $this->price = $price;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): void
    {
        $this->quantity = $quantity;
    }

    public function getTotal(): float
    {
        return $this->total;
    }

    public function setTotal(float $total): void
    {
        $this->total = $total;
    }

    public function getTaxRate(): float
    {
        return $this->taxRate;
    }

    public function setTaxRate(float $taxRate): void
    {
        $this->taxRate = $taxRate;
    }

    public function getIsDiscount(): bool
    {
        return $this->isDiscount;
    }

    public function setIsDiscount(bool $isDiscount): void
    {
        $this->isDiscount = $isDiscount;
    }

    public function getDiscountType(): int
    {
        return $this->discountType;
    }

    public function setDiscountType(int $discountType): void
    {
        $this->discountType = $discountType;
    }

    /**
     * @return array{price: float, quantity: int, total: float, taxRate: float, isDiscount: bool, discountType: int}
     */
    public function toArray(): array
    {
        return [
            'price' => $this->getPrice(),
            'quantity' => $this->getQuantity(),
            'total' => $this->getTotal(),
            'taxRate' => $this->getTaxRate(),
            'isDiscount' => $this->getIsDiscount(),
            'discountType' => $this->getDiscountType(),
        ];
    }
}
