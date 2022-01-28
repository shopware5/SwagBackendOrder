<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace SwagBackendOrder\Subscriber;

use Enlight\Event\SubscriberInterface;
use Enlight_Template_Manager as TemplateManager;
use Shopware_Components_Snippet_Manager as SnippetManager;

class BackendController implements SubscriberInterface
{
    /**
     * @var TemplateManager
     */
    private $templateManager;

    /**
     * @var SnippetManager
     */
    private $snippetManager;

    /**
     * @var string
     */
    private $pluginDir;

    public function __construct(TemplateManager $templateManager, SnippetManager $snippetManager, string $pluginDir)
    {
        $this->templateManager = $templateManager;
        $this->snippetManager = $snippetManager;
        $this->pluginDir = $pluginDir;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_SwagBackendOrder' => 'onGetBackendController',
        ];
    }

    /**
     * adds the templates and snippets dir
     */
    public function onGetBackendController(): string
    {
        $this->templateManager->addTemplateDir($this->pluginDir . '/Resources/views/');
        $this->snippetManager->addConfigDir($this->pluginDir . '/Resources/snippets/');

        return $this->pluginDir . '/Controllers/Backend/SwagBackendOrder.php';
    }
}
