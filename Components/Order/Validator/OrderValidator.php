<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagBackendOrder\Components\Order\Validator;

use Shopware\Components\Model\ModelManager;
use SwagBackendOrder\Components\Order\Struct\OrderStruct;
use SwagBackendOrder\Components\Order\Struct\PositionStruct;
use SwagBackendOrder\Components\Order\Validator\Validators\ProductContext;
use SwagBackendOrder\Components\Order\Validator\Validators\ProductValidator;

class OrderValidator implements OrderValidatorInterface
{
    /**
     * @var ModelManager
     */
    private $modelManager;

    /**
     * @var \Shopware_Components_Snippet_Manager
     */
    private $snippetManager;

    /**
     * @var ProductValidator
     */
    private $productValidator;

    public function __construct(
        ProductValidator $productValidator,
        ModelManager $modelManager,
        \Shopware_Components_Snippet_Manager $snippetManager
    ) {
        $this->modelManager = $modelManager;
        $this->snippetManager = $snippetManager;
        $this->productValidator = $productValidator;
    }

    /**
     * @return ValidationResult
     */
    public function validate(OrderStruct $order)
    {
        $result = new ValidationResult();

        /** @var PositionStruct[] $positions */
        $positions = $order->getPositions();
        $violations = $this->validateOrderDetails($positions);

        $result->addMessages($violations->getMessages());

        return $result;
    }

    /**
     * @param PositionStruct[] $positions
     *
     * @return ValidationResult
     */
    protected function validateOrderDetails(array $positions)
    {
        $violations = new ValidationResult();

        /** @var PositionStruct $position */
        foreach ($positions as $position) {
            $result = $this->productValidator->validate($this->getProductContext($position));
            $violations->addMessages($result->getMessages());
        }

        return $violations;
    }

    /**
     * @return ProductContext
     */
    private function getProductContext(PositionStruct $positionStruct)
    {
        return new ProductContext($positionStruct->getNumber(), $positionStruct->getQuantity());
    }
}
