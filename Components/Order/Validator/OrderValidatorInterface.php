<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace SwagBackendOrder\Components\Order\Validator;

use SwagBackendOrder\Components\Order\Struct\OrderStruct;

interface OrderValidatorInterface
{
    public function validate(OrderStruct $order): ValidationResult;
}
