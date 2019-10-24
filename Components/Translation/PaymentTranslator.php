<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagBackendOrder\Components\Translation;

use Shopware_Components_Translation;

class PaymentTranslator
{
    /**
     * @var Shopware_Components_Translation
     */
    private $translationComponent;

    public function __construct(Shopware_Components_Translation $translationComponent)
    {
        $this->translationComponent = $translationComponent;
    }

    /**
     * @param int $languageId
     *
     * @return array
     */
    public function translate(array $paymentMethod, $languageId)
    {
        $paymentTranslations = $this->translationComponent->read($languageId, 'config_payment');

        $paymentId = $paymentMethod['id'];

        if (!is_null($paymentTranslations[$paymentId]['description'])) {
            $paymentMethod['description'] = $paymentTranslations[$paymentId]['description'];
        }

        //for the confirmation mail template
        $paymentMethod['additionaldescription'] = $paymentTranslations[$paymentId]['additionalDescription'];
        $paymentMethod['additionalDescription'] = $paymentTranslations[$paymentId]['additionalDescription'];

        return $paymentMethod;
    }
}
