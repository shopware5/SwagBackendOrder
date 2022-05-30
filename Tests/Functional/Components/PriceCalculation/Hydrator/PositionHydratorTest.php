<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace SwagBackendOrder\Tests\Functional\Components\PriceCalculation\Hydrator;

use PHPUnit\Framework\TestCase;
use Shopware\Bundle\StoreFrontBundle\Service\Core\ShopContextFactoryInterface;
use SwagBackendOrder\Components\PriceCalculation\Hydrator\PositionHydrator;
use SwagBackendOrder\Tests\Functional\ContainerTrait;

class PositionHydratorTest extends TestCase
{
    use ContainerTrait;

    private const FORMER_PHPUNIT_FLOAT_EPSILON = 0.0000000001;

    public function testHydrateValueCasting(): void
    {
        $data = [
            'price' => '99.99',
            'quantity' => '12',
            'total' => '99.99',
            'taxRate' => '22.2',
            'isDiscount' => '1',
            'discountType' => '12',
        ];

        $position = (new PositionHydrator(
            $this->getContainer()->get('models'),
            $this->getContainer()->get(ShopContextFactoryInterface::class)
        ))->hydrate($data);

        // Should be float values
        static::assertEqualsWithDelta(99.99, $position->getPrice(), self::FORMER_PHPUNIT_FLOAT_EPSILON);
        static::assertEqualsWithDelta(99.99, $position->getTotal(), self::FORMER_PHPUNIT_FLOAT_EPSILON);
        static::assertEqualsWithDelta(22.2, $position->getTaxRate(), self::FORMER_PHPUNIT_FLOAT_EPSILON);

        // Should be int values
        static::assertSame(12, $position->getQuantity());
        static::assertSame(12, $position->getDiscountType());

        // Should be a boolean value
        static::assertTrue($position->getIsDiscount());
    }
}
