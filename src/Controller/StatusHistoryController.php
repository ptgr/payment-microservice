<?php

namespace App\Controller;

use App\Entity\StatusHistory;
use App\Entity\Token;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;

class StatusHistoryController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/api/v1/payment/history/{token}', name: 'history', methods: ['GET'])]
    /**
     * @OA\Tag(name="Get status history of token")
     * @OA\Response(
     *     response="200",
     *     description="Success",
     *     @OA\JsonContent(
     *         type="array",
     *         @OA\Items(
     *             @OA\Property(
     *                 property="timestamp",
     *                 type="string",
     *                 description="Timestamp of the event",
     *                 example="2023-10-17 10:18:25"
     *             ),
     *             @OA\Property(
     *                 property="type",
     *                 type="string",
     *                 description="Type of the event (e.g., 'token', 'payment')",
     *                 example="token"
     *             ),
     *             @OA\Property(
     *                 property="before",
     *                 type="string",
     *                 description="State before the event",
     *                 example="active"
     *             ),
     *             @OA\Property(
     *                 property="after",
     *                 type="string",
     *                 description="State after the event",
     *                 example="unavailable"
     *             )
     *         )
     *     ),
     * @OA\Response(
     *         response=404,
     *         description="Token not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Token not found.")
     *         )
     *     )
     * )
     */
    public function index(Request $request, ?Token $token): JsonResponse
    {
        if ($token === null)
            return new JsonResponse(['message' => 'Token not found.'], 404);

        $statusHistory = $this->entityManager->getRepository(StatusHistory::class)->findBy(['token' => $token->getId()], ['updated_at' => 'DESC']);
        $response = [];

        foreach ($statusHistory as $history) {
            $response[] = [
                'timestamp' => $history->getUpdatedAt()->format('Y-m-d H:i:s'),
                'type' => $history->getType(),
                'before' => $history->getOld(),
                'after' => $history->getNew(),
            ];
        }

        return new JsonResponse($response);
    }
}