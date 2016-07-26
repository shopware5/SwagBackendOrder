<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagBackendOrder\Components\Order\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class ProductExists extends Constraint
{
    public $namespace = 'backend/swag_backend_order/validations';

    public $snippet = 'article_not_found';

    /**
     * @inheritdoc
     */
    public function validatedBy()
    {
        return 'swag_backend_order.validator.constraint.product_exists';
    }
}