<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagBackendOrder\Bundle\SearchBundleDBAL\ConditionHandler;

use Shopware\Bundle\SearchBundle\ConditionInterface;
use Shopware\Bundle\SearchBundleDBAL\ConditionHandlerInterface;
use Shopware\Bundle\SearchBundleDBAL\QueryBuilder;
use Shopware\Bundle\StoreFrontBundle\Struct\ShopContextInterface;
use SwagBackendOrder\Bundle\SearchBundle\Condition\VariantCondition;

class VariantConditionHandler implements ConditionHandlerInterface
{
    /**
     * {@inheritdoc}
     */
    public function supportsCondition(ConditionInterface $condition)
    {
        return $condition instanceof VariantCondition;
    }

    /**
     * {@inheritdoc}
     */
    public function generateCondition(
        ConditionInterface $condition,
        QueryBuilder $query,
        ShopContextInterface $context
    ) {
        $joinParts = $query->getQueryPart('join');
        /** @var array $productJoins */
        $productJoins = $joinParts['product'];

        $query->resetQueryPart('join');

        foreach ($productJoins as $key => &$productJoin) {
            if ($productJoin['joinTable'] === 's_articles_details') {
                // overwrite the original join condition to join all variants not only mainVariant like in the core
                $productJoin['joinCondition'] = 'product.id = variant.articleID AND variant.active = 1 AND product.active = 1';

                break;
            }
        }
        unset($productJoin);
        $joinParts['product'] = $productJoins;

        foreach ($joinParts as $fromAlias => $joinPart) {
            foreach ($joinPart as $join) {
                if ($join['joinType'] === 'inner') {
                    $query->innerJoin(
                        $fromAlias,
                        $join['joinTable'],
                        $join['joinAlias'],
                        $join['joinCondition']
                    );
                } else {
                    $query->leftJoin(
                        $fromAlias,
                        $join['joinTable'],
                        $join['joinAlias'],
                        $join['joinCondition']
                    );
                }
            }
        }
    }
}
