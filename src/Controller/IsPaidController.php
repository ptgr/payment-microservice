<?php

namespace App\Controller;

use App\Entity\Payment;
use App\Entity\Token;
use App\Enum\PaymentStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;

class IsPaidController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/api/v1/payment/is-paid/{token}', name: 'is_paid', methods: ['GET'])]
    /**
     * @OA\Tag(name="Is paid")
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="paid", type="boolean", example=true, description="Indicates if the payment is paid."),
     *             @OA\Property(property="paid_created_at", type="string", format="date-time", example="2023-10-16 16:53:00", description="Timestamp when the payment was made."),
     *             @OA\Property(property="status", type="string", example="captured", description="Payment status (authorized, captured, or refunded)."),
     *             @OA\Property(property="status_updated_at", type="string", format="date-time", example="2023-10-17 08:43:07", description="Timestamp when the payment status was last updated."),
     *         )
     *     ),
     *       @OA\Response(
     *         response=404,
     *         description="Token not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Token not found.")
     *         )
     *     )
     */
    public function get(Request $request, ?Token $token): JsonResponse
    {
        if ($token === null)
            return new JsonResponse(['message' => 'Token not found.'], 404);

        $payment = $this->entityManager->getRepository(Payment::class)->findOneBy(['token' => $token->getId()]);
        if ($payment === null)
            return new JsonResponse(['paid' => false, 'message' => 'This payment has not been paid yet.']);

        $isPaid = $payment->getStatus() === PaymentStatus::CAPTURED;
        return new JsonResponse([
            'paid' => $isPaid,
            'paid_created_at' => $payment->getCreatedAt()->format('Y-m-d H:i:s'),
            'status' => $payment->getStatus(),
            'status_updated_at' => $payment->getUpdatedAt()->format('Y-m-d H:i:s'),
        ]);
    }
}