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

class StatusHistoryController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/api/v1/payment/history/{token}', name: 'is_paid', methods: ['GET'])]
    public function index(Request $request, Token $token): JsonResponse
    {
        
    }
}