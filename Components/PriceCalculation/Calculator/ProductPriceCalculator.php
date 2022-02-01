<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace SwagBackendOrder\Components\PriceCalculation\Calculator;

use Shopware\Bundle\StoreFrontBundle\Struct\ShopContextInterface;
use Shopware\Bundle\StoreFrontBundle\Struct\Tax;
use SwagBackendOrder\Components\PriceCalculation\Context\PriceContext;
use SwagBackendOrder\Components\PriceCalculation\CurrencyConverter;
use SwagBackendOrder\Components\PriceCalculation\Result\PriceResult;
use SwagBackendOrder\Components\PriceCalculation\TaxCalculation;

class ProductPriceCalculator
{
    /**
     * @var TaxCalculation
     */
    private $taxCalculation;

    /**
     * @var CurrencyConverter
     */
    private $currencyConverter;

    public function __construct(TaxCalculation $taxCalculation, CurrencyConverter $currencyConverter)
    {
        $this->taxCalculation = $taxCalculation;
        $this->currencyConverter = $currencyConverter;
    }

    /**
     * @throws \RuntimeException
     */
    public function calculate(PriceContext $context): PriceResult
    {
        if (!$context->isNetPrice()) {
            throw new \RuntimeException('The given price is not a net price.');
        }

        $result = new PriceResult();

        $netPrice = $this->currencyConverter->getCurrencyPrice($context->getPrice(), $context->getCurrencyFactor());
        $result->setNet($netPrice);

        $grossPrice = $this->taxCalculation->getGrossPrice($netPrice, $context->getTaxRate());
        $result->setGross($grossPrice);

        $result->setTaxRate($context->getTaxRate());

        return $result;
    }

    public function calculateBasePrice(PriceContext $priceContext): float
    {
        $baseCurrencyPrice = $this->currencyConverter->getBaseCurrencyPrice(
            $priceContext->getPrice(),
            $priceContext->getCurrencyFactor()
        );

        $basePrice = $baseCurrencyPrice;
        if ($priceContext->isNetPrice() || $priceContext->isTaxFree()) {
            return $basePrice;
        }

        return $this->taxCalculation->getNetPrice($baseCurrencyPrice, $priceContext->getTaxRate());
    }

    public function calculatePrice(float $price, Tax $tax, ShopContextInterface $context): float
    {
        $customerGroup = $context->getCurrentCustomerGroup();

        if ($customerGroup->useDiscount() && $customerGroup->getPercentageDiscount()) {
            $price -= ($price / 100 * $customerGroup->getPercentageDiscount());
        }

        $price *= $context->getCurrency()->getFactor();

        if (!$customerGroup->displayGrossPrices()) {
            return $price;
        }

        return $price * (100 + $tax->getTax()) / 100;
    }
}
