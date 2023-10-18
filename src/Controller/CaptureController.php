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
use OpenApi\Annotations as OA;

class CaptureController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
    ) {
    }

    #[Route('/api/v1/payment/capture/{token}', name: 'capture', methods: ['GET', 'POST'])]
    /**
     * This request is triggered after a customer authorized the payment.
     * It can be GET or POST and content of body depends of the provider.
     * 
     * @OA\Tag(name="Capture payment")
     */
    public function capture(Request $request, ProviderStrategy $providerStrategy, ?Token $token = null): RedirectResponse
    {
        $processResult = null;
        try {
            if ($token === null) {
                $this->logger->warning("CAPTURE - The token does not exists.");
                return $this->redirect($this->getParameter('app.redirect_after_failure_payment'));
            }

            if ($token->getStatus() !== TokenStatus::ACTIVE) {
                $this->logger->warning("CAPTURE - The token is not active.", ['token' => $token->getId()]);
                return $this->redirect($this->getParameter('app.redirect_after_failure_payment'));
            }

            $providerFacade = $providerStrategy->resolve($token->getProvider());
            if (!$providerFacade instanceof ICaptureable)
                return $this->redirect($this->getParameter('app.redirect_after_failure_payment'));
            
            $payload = \array_merge($request->request->all(), $request->query->all());
            $processResult = $providerFacade->capture($token, $payload);

        } catch (\Throwable $th) {
            $this->logger->error($request->attributes->get('_route'), ['error' => $th->getMessage(), 'stack' => $th->getTraceAsString()]);
            return $this->redirect($this->getParameter('app.redirect_after_failure_payment'));
        }

        return $processResult;
    }
}