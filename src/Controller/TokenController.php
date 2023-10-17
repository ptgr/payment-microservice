<?php

namespace App\Controller;

use App\Entity\Item;
use App\Entity\Provider;
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
use OpenApi\Annotations as OA;

class TokenController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
    ) {
    }

    #[Route('/api/v1/payment/token', name: 'token', methods: ['POST'])]
    /**
     * @OA\Tag(name="Generate payment token")
     * @OA\RequestBody(
     *     required=true,
     *     @OA\MediaType(
     *         mediaType="application/json",
     *         @OA\Schema(
     *             @OA\Property(property="transaction_name", type="string", example="TR23FDF64564", description="Name of the transaction"),
     *             @OA\Property(property="vat", type="number", example=20, description="VAT percentage"),
     *             @OA\Property(property="currency_code", type="string", example="EUR", description="Currency code"),
     *             @OA\Property(property="provider_id", type="integer", example=1, description="Provider ID, Paypal - 1"),
     *             @OA\Property(
     *                 property="items",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="external_id", type="integer", example=1001, description="External ID of the item"),
     *                     @OA\Property(property="name", type="string", example="Laptop", description="Name of the item"),
     *                     @OA\Property(property="quantity", type="integer", example=1, description="Quantity of the item"),
     *                     @OA\Property(property="price", type="number", example=8000, description="Price of the item without decimal places. Example: 80.00 => 8000"),
     *                     @OA\Property(property="discount", type="number", example=10, description="Discount on the item in percentage"),
     *                     @OA\Property(property="shipping", type="number", example=100, description="Shipping cost of the item")
     *                 )
     *             )
     *         )
     *     )
     * )
     *
     * @OA\Response(
     *     response="201",
     *     description="Created",
     *     @OA\JsonContent(
     *         @OA\Property(property="token", type="string", example="17d3f6d18acfeae6c5048692c3806fbd2ec89cefb", description="Generated token"),
     *         @OA\Property(property="pay_url", type="string", example="http://localhost/api/v1/payment/pay/17d3f6d18acfeae6c5048692c3806fbd2ec89cefb", description="Payment URL")
     *     )
     * )
     */
    public function get(Request $request, ProviderStrategy $providerStrategy): JsonResponse
    {
        $payload = $request->toArray();
        $tokenEntity = null;

        try {
            $this->entityManager->getConnection()->beginTransaction();

            $errors = (new TokenRequest($payload))->validate()->getErrorsForResponse();

            if (!empty($errors))
                return new JsonResponse($errors, Response::HTTP_BAD_REQUEST);

            $providerEntity = $this->entityManager->getRepository(Provider::class)->find($payload['provider_id']);
            if (!$providerEntity)
                return new JsonResponse("There is no provider strategy.", Response::HTTP_UNPROCESSABLE_ENTITY);

            $items = $this->entityManager->getRepository(Item::class)->storeTokenPayload($payload);
            if (empty($items))
                return new JsonResponse("No item can be included in payment.", Response::HTTP_UNPROCESSABLE_ENTITY);

            $credential = (new Credential())->get($providerEntity->getId(), $items[0]->getCurrencyCode());
            $tokenEntity = $this->entityManager->getRepository(Token::class)->generate($providerEntity, $credential);

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