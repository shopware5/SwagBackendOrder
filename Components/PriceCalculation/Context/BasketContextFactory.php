<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagBackendOrder\Components\PriceCalculation\Context;

use Shopware\Components\Model\ModelManager;
use Shopware\Models\Dispatch\Dispatch;
use Shopware\Models\Shop\Currency;
use Shopware\Models\Tax\Tax;

class BasketContextFactory
{
    /**
     * @var ModelManager
     */
    private $modelManager;

    /**
     * @param ModelManager $modelManager
     */
    public function __construct(ModelManager $modelManager)
    {
        $this->modelManager = $modelManager;
    }

    /**
     * @param int $currencyId
     * @param int|null $dispatchId
     * @param float[] $basketTaxRates - All available tax rates
     * @param bool $net
     * @return BasketContext
     */
    public function create(
        $currencyId,
        $dispatchId = null,
        array $basketTaxRates = [],
        $net = false
    ) {
        $currencyFactor = $this->getCurrencyFactor($currencyId);

        $dispatchTaxRate = 0.00;
        if (!is_null($dispatchId)) {
            $dispatchTaxRate = $this->getDispatchTaxRate($dispatchId, $basketTaxRates);
        }

        return new BasketContext($currencyFactor, $net, $dispatchTaxRate);
    }

    /**
     * @param int $currencyId
     * @return float|null
     */
    private function getCurrencyFactor($currencyId)
    {
        $currency = $this->modelManager->find(Currency::class, $currencyId);
        if (is_null($currency)) {
            $currency = $this->getBaseCurrency();
        }
        return $currency->getFactor();
    }

    /**
     * @param int $dispatchId
     * @param float[] $basketTaxRates
     * @return float
     * @throws \Exception
     */
    private function getDispatchTaxRate($dispatchId, array $basketTaxRates)
    {
        /** @var Dispatch $dispatch */
        $dispatch = $this->modelManager->find(Dispatch::class, $dispatchId);

        if (is_null($dispatch)) {
            throw new \Exception("Can not find given dispatch with id " . $dispatchId);
        }

        $taxId = $dispatch->getTaxCalculation();
        $tax = $this->modelManager->find(Tax::class, $taxId);

        if (!is_null($tax)) {
            return $tax->getTax();
        }
        return $this->getHighestDispatchTaxRate($basketTaxRates);
    }

    /**
     * @param float[] $basketTaxRates
     * @return float
     */
    private function getHighestDispatchTaxRate(array $basketTaxRates)
    {
        return max($basketTaxRates);
    }

    /**
     * @return Currency
     */
    private function getBaseCurrency()
    {
        $repository = $this->modelManager->getRepository(Currency::class);
        return $repository->findOneBy([ 'default' => 1 ]);
    }
}
