<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace SwagBackendOrder\Components\Order\Factory;

use Shopware\Components\Model\ModelManager;
use Shopware\Models\Article\Detail as ProductVariant;
use Shopware\Models\Attribute\OrderDetail;
use Shopware\Models\Order\Detail;
use Shopware\Models\Order\DetailStatus;
use Shopware\Models\Tax\Tax;
use Shopware_Components_Modules as Modules;
use SwagBackendOrder\Components\Order\Struct\PositionStruct;
use SwagBackendOrder\Components\Order\Validator\InvalidOrderException;

class DetailFactory
{
    /**
     * @var ModelManager
     */
    private $modelManager;

    /**
     * @var \sArticles
     */
    private $productCoreClass;

    public function __construct(ModelManager $modelManager, Modules $modules)
    {
        $this->modelManager = $modelManager;
        $this->productCoreClass = $modules->Articles();
    }

    /**
     * @throws InvalidOrderException
     */
    public function create(PositionStruct $positionStruct, bool $isTaxFree): Detail
    {
        if (!$positionStruct->getNumber()) {
            throw new InvalidOrderException('No product number was passed.');
        }

        if ($positionStruct->isDiscount()) {
            return $this->createDiscount($positionStruct, $isTaxFree);
        }

        $detail = new Detail();

        $productVariant = $this->modelManager->getRepository(ProductVariant::class)->findOneBy(['number' => $positionStruct->getNumber()]);
        if (!$productVariant instanceof ProductVariant) {
            throw new \RuntimeException(sprintf('Could not find %s with number "%s"', ProductVariant::class, $positionStruct->getNumber()));
        }
        $product = $productVariant->getArticle();

        $tax = $this->modelManager->find(Tax::class, $positionStruct->getTaxId());
        // Actually sOrder::sSaveOrder() sets this to the illegal value of '0' when the order is taxfree,
        // but this is not possible via Doctrine, so by not setting the value it falls back to NULL
        if (!$isTaxFree) {
            $detail->setTax($tax);
        }

        $detail->setTaxRate((float) $positionStruct->getTaxRate());

        $detail->setEsdArticle(0);

        $detailStatus = $this->modelManager->find(DetailStatus::class, 0);
        if (!$detailStatus instanceof DetailStatus) {
            throw new \RuntimeException(sprintf('Could not find %s with ID "%s"', DetailStatus::class, 0));
        }
        $detail->setStatus($detailStatus);

        $detail->setArticleId($product->getId());
        $detail->setArticleDetail($productVariant);
        $name = $this->productCoreClass->sGetArticleNameByOrderNumber($positionStruct->getNumber());
        $name = \is_string($name) ? $name : '';
        $detail->setArticleName($name);
        $detail->setArticleNumber($positionStruct->getNumber());
        $detail->setPrice($positionStruct->getPrice());
        $detail->setMode($positionStruct->getMode());
        $detail->setQuantity($positionStruct->getQuantity());
        $detail->setShipped(0);
        $detail->setUnit($productVariant->getUnit() ? $productVariant->getUnit()->getName() : '');
        $detail->setPackUnit($productVariant->getPackUnit());
        $detail->setAttribute($this->createDetailAttribute());
        $detail->setEan($positionStruct->getEan());

        return $detail;
    }

    private function createDiscount(PositionStruct $positionStruct, bool $isTaxFree): Detail
    {
        $detail = new Detail();
        $detail->setArticleNumber($positionStruct->getNumber());
        $detail->setArticleName($positionStruct->getName());
        $detail->setMode($positionStruct->getMode());
        $detail->setPrice($positionStruct->getTotal());

        $detail->setQuantity(1);
        $detail->setShipped(0);
        $detail->setArticleId($positionStruct->getProductId());
        $detail->setEsdArticle(0);

        $tax = $this->modelManager->find(Tax::class, $positionStruct->getTaxId());
        // Actually sOrder::sSaveOrder() sets this to the illegal value of '0' when the order is taxfree,
        // but this is not possible via Doctrine, so by not setting the value it falls back to NULL
        if (!$isTaxFree) {
            $detail->setTax($tax);
        }
        $detail->setTaxRate((float) $positionStruct->getTaxRate());

        $detailStatus = $this->modelManager->find(DetailStatus::class, 0);
        if (!$detailStatus instanceof DetailStatus) {
            throw new \RuntimeException(sprintf('Could not find %s with ID "%s"', DetailStatus::class, 0));
        }
        $detail->setStatus($detailStatus);
        $detail->setAttribute($this->createDetailAttribute());

        return $detail;
    }

    private function createDetailAttribute(): OrderDetail
    {
        $orderDetailAttribute = new OrderDetail();
        $orderDetailAttribute->setAttribute1('');
        $orderDetailAttribute->setAttribute2('');
        $orderDetailAttribute->setAttribute3('');
        $orderDetailAttribute->setAttribute4('');
        $orderDetailAttribute->setAttribute5('');
        $orderDetailAttribute->setAttribute6('');

        return $orderDetailAttribute;
    }
}
