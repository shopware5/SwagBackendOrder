<?php
/**
 * Shopware 5
 * Copyright (c) shopware AG
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */

/**
 * Shopware SwagCreateBackendOrder Plugin - Bootstrap
 *
 * @category Shopware
 * @package Shopware\Plugins\SwagCreateBackendOrder
 * @copyright Copyright (c) 2015, shopware AG (http://www.shopware.de)
 * @author Simon Bäumer
 */
class Shopware_Plugins_Backend_SwagCreateBackendOrder_Bootstrap
    extends Shopware_Components_Plugin_Bootstrap
{
    //@TODO: plugin json
    /**
     * returns the label
     *
     * @return string
     */
    public function getLabel()
    {
        $pluginJson = $this->getPluginJson();

        return $pluginJson['label']['de'];
    }

    /**
     * returns the version
     *
     * @return string
     */
    public function getVersion()
    {
        $pluginJson = $this->getPluginJson();

        return $pluginJson['currentVersion'];
    }

    /**
     * @return array
     */
    public function getInfo()
    {
        return array(
            'version' => $this->getVersion(),
            'label' => $this->getLabel(),
            'supplier' => 'shopware AG',
            'description' => 'Ermöglicht es Bestellungen über das Backend zu erstellen.',
            'link' => 'www.shopware.com'
        );
    }

    /**
     * @return array|bool
     */
    public function install()
    {
        $this->createConfiguration();

        $this->registerEvents();

        return array('success' => true, 'invalidateCache' => array('backend'));
    }

    /**
     * @return array|bool
     */
    public function uninstall()
    {
        return array('success' => true, 'invalidateCache' => array('backend'));
    }

    /**
     * configuration for the plugin configuration window
     */
    private function createConfiguration()
    {
        $form = $this->Form();

        $form->addElement('text', 'validationMail',
            array(
                'label' => 'Gast Konto eMail',
                'required' => true,
                'description' => 'Die eMail-Adresse mit der Gast Konten angelegt werden sollen.')
        );

        $form->addElement('text', 'desktopTypes',
            array(
                'label' => 'Desktop-Types',
                'value' => 'Backend',
                'description' => 'Hier kann angegeben werden über welchen Kommunikatoinskanal die Bestellung eingegagen ist. Zum Beispiel Telefon, Handy, Geschäft, ... \n Die Verschiedenen Typen werden durch ein Komma (,) getrennt.'
            )
        );

        /**
         * translation for the configuration fields
         */
        $shopRepository = Shopware()->Models()->getRepository('Shopware\Models\Shop\Locale');

        $translations = array(
            'en_GB' => array(
                'validationMail' => array(
                    'label' => 'Guest account e-mail',
                    'description' => 'The e-mail address which guest accounts use.'
                ),
                'desktopTypes' => array(
                    'label' => 'Desktop types',
                    'description' => 'You can choose which communication channel are available. For example telephone, mobile phone, store, and so on.\n The different types are separated by a comma (,).'
                )
            )
        );

        foreach ($translations as $locale => $snippets) {
            /** @var Shopware\Models\Shop\Locale $localeModel */
            $localeModel = $shopRepository->findOneBy(array('locale' => $locale));

            if (is_null($localeModel)) {
                continue;
            }

            foreach ($snippets as $element => $snippet) {
                $elementModel = $form->getElement($element);

                if (is_null($element)) {
                    continue;
                }

                $translationModel = new Shopware\Models\Config\ElementTranslation();
                $translationModel->setLabel($snippet['label']);
                $translationModel->setDescription($snippet['description']);
                $translationModel->setLocale($localeModel);

                $elementModel->addTranslation($translationModel);
            }
        }
    }

    /**
     * function to register events and hooks
     */
    private function registerEvents()
    {
        $this->subscribeEvent(
                'Enlight_Controller_Dispatcher_ControllerPath_Backend_SwagCreateBackendOrder',
                'onGetBackendController'
        );

        $this->subscribeEvent(
                'Enlight_Controller_Action_PostDispatch_Backend_Order',
                'onOrderPostDispatch'
        );

        $this->subscribeEvent(
                'Enlight_Controller_Action_PostDispatch_Backend_Customer',
                'onCustomerPostDispatch'
        );

        $this->subscribeEvent(
                'Enlight_Controller_Action_Backend_Customer_ValidateEmail',
                'onValidateEmail'
        );

        // Register AboCommerce-Resource
        $this->subscribeEvent(
                'Enlight_Bootstrap_InitResource_CreateBackendOrder',
                'onInitCreateBackendOrderResource'
        );
    }

    /**
     * checks if the fake email was used to create accounts with the same email
     *
     * @param Enlight_Event_EventArgs $arguments
     * @return bool
     */
    public function onValidateEmail(Enlight_Event_EventArgs $arguments)
    {
        $mail = $arguments->getSubject()->Request()->getParam('value');

        if ($this->Config()->get('validationMail') == $mail) {
            Shopware()->Plugins()->Controller()->ViewRenderer()->setNoRender();
            echo true;
            return true;
        }
    }

    /**
     * adds the templates and snippets dir
     *
     * @return string
     */
    public function onGetBackendController()
    {
        $this->Application()->Template()->addTemplateDir(
                $this->Path() . 'Views/'
        );

        $this->Application()->Snippets()->addConfigDir(
            $this->Path() . 'Snippets/'
        );

        return $this->Path() . '/Controllers/Backend/SwagCreateBackendOrder.php';
    }

    /**
     * adds the templates directories which expand the order module
     *
     * @param Enlight_Event_EventArgs $args
     */
    public function onOrderPostDispatch(Enlight_Event_EventArgs $args)
    {
        $view = $args->getSubject()->View();

        // Add view directory
        $args->getSubject()->View()->addTemplateDir(
            $this->Path() . 'Views/'
        );

        if ($args->getRequest()->getActionName() === 'load') {
            $view->extendsTemplate(
                'backend/order/view/create_backend_order/list.js'
            );
        }
    }

    /**
     * adds the templates directories which expand the customer module
     *
     * @param Enlight_Event_EventArgs $args
     */
    public function onCustomerPostDispatch(Enlight_Event_EventArgs $args)
    {
        $view = $args->getSubject()->View();

        $args->getSubject()->View()->addTemplateDir(
                $this->Path() . 'Views/'
        );

        if($args->getRequest()->getActionName() === 'load') {
            $view->extendsTemplate(
                'backend/customer/controller/create_backend_order/detail.js'
            );
            $view->extendsTemplate(
                    'backend/customer/controller/create_backend_order/main.js'
            );
            $view->extendsTemplate(
                    'backend/customer/view/create_backend_order/detail/base.js'
            );
            $view->extendsTemplate(
                    'backend/customer/view/create_backend_order/detail/additional.js'
            );
        }
    }

    /**
     * gets the plugin json and decodes it
     *
     * @return mixed
     */
    private function getPluginJson()
    {
        $pluginInfo = json_decode(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'plugin.json'), true);

        return $pluginInfo;
    }

    /**
     * Event listener function of the Enlight_Bootstrap_InitResource_CreateBackendOrder event.
     * Fired on $this->Application->CreateBackendOrder();
     *
     * @return Shopware_Components_CreateBackendOrder
     */
    public function onInitCreateBackendOrderResource()
    {
        $this->Application()->Loader()->registerNamespace('Shopware_Components', $this->Path() . 'Components/');

        $createBackendOrder = Enlight_Class::Instance('Shopware_Components_CreateBackendOrder');
        $this->getShopwareBootstrap()->registerResource('CreateBackendOrder', $createBackendOrder);

        return $createBackendOrder;
    }

    /**
     * Shopware application bootstrap class.
     *
     * Used to register plugin components.
     *
     * @return Enlight_Bootstrap
     */
    public function getShopwareBootstrap()
    {
        return $this->Application()->Bootstrap();
    }
}