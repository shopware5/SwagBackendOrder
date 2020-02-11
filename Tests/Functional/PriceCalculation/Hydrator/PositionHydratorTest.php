<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagBackendOrder\Tests\Functional\PriceCalculation\Hydrator;

use PHPUnit\Framework\TestCase;
use SwagBackendOrder\Components\PriceCalculation\Hydrator\PositionHydrator;

class PositionHydratorTest extends TestCase
{
    public function test_hydrate_valueCasting()
    {
        $data = [
            'price' => '99.99',
            'quantity' => '12',
            'total' => '99.99',
            'taxRate' => '22.2',
            'isDiscount' => '1',
            'discountType' => '12',
        ];

        $position = (new PositionHydrator())->hydrate($data);

        // Should be float values
        static::assertSame(99.99, $position->getPrice());
        static::assertSame(99.99, $position->getTotal());
        static::assertSame(22.2, $position->getTaxRate());

        // Should be int values
        static::assertSame(12, $position->getQuantity());
        static::assertSame(12, $position->getDiscountType());

        // Should be a boolean value
        static::assertTrue($position->getIsDiscount());
    }
}
