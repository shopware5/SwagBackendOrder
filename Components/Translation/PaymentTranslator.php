<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace SwagBackendOrder\Components\Translation;

class PaymentTranslator
{
    /**
     * @var \Shopware_Components_Translation
     */
    private $translationComponent;

    public function __construct(\Shopware_Components_Translation $translationComponent)
    {
        $this->translationComponent = $translationComponent;
    }

    public function translate(array $paymentMethod, int $languageId): array
    {
        $paymentTranslations = $this->translationComponent->read($languageId, 'config_payment');

        $paymentId = $paymentMethod['id'];

        if (!\is_null($paymentTranslations[$paymentId]['description'])) {
            $paymentMethod['description'] = $paymentTranslations[$paymentId]['description'];
        }

        // for the confirmation mail template
        $paymentMethod['additionaldescription'] = $paymentTranslations[$paymentId]['additionalDescription'];
        $paymentMethod['additionalDescription'] = $paymentTranslations[$paymentId]['additionalDescription'];

        return $paymentMethod;
    }
}
