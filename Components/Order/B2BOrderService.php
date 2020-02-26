<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagBackendOrder\Components\Order;

use Shopware\B2B\Common\Repository\NotFoundException;
use Shopware\B2B\Order\Framework\OrderConversionService;
use Shopware\B2B\StoreFrontAuthentication\Framework\AuthenticationIdentityLoaderInterface;
use Shopware\B2B\StoreFrontAuthentication\Framework\CredentialsBuilderInterface;
use Shopware\B2B\StoreFrontAuthentication\Framework\Identity;
use Shopware\B2B\StoreFrontAuthentication\Framework\LoginContextService;
use Shopware\Models\Order\Order;

class B2BOrderService implements B2BOrderServiceInterface
{
    /**
     * @var OrderConversionService|null
     */
    private $conversionService;

    /**
     * @var LoginContextService|null
     */
    private $loginContextService;

    /**
     * @var CredentialsBuilderInterface|null
     */
    private $credentialsBuilder;

    /**
     * @var AuthenticationIdentityLoaderInterface|null
     */
    private $authenticationIdentityLoader;

    public function __construct(
        ?OrderConversionService $conversionService,
        ?LoginContextService $loginContextService,
        ?CredentialsBuilderInterface $credentialsBuilder,
        ?AuthenticationIdentityLoaderInterface $authenticationIdentityLoader
    ) {
        $this->conversionService = $conversionService;
        $this->loginContextService = $loginContextService;
        $this->credentialsBuilder = $credentialsBuilder;
        $this->authenticationIdentityLoader = $authenticationIdentityLoader;
    }

    public function createB2BOrder(Order $order): void
    {
        if ($this->conversionService === null
            || $this->loginContextService === null
            || $this->credentialsBuilder === null
            || $this->authenticationIdentityLoader === null
        ) {
            return;
        }

        try {
            $ownershipContext = $this->getIdentity($order->getCustomer()->getId())->getOwnershipContext();
        } catch (NotFoundException $exception) {
            return;
        }

        $this->conversionService->convertOrderToB2bOrderContext(
            $order->getId(),
            $ownershipContext
        );
    }

    private function getIdentity(int $userId): Identity
    {
        $credentials = $this->credentialsBuilder->createCredentialsByUserId($userId);

        return $this->authenticationIdentityLoader->fetchIdentityByCredentials($credentials, $this->loginContextService);
    }
}
