<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
     * @var int
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

    /**
     * @var bool
     */
    private $isSurcharge;

    /**
     * @var int
     */
    private $surchargeType;

    /**
     * @return float
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @param float $price
     */
    public function setPrice($price)
    {
        $this->price = $price;
    }

    /**
     * @return int
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * @param int $quantity
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;
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
     * @return int
     */
    public function getTaxRate()
    {
        return $this->taxRate;
    }

    /**
     * @param int $taxRate
     */
    public function setTaxRate($taxRate)
    {
        $this->taxRate = $taxRate;
    }

    /**
     * @return bool
     */
    public function getIsDiscount()
    {
        return $this->isDiscount;
    }

    /**
     * @param bool $isDiscount
     */
    public function setIsDiscount($isDiscount)
    {
        $this->isDiscount = $isDiscount;
    }

    /**
     * @return int
     */
    public function getDiscountType()
    {
        return $this->discountType;
    }

    /**
     * @param int $discountType
     */
    public function setDiscountType($discountType)
    {
        $this->discountType = $discountType;
    }

    /**
     * @return bool
     */
    public function isSurcharge()
    {
        return $this->isSurcharge;
    }

    /**
     * @param bool $isSurcharge
     */
    public function setIsSurcharge($isSurcharge)
    {
        $this->isSurcharge = $isSurcharge;
    }

    /**
     * @return int
     */
    public function getSurchargeType()
    {
        return $this->surchargeType;
    }

    /**
     * @param int $surchargeType
     */
    public function setSurchargeType($surchargeType)
    {
        $this->surchargeType = $surchargeType;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'price' => $this->getPrice(),
            'quantity' => $this->getQuantity(),
            'total' => $this->getTotal(),
            'taxRate' => $this->getTaxRate(),
            'isDiscount' => $this->getIsDiscount(),
            'discountType' => $this->getDiscountType(),
            'isSurcharge' => $this->isSurcharge(),
            'surchargeType' => $this->getSurchargeType(),
        ];
    }
}
