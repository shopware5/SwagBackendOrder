<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagBackendOrder\Components;

use Doctrine\ORM\Query\Expr\Join;
use Shopware\Components\Model\ModelManager;
use Shopware\Components\Model\QueryBuilder;
use Shopware\Models\Article\Article;
use Shopware\Models\Article\Supplier;

class ProductRepository
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
     * @param string $search
     * @param string $customerGroupKey
     *
     * @return QueryBuilder
     */
    public function getProductQueryBuilder($search, $customerGroupKey = 'EK')
    {
        $builder = $this->modelManager->createQueryBuilder();

        /*
         * query to search for product variants or the product number
         * the query concatenate the product name and the additional text field for the search
         */
        $builder->select(
            'articles.id AS articleId,
            details.number,
            details.id AS variantId,
            articles.name,
            details.id,
            details.inStock,
            articles.taxId,
            prices.price,
            details.additionalText,
            tax.tax,
            articles.supplierId,
            sp.id as supplierID'
        );

        $builder->from(Article::class, 'articles')
                ->leftJoin('articles.details', 'details')
                ->leftJoin('articles.supplier', 'sp')
                ->leftJoin('details.prices', 'prices', Join::WITH, 'prices.customerGroupKey = :groupKey');

        if ($customerGroupKey !== 'EK') {
            $builder->leftJoin('details.prices', 'fallbackPrices', Join::WITH, "fallbackPrices.customerGroupKey = 'EK'");
            $builder->addSelect('fallbackPrices.price AS fallbackPrice');
        }

        $builder->leftJoin('articles.tax', 'tax')
                ->where(
                    $builder->expr()->like(
                        $builder->expr()->concat(
                            'articles.name',
                            $builder->expr()->concat(
                                $builder->expr()->literal(' '),
                                'IFNULL(details.additionalText,\'\')'
                            )
                        ),
                        $builder->expr()->literal($search)
                    )
                )
                ->orWhere('details.number LIKE :number')
                ->orWhere('sp.name LIKE :number')
                ->andWhere('articles.active = 1')
                ->setParameter('number', $search)
                ->setParameter('groupKey', $customerGroupKey)
                ->orderBy('details.number')
                ->groupBy('details.number')
                ->setMaxResults(3);

        return $builder;
    }
}
