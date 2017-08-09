<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagBackendOrder\Components;

use Doctrine\ORM\Query\Expr;
use Shopware\Components\Model\ModelManager;
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
     *
     * @return \Doctrine\ORM\QueryBuilder|\Shopware\Components\Model\QueryBuilder
     */
    public function getProductQueryBuilder($search)
    {
        $builder = $this->modelManager->createQueryBuilder();

        /*
         * query to search for article variants or the article ordernumber
         * the query concats the article name and the additional text field for the search
         */
        $builder->select(
            'articles.id AS articleId,
            details.number,
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
            ->leftJoin('details.prices', 'prices')
            ->leftJoin('articles.tax', 'tax')
            ->leftJoin(
                Supplier::class,
                'sp',
                Expr\Join::WITH,
                'articles.supplierId = sp.id'
            )
            ->where(
                $builder->expr()->like(
                    $builder->expr()->concat(
                        'articles.name',
                        $builder->expr()->concat(
                            $builder->expr()->literal(' '),
                            'details.additionalText'
                        )
                    ),
                    $builder->expr()->literal($search)
                )
            )
            ->orWhere('details.number LIKE :number')
            ->orWhere('sp.name LIKE :number')
            ->andWhere('articles.active = 1')
            ->setParameter('number', $search)
            ->orderBy('details.number')
            ->groupBy('details.number')
            ->setMaxResults(8);

        return $builder;
    }
}
