<?php

namespace App\Controller;

use App\Interface\IProviderNotification;
use App\Interface\INotifiable;
use App\Service\ProviderStrategy;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class NotifyController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
    ) {
    }

    #[Route('/api/v1/payment/notify/{token}', name: 'capture')]
    public function index(Request $request, ?string $token = null, ProviderStrategy $providerStrategy): JsonResponse
    {
        $processResult = null;
        try {
            $providers = $providerStrategy->getAll();
            foreach ($providers as $provider) {
                if (!$provider instanceof IProviderNotification)
                    continue;

                $isProviderNotification = $provider->isProviderNotification($request);
                if (!$isProviderNotification)
                    continue;
                
                if (!$provider instanceof INotifiable)
                    continue;

                $processResult = $provider->notify($request);
                break;
            }

        } catch (\Throwable $th) {
            $this->logger->error($request->attributes->get('_route'), ['error' => $th->getMessage(), 'stack' => $th->getTraceAsString(), 'content' => $request->getContent()]);
            return new JsonResponse($th->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        if ($processResult !== null)
            return $processResult;

        return new JsonResponse("The notification was not process with success.", Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
