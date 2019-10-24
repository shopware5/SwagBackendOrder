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

class LastStockValidator extends ConstraintValidator
{
    /**
     * @var \Enlight_Components_Snippet_Manager
     */
    private $snippetManager;

    /**
     * @var Connection
     */
    private $connection;

    public function __construct(
        \Enlight_Components_Snippet_Manager $snippetManager,
        Connection $connection
    ) {
        $this->snippetManager = $snippetManager;
        $this->connection = $connection;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        /** @var LastStock $constraint */
        if ($constraint instanceof LastStock === false) {
            return;
        }

        if (!$this->isLastStockProduct($value)) {
            return;
        }

        $currentInStock = $this->getInStock($value);
        if (!$this->isValid($currentInStock, $constraint->quantity)) {
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
    private function getInStock($orderNumber)
    {
        $builder = $this->connection->createQueryBuilder();
        $builder->select('detail.instock')
            ->from('s_articles_details', 'detail')
            ->where('ordernumber = :number')
            ->setParameter('number', $orderNumber);

        $stmt = $builder->execute();

        return $stmt->fetchColumn();
    }

    /**
     * @param int $inStock
     * @param int $quantity
     *
     * @return bool
     */
    private function isValid($inStock, $quantity)
    {
        return ($inStock - $quantity) >= 0;
    }

    /**
     * @param string $orderNumber
     *
     * @return bool
     */
    private function isLastStockProduct($orderNumber)
    {
        $builder = $this->connection->createQueryBuilder();
        $builder->select('details.laststock')
            ->from('s_articles', 'article')
            ->leftJoin('article', 's_articles_details', 'details', 'details.articleID = article.id')
            ->where('ordernumber = :number')
            ->setParameter('number', $orderNumber);

        $stmt = $builder->execute();

        return $stmt->fetchColumn() != 0;
    }
}
