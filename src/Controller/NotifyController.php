<?php

namespace App\Controller;

use App\Entity\Token;
use App\Interface\INotifiable;
use App\Service\ProviderStrategy;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;

class NotifyController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
    ) {
    }

    #[Route('/api/v1/payment/notify/{token}', name: 'notify', methods: ['GET', 'POST'])]
    /**
     * This request is triggered by payment provider (webhooks).
     * It can be GET or POST and content of body depends of the provider.
     * 
     * @OA\Tag(name="Process payment notification")
     */
    public function notify(Request $request, ProviderStrategy $providerStrategy, ?Token $token = null): JsonResponse
    {
        try {
            $providerInstance = null;

            if ($token === null) {
                foreach ($providerStrategy->getNotificationProviders() as $provider) {

                    if ($provider->isProviderNotification($request, $token)) {
                        $providerInstance = $provider;
                        break;
                    }
                }
            } else {
                $providerInstance = $providerStrategy->resolve($token->getProvider());
            }

            if (!$providerInstance instanceof INotifiable)
                return new JsonResponse("The notification was not process with success.", Response::HTTP_UNPROCESSABLE_ENTITY);

            return $providerInstance->notify($request, $token);

        } catch (\Throwable $th) {
            $this->logger->error($request->attributes->get('_route'), ['error' => $th->getMessage(), 'stack' => $th->getTraceAsString(), 'content' => $request->getContent()]);
            return new JsonResponse($th->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}