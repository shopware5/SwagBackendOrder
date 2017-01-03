<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagBackendOrder\Tests\Unit\Components\ConfirmationMail;

use Shopware\Models\Article\Repository;
use SwagBackendOrder\Components\ConfirmationMail\ConfirmationMailCreator;
use SwagBackendOrder\Components\ConfirmationMail\ConfirmationMailRepository;
use SwagBackendOrder\Components\ConfirmationMail\NumberFormatterWrapper;
use SwagBackendOrder\Components\Translation\ShippingTranslator;
use SwagBackendOrder\Components\Translation\PaymentTranslator;
use SwagBackendOrder\Components\PriceCalculation\TaxCalculation;

class ConfirmationMailCreatorTest extends \PHPUnit_Framework_TestCase
{
    public function test_it_can_be_created()
    {
        $confirmationMailCreator = new ConfirmationMailCreator(
            $this->createMock(TaxCalculation::class),
            $this->createMock(PaymentTranslator::class),
            $this->createMock(ShippingTranslator::class),
            $this->createMock(ConfirmationMailRepository::class),
            $this->createMock(Repository::class),
            $this->createMock(\Shopware_Components_Config::class),
            $this->createMock(NumberFormatterWrapper::class),
            $this->createMock(\sArticles::class)
        );

        $this->assertInstanceOf(ConfirmationMailCreator::class, $confirmationMailCreator);
    }
}