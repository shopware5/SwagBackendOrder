<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace SwagBackendOrder\Tests\Functional\Components\ConfirmationMail;

use PHPUnit\Framework\TestCase;
use Shopware\Models\Article\Detail;
use Shopware\Models\Order\Detail as OrderDetailModel;
use Shopware\Models\Order\DetailStatus;
use Shopware\Models\Order\Order;
use Shopware_Components_Translation;
use SwagBackendOrder\Components\ConfirmationMail\ConfirmationMailCreator;
use SwagBackendOrder\Components\ConfirmationMail\ConfirmationMailRepository;
use SwagBackendOrder\Components\ConfirmationMail\NumberFormatterWrapper;
use SwagBackendOrder\Components\PriceCalculation\TaxCalculation;
use SwagBackendOrder\Components\Translation\PaymentTranslator;
use SwagBackendOrder\Components\Translation\ShippingTranslator;
use SwagBackendOrder\Tests\Functional\ContainerTrait;
use SwagBackendOrder\Tests\Functional\DatabaseTestCaseTrait;
use SwagBackendOrder\Tests\Functional\FixtureImportTestCaseTrait;

class ConfirmationMailCreatorTest extends TestCase
{
    use ContainerTrait;
    use FixtureImportTestCaseTrait;
    use DatabaseTestCaseTrait;

    public const ORDER_ID = 10000;

    public function testPrepareOrderConfirmationMailDataShouldReturnLocalizedBillingSums(): void
    {
        $this->importFixtures(__DIR__ . '/test-fixtures.sql');

        $confirmationMailCreator = $this->createConfirmationMailCreator();

        $order = $this->getContainer()->get('models')->find(Order::class, self::ORDER_ID);
        static::assertInstanceOf(Order::class, $order);
        $mailData = $confirmationMailCreator->prepareOrderConfirmationMailData($order);

        static::assertSame('63,89 EUR', $mailData['sAmount']);
        static::assertSame('53,69 EUR', $mailData['sAmountNet']);
        static::assertSame('3,90 EUR', $mailData['sShippingCosts']);
    }

    public function testPrepareOrderDetailsConfirmationMailDataShouldReturnLocalizedBillingSums(): void
    {
        $this->importFixtures(__DIR__ . '/test-fixtures.sql');

        $confirmationMailCreator = $this->createConfirmationMailCreator();

        $order = $this->getContainer()->get('models')->find(Order::class, self::ORDER_ID);
        static::assertInstanceOf(Order::class, $order);
        $orderDetails = $confirmationMailCreator->prepareOrderDetailsConfirmationMailData($order, $order->getLanguageSubShop()->getLocale());

        static::assertSame('50,41', $orderDetails[0]['netprice']);
        static::assertSame('59,99', $orderDetails[0]['amount']);
        static::assertSame('50,00', $orderDetails[0]['amountnet']);
        static::assertSame('59,99', $orderDetails[0]['priceNumeric']);
        static::assertSame('59,99', $orderDetails[0]['price']);
    }

    public function testPrepareOrderDetailsConfirmationMailDataWithDiscount(): void
    {
        $this->importFixtures(__DIR__ . '/test-fixtures.sql');

        // Insert the discount into the order
        $order = $this->getContainer()->get('models')->find(Order::class, self::ORDER_ID);
        static::assertInstanceOf(Order::class, $order);
        $confirmationMailCreator = $this->createConfirmationMailCreator();

        $this->insertDiscount($order);

        $orderDetails = $confirmationMailCreator->prepareOrderDetailsConfirmationMailData($order, $order->getLanguageSubShop()->getLocale());
        $discountDetails = $orderDetails[1];

        static::assertSame('DISCOUNT.0', $discountDetails['ordernumber']);
        static::assertSame('DISCOUNT.0', $discountDetails['ordernumber']);
        static::assertSame(4, (int) $discountDetails['modus']);
    }

    private function insertDiscount(Order $order): void
    {
        $detail = new OrderDetailModel();
        $detail->setTaxRate(0);
        $detail->setQuantity(1);
        $detail->setShipped(0);
        $detail->setOrder($order);
        $detail->setNumber((string) $order->getNumber());
        $detail->setArticleId(0);
        $detail->setArticleName('Discount (percentage)');
        $detail->setArticleNumber('DISCOUNT.0');
        $detail->setPrice(-10.0);
        $detail->setMode(4);
        $status = $this->getContainer()->get('models')->find(DetailStatus::class, 0);
        static::assertInstanceOf(DetailStatus::class, $status);
        $detail->setStatus($status);

        $em = $this->getContainer()->get('models');
        $em->persist($detail);
        $em->flush($detail);
    }

    private function createConfirmationMailCreator(): ConfirmationMailCreator
    {
        return new ConfirmationMailCreator(
            new TaxCalculation(),
            new PaymentTranslator(new Shopware_Components_Translation($this->getContainer()->get('dbal_connection'), $this->getContainer())),
            new ShippingTranslator(new Shopware_Components_Translation($this->getContainer()->get('dbal_connection'), $this->getContainer())),
            new ConfirmationMailRepository($this->getContainer()->get('dbal_connection')),
            $this->getContainer()->get('models')->getRepository(Detail::class),
            $this->getContainer()->get('config'),
            new NumberFormatterWrapper(),
            $this->getContainer()->get('modules')->Articles()
        );
    }
}
