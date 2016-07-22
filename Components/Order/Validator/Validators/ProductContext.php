<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagBackendOrder\Components\Order\Validator\Validators;

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

    /**
     * @param string $orderNumber
     * @param int $quantity
     */
    public function __construct($orderNumber, $quantity)
    {
        $this->orderNumber = $orderNumber;
        $this->quantity = $quantity;
    }

    /**
     * @return string
     */
    public function getOrderNumber()
    {
        return $this->orderNumber;
    }

    /**
     * @return int
     */
    public function getQuantity()
    {
        return $this->quantity;
    }
}