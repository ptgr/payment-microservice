<?php

namespace App\Controller;

use App\Enum\TokenStatus;
use App\Interface\ICaptureable;
use App\Entity\Token;
use App\Service\ProviderStrategy;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class CaptureController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
    ) {
    }

    #[Route('/api/v1/payment/capture/{token}', name: 'capture')]
    public function index(Request $request, string $token, ProviderStrategy $providerStrategy): RedirectResponse
    {
        $processResult = null;
        try {
            $tokenEntities = $this->entityManager->getRepository(Token::class)->findBy(['token' => $token]);
            if (empty($tokenEntities)) {
                $this->logger->warning("CAPTURE - The token does not exists.", ['token' => $token]);
                return $this->redirect($this->getParameter('app.redirect_after_failure_payment'));
            }

            if ($tokenEntities[0]->getStatus() !== TokenStatus::ACTIVE) {
                $this->logger->warning("CAPTURE - The token is not active.", ['token' => $token]);
                return $this->redirect($this->getParameter('app.redirect_after_failure_payment'));
            }

            $providerFacade = $providerStrategy->resolve($tokenEntities[0]->getMethod());
            if (!$providerFacade instanceof ICaptureable)
                return $this->redirect($this->getParameter('app.redirect_after_failure_payment'));

            $processResult = $providerFacade->capture($tokenEntities[0], $request->toArray());

        } catch (\Throwable $th) {
            $this->logger->error($request->attributes->get('_route'), ['error' => $th->getMessage(), 'stack' => $th->getTraceAsString()]);
            return $this->redirect($this->getParameter('app.redirect_after_failure_payment'));
        }

        return $processResult;
    }
}