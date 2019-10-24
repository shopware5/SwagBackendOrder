<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagBackendOrder\Components\Order\Validator\Validators;

use SwagBackendOrder\Components\Order\Validator\Constraints\CustomProduct;
use SwagBackendOrder\Components\Order\Validator\Constraints\EsdProduct;
use SwagBackendOrder\Components\Order\Validator\Constraints\LastStock;
use SwagBackendOrder\Components\Order\Validator\Constraints\ProductExists;
use SwagBackendOrder\Components\Order\Validator\ValidationResult;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProductValidator
{
    /**
     * @var ValidatorInterface
     */
    private $validator;

    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    /**
     * @return ValidationResult
     */
    public function validate(ProductContext $context)
    {
        $result = new ValidationResult();

        //A discount can not be validated like a regular product, since there is
        //no product referenced to it. Therefore, we have to early return the result here.
        if ($context->isDiscount()) {
            return $result;
        }

        $violationList = $this->validator->validate($context->getOrderNumber(), [
            new ProductExists(),
            new EsdProduct(),
            new CustomProduct(),
            new LastStock(['quantity' => $context->getQuantity()]),
        ]);

        /** @var ConstraintViolationInterface $violation */
        foreach ($violationList as $violation) {
            $result->addMessage($violation->getMessage());
        }

        return $result;
    }
}
