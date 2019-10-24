<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagBackendOrder\Components\ProductSearch;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Shopware\Bundle\StoreFrontBundle\Service\AdditionalTextServiceInterface;
use Shopware\Bundle\StoreFrontBundle\Service\ContextServiceInterface;
use Shopware\Bundle\StoreFrontBundle\Struct\Product;
use Shopware\Bundle\StoreFrontBundle\Struct\ShopContextInterface;
use Shopware\Bundle\StoreFrontBundle\Struct\Tax as TaxStruct;
use SwagBackendOrder\Components\PriceCalculation\Calculator\ProductPriceCalculator;
use SwagBackendOrder\Components\PriceCalculation\Hydrator\RequestHydrator;
use SwagBackendOrder\Components\PriceCalculation\Result\PriceResult;

class ProductSearch implements ProductSearchInterface
{
    /**
     * @var int
     */
    private $totalCount = 0;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var ProductPriceCalculator
     */
    private $productPriceCalculator;

    /**
     * @var ContextServiceInterface
     */
    private $contextService;

    /**
     * @var RequestHydrator
     */
    private $requestHydrator;

    /**
     * @var AdditionalTextServiceInterface
     */
    private $additionalTextService;

    public function __construct(
        Connection $connection,
        ProductPriceCalculator $productPriceCalculator,
        ContextServiceInterface $contextService,
        RequestHydrator $requestHydrator,
        AdditionalTextServiceInterface $additionalTextService
    ) {
        $this->connection = $connection;
        $this->productPriceCalculator = $productPriceCalculator;
        $this->contextService = $contextService;
        $this->requestHydrator = $requestHydrator;
        $this->additionalTextService = $additionalTextService;
    }

    /**
     * {@inheritdoc}
     */
    public function getLastResultTotalCount()
    {
        return $this->totalCount;
    }

    /**
     * {@inheritdoc}
     */
    public function findProducts($searchTerm, $shopId, $limit = 10, $offset = 0)
    {
        // Escape "_" (MySQL wildcards) and surround with "%" (MySQL wildcards)
        $searchTerm = '%' . str_replace('_', '\_', $searchTerm) . '%';

        $queryBuilder = $this->getSearchBaseQuery();
        $searchResult = $queryBuilder
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->setParameter('searchTerm', $searchTerm)
            ->execute()
            ->fetchAll(\PDO::FETCH_ASSOC);

        $shopContext = $this->contextService->createShopContext($shopId);

        foreach ($searchResult as &$product) {
            $productStruct = new Product($product['id'], $product['variantId'], $product['number']);
            $additionalText = $this->additionalTextService->buildAdditionalText($productStruct, $shopContext);
            $product['additionalText'] = $additionalText->getAdditional();
        }
        unset($product);

        $this->totalCount = count($this->getSearchBaseQuery()
            ->setParameter('searchTerm', $searchTerm)
            ->execute()
            ->fetchAll(\PDO::FETCH_ASSOC));

        return $searchResult;
    }

    /**
     * {@inheritdoc}
     */
    public function getProduct($orderNumber, $params, $shopId, $customerGroupKey)
    {
        $product = $this->getProductByNumber($orderNumber, $customerGroupKey, $shopId);

        $tax = new TaxStruct();
        $tax->setId($product['taxId']);
        $tax->setTax($product['tax']);

        $product['quantity'] = $params['quantity'];

        $requestStruct = $this->requestHydrator->hydrateFromRequest($params);
        $shopContext = $this->contextService->createShopContext(
            $shopId,
            $requestStruct->getCurrencyId(),
            $customerGroupKey
        );

        if ($product['to'] !== 'beliebig') {
            $blockPrices = $this->getBlockPrices(
                $product['productDetailId'],
                $product['isFallbackPrice'] ? $shopContext->getFallbackCustomerGroup()->getKey() : $customerGroupKey
            );

            $blockPricesResult = [];
            foreach ($blockPrices as $price) {
                $blockPricesResult[$price['from']] = [
                    'net' => round($price['price'], PriceResult::ROUND_PRECISION),
                    'gross' => round(
                        $this->calculatePrice(
                            $price['price'],
                            $tax,
                            $shopContext
                        ),
                        PriceResult::ROUND_PRECISION
                    ),
                ];
            }

            foreach ($blockPricesResult as $amount => $price) {
                if ($product['quantity'] >= $amount) {
                    $product['price'] = $price['net'];
                }
            }

            $product['blockPrices'] = json_encode($blockPricesResult);
        }

        $product['price'] = $this->calculatePrice(
            $product['price'],
            $tax,
            $shopContext,
            $params['displayNet'] === 'true'
        );

        return $product;
    }

    /**
     * @param float $price
     * @param bool  $getNetPrice
     *
     * @return float
     */
    private function calculatePrice(
        $price,
        TaxStruct $tax,
        ShopContextInterface $shopContext,
        $getNetPrice = false
    ) {
        if ($getNetPrice) {
            return round($price, PriceResult::ROUND_PRECISION);
        }

        return $this->productPriceCalculator->calculatePrice($price, $tax, $shopContext);
    }

    /**
     * @param int    $productDetailsId
     * @param string $customerGroupKey
     *
     * @return array
     */
    private function getBlockPrices($productDetailsId, $customerGroupKey)
    {
        return $this->connection->createQueryBuilder()
            ->select(['price.from', 'price.price'])
            ->from('s_articles_prices', 'price')
            ->where('articledetailsID = :productDetailId')
            ->andWhere('price.pricegroup = :customerGroupKey')
            ->setParameter('productDetailId', $productDetailsId)
            ->setParameter('customerGroupKey', $customerGroupKey)
            ->execute()
            ->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param string $orderNumber
     * @param string $customerGroupKey
     * @param int    $shopId
     *
     * @return array
     */
    private function getProductByNumber($orderNumber, $customerGroupKey, $shopId)
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $defaultCustomerGroup = $this->contextService->createShopContext($shopId)->getFallbackCustomerGroup()->getKey();

        $product = $queryBuilder->select([
            'article.name',
            'article.taxID AS taxId',
            'article.id',
            'tax.tax',
            'details.ordernumber AS number',
            'details.additionalText',
            'details.instock AS inStock',
            'details.id AS productDetailId',
            'details.ean AS ean',
            'price.price',
            'price.to',
            'defaultPrice.price AS defaultPrice',
            'defaultPrice.to AS defaultPriceTo',
        ])
            ->from('s_articles', 'article')
            ->join('article', 's_articles_details', 'details', 'article.id = details.articleID')
            ->leftJoin('details', 's_articles_prices', 'price', 'details.id = price.articledetailsID AND price.pricegroup = :priceGroup')
            ->leftJoin('details', 's_articles_prices', 'defaultPrice', 'details.id = defaultPrice.articledetailsID AND defaultPrice.pricegroup = :defaultPriceGroup')
            ->join('article', 's_core_tax', 'tax', 'tax.id = article.taxID')
            ->where('details.ordernumber = :ordernumber')
            ->setParameter('priceGroup', $customerGroupKey)
            ->setParameter('defaultPriceGroup', $defaultCustomerGroup)
            ->setParameter('ordernumber', $orderNumber)
            ->execute()
            ->fetch(\PDO::FETCH_ASSOC);

        return $this->prepareProductPrice($product);
    }

    /**
     * @return array
     */
    private function prepareProductPrice(array $product)
    {
        if (!$product['price']) {
            $product['price'] = $product['defaultPrice'];
            $product['to'] = $product['defaultPriceTo'];
            $product['isFallbackPrice'] = true;
        }

        unset($product['defaultPrice']);
        unset($product['defaultPriceTo']);

        return $product;
    }

    /**
     * @return QueryBuilder
     */
    private function getSearchBaseQuery()
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        return $queryBuilder->select([
            'article.name',
            'article.active AS articleActive',
            'details.ordernumber AS number',
            'details.additionalText',
            'details.id as variantId',
            'details.active AS variantActive',
        ])
            ->from('s_articles', 'article')
            ->join('article', 's_articles_details', 'details', 'article.id = details.articleID')
            ->join('article', 's_articles_supplier', 'supplier', 'article.supplierID = supplier.id')
            ->where('article.name LIKE :searchTerm')
            ->orWhere('details.ordernumber LIKE :searchTerm')
            ->orWhere('details.additionalText LIKE :searchTerm')
            ->orWhere('supplier.name LIKE :searchTerm');
    }
}
