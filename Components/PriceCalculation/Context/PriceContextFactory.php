<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
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

    public function __construct(ModelManager $modelManager)
    {
        $this->modelManager = $modelManager;
    }

    public function create(float $price, float $taxRate, bool $taxFree, bool $isNetPrice, int $currencyId): PriceContext
    {
        $currency = $this->modelManager->find(Currency::class, $currencyId);
        if (!$currency instanceof Currency) {
            $currency = $this->getBaseCurrency();
        }

        return new PriceContext($price, $taxRate, $taxFree, $isNetPrice, $currency->getFactor());
    }

    private function getBaseCurrency(): Currency
    {
        $currency = $this->modelManager->getRepository(Currency::class)->findOneBy(['default' => 1]);
        if (!$currency instanceof Currency) {
            throw new \RuntimeException('Default currency not found');
        }

        return $currency;
    }
}
