<?php

namespace App\Repository;

use App\Entity\Token;
use App\Enum\StatusType;
use App\Entity\StatusHistory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class StatusHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StatusHistory::class);
    }

    public function prepare(StatusType $statusType, Token $token, string $oldValue, string $newValue): StatusHistory
    {
        $statusHistory = new StatusHistory();
        $statusHistory->setType($statusType);
        $statusHistory->setToken($token);
        $statusHistory->setOld($oldValue);
        $statusHistory->setNew($newValue);

        return $statusHistory;
    }
}
