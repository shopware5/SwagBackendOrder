<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace SwagBackendOrder\Tests\Functional\Components\Order\Validator\Validators;

use PHPUnit\Framework\TestCase;
use SwagBackendOrder\Components\Order\Validator\Validators\ProductContext;
use SwagBackendOrder\Components\Order\Validator\Validators\ProductValidator;
use SwagBackendOrder\Tests\Functional\ContainerTrait;
use SwagBackendOrder\Tests\Functional\DatabaseTestCaseTrait;

class ProductValidatorTest extends TestCase
{
    use ContainerTrait;
    use DatabaseTestCaseTrait;

    public function testValidate(): void
    {
        $validator = $this->getProductValidator();
        $quantity = 10;

        $context = new ProductContext($this->getProductNumberWithActivatedOnSale(), $quantity);
        $violation = $validator->validate($context);

        static::assertStringContainsString($this->getProductNumberWithActivatedOnSale(), $violation->getMessages()[0]);
    }

    private function getProductValidator(): ProductValidator
    {
        return $this->getContainer()->get('swag_backend_order.order.product_validator');
    }

    private function getProductNumberWithActivatedOnSale(): string
    {
        return 'SW10198';
    }
}
