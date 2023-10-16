<?php

namespace App\Repository;

use App\Entity\Payment;
use App\Entity\Token;
use App\Enum\PaymentStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PaymentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Payment::class);
    }

    public function setAsRefund(string $transactionNumber): void
    {
        $paymentEntity = $this->getEntityManager()->getRepository(Payment::class)->findOneBy(['transaction_number' => $transactionNumber]);
        $paymentEntity->setUpdatedAt();
        $paymentEntity->setStatus(PaymentStatus::REFUNDED);
        $this->getEntityManager()->flush();
    }

    public function store(Token $token, float $amount, string $transactionNumber): void
    {
        $paymentEntity = $this->getEntityManager()->getRepository(Payment::class)->findOneBy(['token' => $token->getId()]);
        
        if ($paymentEntity !== null) {
            $paymentEntity->setUpdatedAt();
            $paymentEntity->setStatus(PaymentStatus::CAPTURED);
            $this->getEntityManager()->flush();
            return;
        }

        $paymentEntity = new Payment();
        $paymentEntity->setToken($token);
        $paymentEntity->setAmount($amount);
        $paymentEntity->setTransactionNumber($transactionNumber);
        $paymentEntity->setStatus(PaymentStatus::CAPTURED);

        $this->getEntityManager()->persist($paymentEntity);
        $this->getEntityManager()->flush();
    }
}