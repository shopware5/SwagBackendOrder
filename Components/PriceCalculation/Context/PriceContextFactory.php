<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagBackendOrder\Components\PriceCalculation\Context;

use Shopware\Components\Model\ModelManager;
use Shopware\Models\Shop\Currency;

class PriceContextFactory
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
     * @param float $price
     * @param float $taxRate
     * @param boolean $taxFree
     * @param boolean $isNetPrice
     * @param int $currencyId
     * @return PriceContext
     */
    public function create($price, $taxRate, $taxFree, $isNetPrice, $currencyId)
    {
        $currency = $this->modelManager->find(Currency::class, $currencyId);
        if (null === $currency) {
            $currency = $this->getBaseCurrency();
        }

        return new PriceContext($price, $taxRate, $taxFree, $isNetPrice, $currency->getFactor());
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