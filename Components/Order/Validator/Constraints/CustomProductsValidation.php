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

use Doctrine\DBAL\Connection;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class CustomProductsValidation extends ConstraintValidator
{
    /**
     * @var \Shopware_Components_Snippet_Manager
     */
    private $snippetManager;

    /**
     * @var Connection
     */
    private $connection;

    public function __construct(\Shopware_Components_Snippet_Manager $snippetManager, Connection $connection)
    {
        $this->snippetManager = $snippetManager;
        $this->connection = $connection;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint): void
    {
        if ($constraint instanceof CustomProduct === false) {
            return;
        }

        if (!$this->isCustomProductsPluginActivate($constraint)) {
            return;
        }

        if ($this->isCustomProduct($value)) {
            $message = $this->snippetManager->getNamespace($constraint->namespace)->get($constraint->snippet);

            $this->context->addViolation(\sprintf($message, $value));
        }
    }

    private function isCustomProductsPluginActivate(CustomProduct $constraint): bool
    {
        $builder = $this->connection->createQueryBuilder();
        $builder->select('plugins.name');
        $builder->from('s_core_plugins', 'plugins');
        $builder->where('plugins.name = :name');
        $builder->andWhere('plugins.active = 1');
        $builder->setParameter('name', $constraint->pluginName);

        $stmt = $builder->execute();

        return (bool) $stmt->fetchColumn();
    }

    private function isCustomProduct(string $orderNumber): bool
    {
        $builder = $this->connection->createQueryBuilder();
        $builder->select('article.id');
        $builder->from('s_plugin_custom_products_template_product_relation', 'relation');
        $builder->leftJoin('relation', 's_articles', 'article', 'article.id = relation.article_id');
        $builder->leftJoin('article', 's_articles_details', 'detail', 'detail.articleID = article.id');
        $builder->where('detail.ordernumber = :number');
        $builder->setParameter('number', $orderNumber);

        $stmt = $builder->execute();

        return (bool) $stmt->fetchColumn();
    }
}
