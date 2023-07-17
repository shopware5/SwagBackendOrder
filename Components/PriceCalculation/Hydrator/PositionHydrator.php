<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace SwagBackendOrder\Components\PriceCalculation\Hydrator;

use Shopware\Bundle\StoreFrontBundle\Service\Core\ShopContextFactoryInterface;
use Shopware\Bundle\StoreFrontBundle\Struct\Tax as TaxStruct;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Country\Area;
use Shopware\Models\Country\Country;
use Shopware\Models\Customer\Address;
use SwagBackendOrder\Components\PriceCalculation\Struct\PositionStruct;

class PositionHydrator
{
    /**
     * @var ModelManager
     */
    private $modelManager;

    /**
     * @var ShopContextFactoryInterface
     */
    private $shopContextFactory;

    public function __construct(ModelManager $modelManager, ShopContextFactoryInterface $shopContextFactory)
    {
        $this->modelManager = $modelManager;
        $this->shopContextFactory = $shopContextFactory;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function hydrate(array $data, int $shippingAddressId = 0): PositionStruct
    {
        $position = new PositionStruct();
        $position->setPrice((float) $data['price']);
        $position->setQuantity((int) $data['quantity']);
        $position->setTotal((float) $data['total']);
        $position->setTaxRate($this->calculateTax((float) $data['taxRate'], $shippingAddressId, (int) $data['taxId']));
        $position->setIsDiscount((bool) $data['isDiscount']);
        $position->setDiscountType((int) $data['discountType']);

        return $position;
    }

    private function calculateTax(float $taxRate, int $shippingAddressId, int $taxId): float
    {
        if ($shippingAddressId === 0) {
            return $taxRate;
        }

        $shippingAddress = $this->modelManager->getRepository(Address::class)->find($shippingAddressId);
        if (!$shippingAddress instanceof Address) {
            return $taxRate;
        }

        $shop = $shippingAddress->getCustomer()->getShop();
        $areaId = null;
        $countryId = null;

        $country = $shippingAddress->getCountry();
        if ($country instanceof Country) {
            $countryId = $country->getId();

            $area = $country->getArea();
            if ($area instanceof Area) {
                $areaId = $area->getId();
            }
        }

        $shopContext = $this->shopContextFactory->create(
            $shop->getBaseUrl() ?? '',
            $shop->getId(),
            null,
            null,
            $areaId,
            $countryId
        );

        $taxRule = $shopContext->getTaxRule($taxId);
        if ($taxRule instanceof TaxStruct) {
            return (float) $taxRule->getTax();
        }

        return $taxRate;
    }
}
