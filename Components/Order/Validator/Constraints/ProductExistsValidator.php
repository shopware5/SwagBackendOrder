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

class ProductExistsValidator extends ConstraintValidator
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
        if ($constraint instanceof ProductExists === false) {
            return;
        }

        if (!$this->productExists($value)) {
            $message = $this->snippetManager->getNamespace($constraint->namespace)->get($constraint->snippet);

            $this->context->addViolation(\sprintf($message, $value));
        }
    }

    private function productExists(string $orderNumber): bool
    {
        $builder = $this->connection->createQueryBuilder();
        $builder->select('details.id');
        $builder->from('s_articles_details', 'details');
        $builder->where('ordernumber = :number');
        $builder->setParameter('number', $orderNumber);

        return (bool) $builder->execute()->fetchColumn();
    }
}
