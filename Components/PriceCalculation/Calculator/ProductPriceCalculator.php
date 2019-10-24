<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
     *
     * @return PriceResult
     */
    public function calculate(PriceContext $context)
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

    /**
     * @return float
     */
    public function calculateBasePrice(PriceContext $priceContext)
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

    /**
     * @param float $price
     *
     * @return float
     */
    public function calculatePrice($price, Tax $tax, ShopContextInterface $context)
    {
        $customerGroup = $context->getCurrentCustomerGroup();

        if ($customerGroup->useDiscount() && $customerGroup->getPercentageDiscount()) {
            $price = $price - ($price / 100 * $customerGroup->getPercentageDiscount());
        }

        $price = $price * $context->getCurrency()->getFactor();

        if (!$customerGroup->displayGrossPrices()) {
            return $price;
        }

        $price = $price * (100 + $tax->getTax()) / 100;

        return $price;
    }
}
