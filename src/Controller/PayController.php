<?php

namespace App\Controller;

use App\Entity\TokenItem;
use App\Enum\TokenStatus;
use App\Service\ProviderStrategy;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Token;
use OpenApi\Annotations as OA;

class PayController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
    ) {
    }

    #[Route('/api/v1/payment/pay/{token}', name: 'pay', methods: ['GET'])]
    /**
     * @OA\Tag(name="Redirect to payment provider page")
     */
    public function pay(Request $request, ProviderStrategy $providerStrategy, ?Token $token = null): RedirectResponse
    {
        $processResult = null;
        try {
            if ($token === null) {
                $this->logger->warning("PAY - The token does not exists.");
                return $this->redirect($this->getParameter('app.redirect_after_failure_payment'));
            }

            if ($token->getStatus() !== TokenStatus::ACTIVE) {
                $this->logger->warning("PAY - The token is not active.", ['token' => $token->getId()]);
                return $this->redirect($this->getParameter('app.redirect_after_failure_payment'));
            }

            $providerFacade = $providerStrategy->resolve($token->getProvider());

            $tokenItemsEntities = $this->entityManager->getRepository(TokenItem::class)->findBy(['token' => $token->getId()]);
            $processResult = $providerFacade->process(...$tokenItemsEntities);

        } catch (\Throwable $th) {
            $this->logger->error($request->attributes->get('_route'), ['error' => $th->getMessage(), 'stack' => $th->getTraceAsString()]);
            return $this->redirect($this->getParameter('app.redirect_after_failure_payment'));
        }

        return $processResult;
    }
}