<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace SwagBackendOrder\Components\PriceCalculation\Struct;

/**
 * @phpstan-type PositionArray list<array{price: float, quantity: int, total: float, taxRate: float, isDiscount: bool, discountType: int}>
 */
class RequestStruct
{
    /**
     * @var PositionStruct[]
     */
    private $positions = [];

    /**
     * @var bool
     */
    private $taxFree;

    /**
     * @var bool
     */
    private $previousTaxFree;

    /**
     * @var int|null
     */
    private $dispatchId;

    /**
     * @var int
     */
    private $currencyId;

    /**
     * @var int
     */
    private $previousCurrencyId;

    /**
     * @var float
     */
    private $shippingCosts;

    /**
     * @var float
     */
    private $shippingCostsNet;

    /**
     * @var float
     */
    private $previousShippingTaxRate;

    /**
     * @var float[]
     */
    private $basketTaxRates;

    /**
     * @var bool
     */
    private $displayNet;

    /**
     * @var bool
     */
    private $previousDisplayNet;

    /**
     * @return PositionStruct[]
     */
    public function getPositions(): array
    {
        return $this->positions;
    }

    /**
     * @return PositionArray
     */
    public function getPositionsArray(): array
    {
        $positions = [];
        foreach ($this->getPositions() as $position) {
            $positions[] = $position->toArray();
        }

        return $positions;
    }

    /**
     * @param PositionStruct[] $positions
     */
    public function setPositions(array $positions): void
    {
        $this->positions = $positions;
    }

    public function isTaxFree(): bool
    {
        return $this->taxFree;
    }

    public function setTaxFree(bool $taxFree): void
    {
        $this->taxFree = $taxFree;
    }

    public function isPreviousTaxFree(): bool
    {
        return $this->previousTaxFree;
    }

    public function setPreviousTaxFree(bool $taxFreeChanged): void
    {
        $this->previousTaxFree = $taxFreeChanged;
    }

    public function getDispatchId(): ?int
    {
        return $this->dispatchId;
    }

    public function setDispatchId(int $dispatchId): void
    {
        $this->dispatchId = $dispatchId;
        if ($this->dispatchId === 0) {
            $this->dispatchId = null;
        }
    }

    public function getCurrencyId(): int
    {
        return $this->currencyId;
    }

    public function setCurrencyId(int $currencyId): void
    {
        $this->currencyId = $currencyId;
    }

    public function getPreviousCurrencyId(): int
    {
        return $this->previousCurrencyId;
    }

    public function setPreviousCurrencyId(int $previousCurrencyId): void
    {
        $this->previousCurrencyId = $previousCurrencyId;
    }

    public function getShippingCosts(): float
    {
        return $this->shippingCosts;
    }

    public function setShippingCosts(float $shippingCosts): void
    {
        $this->shippingCosts = $shippingCosts;
    }

    public function getShippingCostsNet(): float
    {
        return $this->shippingCostsNet;
    }

    public function setShippingCostsNet(float $shippingCostsNet): void
    {
        $this->shippingCostsNet = $shippingCostsNet;
    }

    /**
     * @return float[]
     */
    public function getBasketTaxRates(): array
    {
        return $this->basketTaxRates;
    }

    /**
     * @param float[] $basketTaxRates
     */
    public function setBasketTaxRates(array $basketTaxRates): void
    {
        $this->basketTaxRates = $basketTaxRates;
    }

    public function isPreviousDisplayNet(): bool
    {
        return $this->previousDisplayNet;
    }

    public function setPreviousDisplayNet(bool $previousNetChanged): void
    {
        $this->previousDisplayNet = $previousNetChanged;
    }

    public function isDisplayNet(): bool
    {
        return $this->displayNet;
    }

    public function setDisplayNet(bool $displayNet): void
    {
        $this->displayNet = $displayNet;
    }

    public function getPreviousShippingTaxRate(): float
    {
        return $this->previousShippingTaxRate;
    }

    public function setPreviousShippingTaxRate(float $previousShippingTaxRate): void
    {
        $this->previousShippingTaxRate = $previousShippingTaxRate;
    }
}
