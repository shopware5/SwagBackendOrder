<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagBackendOrder\Components\Translation;

class ShippingTranslator
{
    /**
     * @var \Shopware_Components_Translation
     */
    private $translationComponent;

    public function __construct(\Shopware_Components_Translation $translationComponent)
    {
        $this->translationComponent = $translationComponent;
    }

    /**
     * @param int $languageId
     *
     * @return array
     */
    public function translate(array $shipping, $languageId)
    {
        $shippingTranslations = $this->translationComponent->read($languageId, 'config_dispatch');

        $dispatchId = $shipping['id'];

        if (!is_null($shippingTranslations[$dispatchId]['dispatch_name'])) {
            $shipping['name'] = $shippingTranslations[$dispatchId]['dispatch_name'];
            $shipping['dispatch_name'] = $shippingTranslations[$dispatchId]['dispatch_name'];
        }

        $shipping['description'] = $shippingTranslations[$dispatchId]['description'];

        return $shipping;
    }
}
