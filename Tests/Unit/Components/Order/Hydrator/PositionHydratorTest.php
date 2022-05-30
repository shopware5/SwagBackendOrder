<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace SwagBackendOrder\Tests\Unit\Components\Order\Hydrator;

use PHPUnit\Framework\TestCase;
use SwagBackendOrder\Components\Order\Hydrator\PositionHydrator;

class PositionHydratorTest extends TestCase
{
    private const FORMER_PHPUNIT_FLOAT_EPSILON = 0.0000000001;

    public function testHydrateValueCasting(): void
    {
        $data = [
            'mode' => '12',
            'articleId' => '12',
            'detailId' => '12',
            'articleNumber' => 8154711,
            'articleName' => 'PhpUnitTest ProductName',
            'quantity' => '12',
            'statusId' => '12',
            'taxRate' => '19.9',
            'taxId' => '12',
            'price' => '99.9',
            'total' => '199.9',
            'ean' => 'EAN-CODE',
        ];

        $position = (new PositionHydrator())->hydrate($data);

        // should be int values
        static::assertSame(12, $position->getMode());
        static::assertSame(12, $position->getProductId());
        static::assertSame(12, $position->getVariantId());
        static::assertSame(12, $position->getQuantity());
        static::assertSame(12, $position->getStatusId());
        static::assertSame(12, $position->getTaxId());
        // should be float values
        static::assertEqualsWithDelta(19.9, $position->getTaxRate(), self::FORMER_PHPUNIT_FLOAT_EPSILON);
        static::assertEqualsWithDelta(99.9, $position->getPrice(), self::FORMER_PHPUNIT_FLOAT_EPSILON);
        static::assertEqualsWithDelta(199.9, $position->getTotal(), self::FORMER_PHPUNIT_FLOAT_EPSILON);
        // should be float values
        static::assertSame('8154711', $position->getNumber());
        static::assertSame('PhpUnitTest ProductName', $position->getName());
        static::assertSame('EAN-CODE', $position->getEan());
    }
}
