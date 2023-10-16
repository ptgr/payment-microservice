<?php

namespace App\Repository;

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

    public function create(StatusType $statusType, string $oldValue, string $newValue): void
    {
        $statusHistory = new StatusHistory();
        $statusHistory->setType($statusType);
        $statusHistory->setOld($oldValue);
        $statusHistory->setNew($newValue);

        $this->getEntityManager()->persist($statusHistory);
    }
}
