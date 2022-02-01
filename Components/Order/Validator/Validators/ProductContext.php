<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace SwagBackendOrder\Components\Order\Validator\Validators;

use SwagBackendOrder\Components\Order\Struct\PositionStruct;

class ProductContext
{
    /**
     * @var string
     */
    private $orderNumber;

    /**
     * @var int
     */
    private $quantity;

    public function __construct(string $orderNumber, int $quantity)
    {
        $this->orderNumber = $orderNumber;
        $this->quantity = $quantity;
    }

    public function getOrderNumber(): string
    {
        return $this->orderNumber;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    /**
     * Returns a value indicating whether this product is of type discount.
     */
    public function isDiscount(): bool
    {
        return \strpos($this->getOrderNumber(), PositionStruct::DISCOUNT_ORDER_NUMBER_PREFIX) === 0;
    }
}
