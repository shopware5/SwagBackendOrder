<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagBackendOrder\Components\Order\Factory;

use Shopware\Components\Model\ModelManager;
use Shopware\Models\Article\Article;
use Shopware\Models\Article\Detail as ArticleDetail;
use Shopware\Models\Attribute\OrderDetail;
use Shopware\Models\Order\Detail;
use Shopware\Models\Order\DetailStatus;
use Shopware\Models\Tax\Tax;
use SwagBackendOrder\Components\Order\Struct\PositionStruct;

class DetailFactory
{
    /**
     * @var ModelManager
     */
    private $modelManager;

    /**
     * @param ModelManager $modelManager
     */
    public function __construct(ModelManager $modelManager)
    {
        $this->modelManager = $modelManager;
    }

    /**
     * @param PositionStruct $positionStruct
     * @param boolean $isTaxFree
     * @return Detail
     */
    public function create(PositionStruct $positionStruct, $isTaxFree)
    {
        $detail = new Detail();

        $article = $this->modelManager->find(Article::class, $positionStruct->getArticleId());

        $repository = $this->modelManager->getRepository(ArticleDetail::class);
        $articleDetail = $repository->findOneBy([ 'number' => $positionStruct->getNumber() ]);

        $tax = $this->modelManager->find(Tax::class, $positionStruct->getTaxId());
        $detail->setTax($tax);
        $detail->setTaxRate($tax->getTax());
        if ($isTaxFree) {
            $detail->setTaxRate(0);
        }

        $detail->setEsdArticle(0);

        $detailStatus = $this->modelManager->find(DetailStatus::class, 0);
        $detail->setStatus($detailStatus);

        $detail->setArticleId($article->getId());
        $detail->setArticleName($article->getName());
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