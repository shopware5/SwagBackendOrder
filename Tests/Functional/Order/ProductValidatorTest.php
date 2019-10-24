<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagBackendOrder\Tests\Functional\Order;

use PHPUnit\Framework\TestCase;
use SwagBackendOrder\Components\Order\Validator\Validators\ProductContext;
use SwagBackendOrder\Components\Order\Validator\Validators\ProductValidator;
use SwagBackendOrder\Tests\DatabaseTestCaseTrait;

class ProductValidatorTest extends TestCase
{
    use DatabaseTestCaseTrait;

    public function testValidate()
    {
        $validator = $this->getProductValidator();
        $quantity = 10;

        $context = new ProductContext($this->getProductNumberWithActivatedOnSale(), $quantity);
        $violation = $validator->validate($context);

        static::assertStringContainsString($this->getProductNumberWithActivatedOnSale(), $violation->getMessages()[0]);
    }

    /**
     * @return ProductValidator
     */
    private function getProductValidator()
    {
        return Shopware()->Container()->get('swag_backend_order.order.product_validator');
    }

    /**
     * @return string
     */
    private function getProductNumberWithActivatedOnSale()
    {
        return 'SW10198';
    }
}
