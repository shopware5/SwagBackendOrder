<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace SwagBackendOrder\Tests\Functional\Components\Order\Factory;

use PHPUnit\Framework\TestCase;
use Shopware\Models\Order\Detail;
use Shopware\Models\Tax\Tax;
use SwagBackendOrder\Components\Order\Factory\DetailFactory;
use SwagBackendOrder\Components\Order\Struct\PositionStruct;
use SwagBackendOrder\Components\Order\Validator\InvalidOrderException;
use SwagBackendOrder\Tests\Functional\ContainerTrait;

class DetailFactoryTest extends TestCase
{
    use ContainerTrait;

    public function testCreateWithDiscount(): void
    {
        $factory = $this->getContainer()->get('swag_backend_order.order.detail_factory');

        $positionStruct = new PositionStruct();
        $positionStruct->setMode(4);
        $positionStruct->setPrice(-10);
        $positionStruct->setNumber('DISCOUNT.0');
        $positionStruct->setName('Test_Discount');
        $positionStruct->setQuantity(1);
        $positionStruct->setProductId(0);
        $positionStruct->setTaxId(1);

        $result = $factory->create($positionStruct, true);
        static::assertInstanceOf(Detail::class, $result);
    }

    public function testCreateWillThrowExceptionIfNoNumberWasProvided(): void
    {
        $factory = $this->getContainer()->get('swag_backend_order.order.detail_factory');

        $positionStruct = new PositionStruct();
        $positionStruct->setMode(4);
        $positionStruct->setPrice(-10);
        $positionStruct->setName('Test_Discount');
        $positionStruct->setQuantity(1);
        $positionStruct->setProductId(0);

        $this->expectException(InvalidOrderException::class);
        $this->expectExceptionMessage('No product number was passed.');
        $factory->create($positionStruct, true);
    }

    public function testCreateEnsureProductDetailIsSet(): void
    {
        $factory = $this->getContainer()->get('swag_backend_order.order.detail_factory');

        $positionStruct = new PositionStruct();
        $positionStruct->setMode(1);
        $positionStruct->setPrice(19.95);
        $positionStruct->setProductId(178);
        $positionStruct->setNumber('SW10178');
        $positionStruct->setName('Beachtowel Ibiza');
        $positionStruct->setQuantity(1);
        $positionStruct->setTaxId(1);

        $result = $factory->create($positionStruct, false);

        static::assertNotNull($result->getArticleDetail());
    }

    public function testCreateWithDifferentTaxRule(): void
    {
        $modelManager = $this->getContainer()->get('models');
        $modules = $this->getContainer()->get('modules');
        $factory = new DetailFactory($modelManager, $modules);

        $positionStruct = new PositionStruct();
        $positionStruct->setNumber('SW10003');
        $positionStruct->setTaxId(1);
        $positionStruct->setTaxRate(20.0);
        $detail = $factory->create($positionStruct, false);

        static::assertSame(20.0, $detail->getTaxRate());
        static::assertInstanceOf(Tax::class, $detail->getTax());
        static::assertSame(1, $detail->getTax()->getId());
        static::assertNotSame($detail->getTax()->getTax(), $detail->getTaxRate());
    }
}
