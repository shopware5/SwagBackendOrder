<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagBackendOrder\Components\PriceCalculation;

use SwagBackendOrder\Components\PriceCalculation\Context\BasketContext;
use SwagBackendOrder\Components\PriceCalculation\Context\PriceContext;

class BasketPriceCalculator implements BasketPriceCalculatorInterface
{
    /**
     * @var TaxCalculation
     */
    private $taxCalculation;

    /**
     * @var CurrencyConverter
     */
    private $currencyCalculation;

    /**
     * @param TaxCalculation $taxCalculation
     * @param CurrencyConverter $currencyCalculation
     */
    public function __construct(TaxCalculation $taxCalculation, CurrencyConverter $currencyCalculation)
    {
        $this->taxCalculation = $taxCalculation;
        $this->currencyCalculation = $currencyCalculation;
    }

    /**
     * @param BasketContext $context
     * @param BasketContext $oldContext
     * @param PriceContext $priceContext
     * @return PriceStruct
     */
    public function calculateProductPrice(BasketContext $context, BasketContext $oldContext, PriceContext $priceContext)
    {
        $priceStruct = new PriceStruct();

        $baseNetPrice = $this->getBaseNetPrice($context, $oldContext, $priceContext->getPrice(), $priceContext->getTaxRate());
        $netPrice = $this->currencyCalculation->getCurrencyPrice($baseNetPrice, $context->getCurrencyFactor());
        $priceStruct->setNet($netPrice);

        $grossPrice = $this->taxCalculation->getGrossPrice($netPrice, $priceContext->getTaxRate());
        $priceStruct->setGross($grossPrice);

        return $priceStruct;
    }

    /**
     * @param BasketContext $context
     * @param BasketContext $oldContext
     * @param float $price
     * @return PriceStruct
     */
    public function calculateDispatchPrice(BasketContext $context, BasketContext $oldContext, $price)
    {
        $priceStruct = new PriceStruct();

        $baseGrossPrice = $this->getBaseGrossPrice($context, $oldContext, $price);
        $grossPrice = $this->currencyCalculation->getCurrencyPrice($baseGrossPrice, $context->getCurrencyFactor());

        $priceStruct->setGross($grossPrice);

        $netPrice = $this->taxCalculation->getNetPrice($grossPrice, $context->getDispatchTaxRate());
        $priceStruct->setNet($netPrice);

        return $priceStruct;
    }

    /**
     * @param BasketContext $context
     * @param BasketContext $oldContext
     * @param float $price
     * @return float
     */
    private function getBaseCurrencyPrice(BasketContext $context, BasketContext $oldContext, $price)
    {
        if ($this->hasCurrencyChanged($context, $oldContext)) {
            return $this->currencyCalculation->getBaseCurrencyPrice($price, $oldContext->getCurrencyFactor());
        }
        return $price;
    }

    /**
     * @param BasketContext $oldContext
     * @param float $price
     * @param float $taxRate
     * @return float
     */
    private function getPositionNetPrice(BasketContext $oldContext, $price, $taxRate)
    {
        if (!$oldContext->isNet()) {
            return $this->taxCalculation->getNetPrice($price, $taxRate);
        }
        return $price;
    }

    /**
     * @param BasketContext $context
     * @param BasketContext $oldContext
     * @return bool
     */
    private function hasCurrencyChanged(BasketContext $context, BasketContext $oldContext)
    {
        return $context->getCurrencyFactor() !== $oldContext->getCurrencyFactor() && $oldContext->getCurrencyFactor();
    }

    /**
     * @param BasketContext $context
     * @param BasketContext $oldContext
     * @param float $price
     * @param float $taxRate
     * @return float
     */
    private function getBaseNetPrice(BasketContext $context, BasketContext $oldContext, $price, $taxRate)
    {
        $baseCurrencyPrice = $this->getBaseCurrencyPrice($context, $oldContext, $price);
        return $this->getPositionNetPrice($oldContext, $baseCurrencyPrice, $taxRate);
    }

    /**
     * @param BasketContext $context
     * @param BasketContext $oldContext
     * @param float $price
     * @return float
     */
    private function getBaseGrossPrice(BasketContext $context, BasketContext $oldContext, $price)
    {
        $price = $this->getBaseCurrencyPrice($context, $oldContext, $price);
        if ($oldContext->isNet() && $oldContext->getDispatchTaxRate() > 0.00) {
            return $this->taxCalculation->getGrossPrice($price, $context->getDispatchTaxRate());
        }
        return $price;
    }
}
