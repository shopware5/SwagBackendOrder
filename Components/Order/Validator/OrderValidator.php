<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace SwagBackendOrder\Components\Order\Validator;

use SwagBackendOrder\Components\Order\Struct\OrderStruct;
use SwagBackendOrder\Components\Order\Struct\PositionStruct;
use SwagBackendOrder\Components\Order\Validator\Validators\ProductContext;
use SwagBackendOrder\Components\Order\Validator\Validators\ProductValidator;

class OrderValidator implements OrderValidatorInterface
{
    /**
     * @var ProductValidator
     */
    private $productValidator;

    public function __construct(
        ProductValidator $productValidator
    ) {
        $this->productValidator = $productValidator;
    }

    public function validate(OrderStruct $order): ValidationResult
    {
        $result = new ValidationResult();

        $positions = $order->getPositions();
        $violations = $this->validateOrderDetails($positions);

        $result->addMessages($violations->getMessages());

        return $result;
    }

    /**
     * @param PositionStruct[] $positions
     */
    private function validateOrderDetails(array $positions): ValidationResult
    {
        $violations = new ValidationResult();

        foreach ($positions as $position) {
            $result = $this->productValidator->validate($this->getProductContext($position));
            $violations->addMessages($result->getMessages());
        }

        return $violations;
    }

    private function getProductContext(PositionStruct $positionStruct): ProductContext
    {
        return new ProductContext($positionStruct->getNumber(), $positionStruct->getQuantity());
    }
}
