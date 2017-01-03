<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagBackendOrder\Tests\Functional\Components\ConfirmationMail;

use Shopware\Models\Article\Detail;
use Shopware\Models\Order\Order;
use Shopware_Components_Translation;
use SwagBackendOrder\Components\ConfirmationMail\ConfirmationMailCreator;
use SwagBackendOrder\Components\ConfirmationMail\ConfirmationMailRepository;
use SwagBackendOrder\Components\ConfirmationMail\NumberFormatterWrapper;
use SwagBackendOrder\Components\PriceCalculation\TaxCalculation;
use SwagBackendOrder\Components\Translation\PaymentTranslator;
use SwagBackendOrder\Components\Translation\ShippingTranslator;
use SwagBackendOrder\Tests\DatabaseTestCaseTrait;
use SwagBackendOrder\Tests\FixtureImportTestCaseTrait;

class ConfirmationMailCreatorTest extends \PHPUnit_Framework_TestCase
{
    use DatabaseTestCaseTrait;
    use FixtureImportTestCaseTrait;

    const ORDER_ID = 10000;

    /**
     * @return ConfirmationMailCreator
     */
    private function createConfirmationMailCreator()
    {
        return new ConfirmationMailCreator(
            new TaxCalculation(),
            new PaymentTranslator(new Shopware_Components_Translation()),
            new ShippingTranslator(new Shopware_Components_Translation()),
            new ConfirmationMailRepository(Shopware()->Container()->get('dbal_connection')),
            Shopware()->Models()->getRepository(Detail::class),
            Shopware()->Container()->get('config'),
            new NumberFormatterWrapper(),
            Shopware()->Modules()->Articles()
        );
    }

    public function test_prepareOrderConfirmationMailData_should_return_localized_billing_sums()
    {
        $this->importFixtures(__DIR__ . '/test-fixtures.sql');

        $confirmationMailCreator = $this->createConfirmationMailCreator();

        $order = Shopware()->Models()->find(Order::class, self::ORDER_ID);
        $mailData = $confirmationMailCreator->prepareOrderConfirmationMailData($order);

        $this->assertEquals('63,89 EUR', $mailData['sAmount']);
        $this->assertEquals('53,69 EUR', $mailData['sAmountNet']);
        $this->assertEquals('3,90 EUR', $mailData['sShippingCosts']);
    }

    public function test_prepareOrderDetailsConfirmationMailData_should_return_localized_billing_sums()
    {
        $this->importFixtures(__DIR__ . '/test-fixtures.sql');

        $confirmationMailCreator = $this->createConfirmationMailCreator();

        $order = Shopware()->Models()->find(Order::class, self::ORDER_ID);
        $orderDetails = $confirmationMailCreator->prepareOrderDetailsConfirmationMailData($order, $order->getLanguageSubShop()->getLocale());

        $this->assertEquals('50,41', $orderDetails[0]['netprice']);
        $this->assertEquals('59,99', $orderDetails[0]['amount']);
        $this->assertEquals('50,00', $orderDetails[0]['amountnet']);
        $this->assertEquals('59,99', $orderDetails[0]['priceNumeric']);
        $this->assertEquals('59,99', $orderDetails[0]['price']);
    }
}