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

class IsPaidController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/api/v1/payment/is-paid/{token}', name: 'is_paid', methods: ['GET'])]
    public function index(Request $request, Token $token): JsonResponse
    {
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