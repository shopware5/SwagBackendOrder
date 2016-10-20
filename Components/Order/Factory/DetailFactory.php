<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagBackendOrder\Components\Order\Factory;

use Shopware\Components\Model\ModelManager;
use Shopware\Models\Article\Detail as ArticleDetail;
use Shopware\Models\Attribute\OrderDetail;
use Shopware\Models\Order\Detail;
use Shopware\Models\Order\DetailStatus;
use Shopware\Models\Tax\Tax;
use Shopware_Components_Modules as Modules;
use SwagBackendOrder\Components\Order\Struct\PositionStruct;
use SwagBackendOrder\Components\Order\Validator\InvalidOrderException;

class DetailFactory
{
    /** @var ModelManager $modelManager */
    private $modelManager;

    /** @var \sArticles $articleModule */
    private $articleModule;

    /**
     * @param ModelManager $modelManager
     * @param Modules $modules
     */
    public function __construct(ModelManager $modelManager, Modules $modules)
    {
        $this->modelManager = $modelManager;
        $this->articleModule = $modules->Articles();
    }

    /**
     * @param PositionStruct $positionStruct
     * @param boolean $isTaxFree
     * @return Detail
     * @throws InvalidOrderException
     */
    public function create(PositionStruct $positionStruct, $isTaxFree)
    {
        if (!$positionStruct->getNumber()) {
            throw new InvalidOrderException("No product number was passed.");
        }

        $detail = new Detail();

        $repository = $this->modelManager->getRepository(ArticleDetail::class);
        $articleDetail = $repository->findOneBy(['number' => $positionStruct->getNumber()]);
        $article = $articleDetail->getArticle();


        $tax = $this->modelManager->find(Tax::class, $positionStruct->getTaxId());
        // Actually sOrder::sSaveOrder() sets this to the illegal value of '0' when the order is taxfree,
        // but this is not possible via Doctrine, so by not setting the value it falls back to NULL
        if (!$isTaxFree) {
            $detail->setTax($tax);
        }
        $detail->setTaxRate($tax->getTax());

        $detail->setEsdArticle(0);

        $detailStatus = $this->modelManager->find(DetailStatus::class, 0);
        $detail->setStatus($detailStatus);

        $detail->setArticleId($article->getId());
        $name = $this->articleModule->sGetArticleNameByOrderNumber($positionStruct->getNumber());
        $detail->setArticleName($name);
        $detail->setArticleNumber($positionStruct->getNumber());
        $detail->setPrice($positionStruct->getPrice());
        $detail->setMode($positionStruct->getMode());
        $detail->setQuantity($positionStruct->getQuantity());
        $detail->setShipped(0);
        $detail->setUnit($articleDetail->getUnit() ? $articleDetail->getUnit()->getName() : 0);
        $detail->setPackUnit($articleDetail->getPackUnit());

        $detail->setAttribute($this->createDetailAttribute());

        return $detail;
    }

    /**
     * @return OrderDetail
     */
    private function createDetailAttribute()
    {
        /** @var OrderDetail $orderDetailAttribute */
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
