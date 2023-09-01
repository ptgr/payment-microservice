<?php

namespace App\Controller;

use App\Enum\TokenStatus;
use App\Service\ProviderStrategy;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Token;

class PayController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
    ) {
    }

    #[Route('/api/v1/payment/pay/{token}', name: 'pay', methods: ['GET'])]
    public function pay(Request $request, string $token, ProviderStrategy $providerStrategy): RedirectResponse
    {
        $processResult = null;
        try {
            $tokenEntities = $this->entityManager->getRepository(Token::class)->findBy(['token' => $token]);
            if (empty($tokenEntities)) {
                $this->logger->warning("PAY - The token does not exists.", ['token' => $token]);
                return $this->redirect($this->getParameter('app.redirect_after_failure_payment'));
            }

            if ($tokenEntities[0]->getStatus() !== TokenStatus::ACTIVE) {
                $this->logger->warning("PAY - The token is not active.", ['token' => $token]);
                return $this->redirect($this->getParameter('app.redirect_after_failure_payment'));
            }

            $providerFacade = $providerStrategy->resolve($tokenEntities[0]->getMethod());
            $processResult = $providerFacade->process(...$tokenEntities);

        } catch (\Throwable $th) {
            // TODO: logs
            $this->logger->error($request->attributes->get('_route'), ['error' => $th->getMessage(), 'stack' => $th->getTraceAsString()]);
            return $this->redirect($this->getParameter('app.redirect_after_failure_payment'));
        }

        return $processResult;
    }
}