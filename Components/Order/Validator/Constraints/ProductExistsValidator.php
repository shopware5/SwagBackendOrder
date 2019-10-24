<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
    public function validate($value, Constraint $constraint)
    {
        /** @var ProductExists $constraint */
        if ($constraint instanceof ProductExists === false) {
            return;
        }

        if (!$this->productExists($value)) {
            $namespace = $this->snippetManager->getNamespace($constraint->namespace);
            $message = $namespace->get($constraint->snippet);

            $this->context->addViolation(sprintf($message, $value));
        }
    }

    /**
     * @param string $orderNumber
     *
     * @return bool
     */
    private function productExists($orderNumber)
    {
        $builder = $this->connection->createQueryBuilder();
        $builder->select('details.id');
        $builder->from('s_articles_details', 'details');
        $builder->where('ordernumber = :number');
        $builder->setParameter('number', $orderNumber);

        $stmt = $builder->execute();

        return $stmt->fetchColumn();
    }
}
