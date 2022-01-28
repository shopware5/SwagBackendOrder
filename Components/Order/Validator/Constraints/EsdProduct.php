<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace SwagBackendOrder\Components\Order\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class EsdProduct extends Constraint
{
    /**
     * @var string
     */
    public $snippet = 'esd_product';

    /**
     * @var string
     */
    public $namespace = 'backend/swag_backend_order/validations';

    /**
     * {@inheritdoc}
     */
    public function validatedBy(): string
    {
        return 'swag_backend_order.validator.constraint.esd_product';
    }
}
