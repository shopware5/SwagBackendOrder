<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagBackendOrder\Tests\Functional\Order;

use PHPUnit\Framework\TestCase;
use Shopware\Models\Order\Detail;
use SwagBackendOrder\Components\Order\Factory\DetailFactory;
use SwagBackendOrder\Components\Order\Struct\PositionStruct;
use SwagBackendOrder\Components\Order\Validator\InvalidOrderException;

class DetailFactoryTest extends TestCase
{
    public function test_create_with_discount()
    {
        /** @var DetailFactory $factory */
        $factory = Shopware()->Container()->get('swag_backend_order.order.detail_factory');

        $positionStruct = new PositionStruct();
        $positionStruct->setMode(4);
        $positionStruct->setPrice(-10);
        $positionStruct->setNumber('DISCOUNT.0');
        $positionStruct->setName('Test_Discount');
        $positionStruct->setQuantity(1);
        $positionStruct->setArticleId(0);
        $positionStruct->setTaxId(1);

        $result = $factory->create($positionStruct, true);
        static::assertInstanceOf(Detail::class, $result);
    }

    public function test_create_will_throw_exception_if_no_number_was_provided()
    {
        /** @var DetailFactory $factory */
        $factory = Shopware()->Container()->get('swag_backend_order.order.detail_factory');

        $positionStruct = new PositionStruct();
        $positionStruct->setMode(4);
        $positionStruct->setPrice(-10);
        $positionStruct->setName('Test_Discount');
        $positionStruct->setQuantity(1);
        $positionStruct->setArticleId(0);

        $this->expectException(InvalidOrderException::class);
        $this->expectExceptionMessage('No product number was passed.');
        $factory->create($positionStruct, true);
    }

    public function test_create_ensureProductDetailIsSet()
    {
        $factory = Shopware()->Container()->get('swag_backend_order.order.detail_factory');

        $positionStruct = new PositionStruct();
        $positionStruct->setMode(1);
        $positionStruct->setPrice(19.95);
        $positionStruct->setArticleId(178);
        $positionStruct->setNumber('SW10178');
        $positionStruct->setName('Beachtowel Ibiza');
        $positionStruct->setQuantity(1);
        $positionStruct->setTaxId(1);

        $result = $factory->create($positionStruct, false);

        static::assertNotNull($result->getArticleDetail());
    }
}
