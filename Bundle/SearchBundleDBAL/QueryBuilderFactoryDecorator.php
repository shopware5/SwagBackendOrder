<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagBackendOrder\Bundle\SearchBundleDBAL;

use Shopware\Bundle\SearchBundle\Criteria;
use Shopware\Bundle\SearchBundleDBAL\QueryBuilderFactoryInterface;
use Shopware\Bundle\StoreFrontBundle\Struct\ShopContextInterface;

class QueryBuilderFactoryDecorator implements QueryBuilderFactoryInterface
{
    /**
     * @var QueryBuilderFactoryInterface
     */
    private $coreQueryBuilderFactory;

    /**
     * @param QueryBuilderFactoryInterface $coreQueryBuilderFactory
     */
    public function __construct(QueryBuilderFactoryInterface $coreQueryBuilderFactory)
    {
        $this->coreQueryBuilderFactory = $coreQueryBuilderFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function createQueryWithSorting(Criteria $criteria, ShopContextInterface $context)
    {
        return $this->coreQueryBuilderFactory->createQueryWithSorting($criteria, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function createProductQuery(Criteria $criteria, ShopContextInterface $context)
    {
        $query = $this->coreQueryBuilderFactory->createProductQuery($criteria, $context);

        if (!$criteria->hasCondition('swag_backend_order_variant_condition')) {
            return $query;
        }

        // remove groupBy condition because the Shopware core groups by product ID.
        // only do that if the variant condition was added, otherwise the whole shop would be affected
        $query->resetQueryPart('groupBy');

        return $query;
    }

    /**
     * {@inheritdoc}
     */
    public function createQuery(Criteria $criteria, ShopContextInterface $context)
    {
        return $this->coreQueryBuilderFactory->createQuery($criteria, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function createQueryBuilder()
    {
        return $this->coreQueryBuilderFactory->createQueryBuilder();
    }
}
