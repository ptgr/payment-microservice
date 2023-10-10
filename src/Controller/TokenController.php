<?php

namespace App\Controller;

use App\Entity\Item;
use App\Entity\Method;
use App\Entity\Token;
use App\Entity\TokenItem;
use App\Request\TokenRequest;
use App\Service\Credential;
use App\Service\ProviderStrategy;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TokenController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
    ) {
    }

    #[Route('/api/v1/payment/token', name: 'token', methods: ['POST'])]
    public function get(Request $request, ProviderStrategy $providerStrategy): JsonResponse
    {
        $payload = $request->toArray();
        $tokenEntity = null;

        try {
            $this->entityManager->getConnection()->beginTransaction();

            $errors = (new TokenRequest($payload))->validate()->getErrorsForResponse();

            if (!empty($errors))
                return new JsonResponse($errors, Response::HTTP_BAD_REQUEST);

            $methodEntity = $this->entityManager->getRepository(Method::class)->find($payload['method_id']);

            $strategyExists = $providerStrategy->exists($methodEntity);
            if (!$strategyExists)
                return new JsonResponse("There is no provider strategy.", Response::HTTP_UNPROCESSABLE_ENTITY);

            $items = $this->entityManager->getRepository(Item::class)->storeTokenPayload($payload);
            if (empty($items))
                return new JsonResponse("No item can be included in payment.", Response::HTTP_UNPROCESSABLE_ENTITY);
            
            $credential = (new Credential())->get($methodEntity->getId(), $items[0]->getCurrencyCode());
            $tokenEntity = $this->entityManager->getRepository(Token::class)->generate($methodEntity, $credential);

            $this->entityManager->getRepository(TokenItem::class)->store($tokenEntity, $payload['transaction_name'], ...$items);

            $this->entityManager->getConnection()->commit();
        } catch (\Throwable $th) {
            $this->entityManager->getConnection()->rollBack();
            $this->logger->error($request->getUri(), ['error' => $th->getMessage(), 'stack' => $th->getTraceAsString(), 'payload' => $payload]);
            return new JsonResponse($th->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $payUrl = $this->getParameter('app.domain') . $this->generateUrl("pay", ['token' => $tokenEntity->getId()]);
        return new JsonResponse(['token' => $tokenEntity->getId(), 'pay_url' => $payUrl], Response::HTTP_CREATED);
    }
}