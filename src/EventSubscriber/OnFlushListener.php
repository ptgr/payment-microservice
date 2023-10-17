<?php

namespace App\EventSubscriber;

use App\Entity\Payment;
use App\Entity\StatusHistory;
use App\Entity\Token;
use App\Enum\StatusType;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::onFlush)]
class OnFlushListener
{
    public function onFlush(OnFlushEventArgs $args): void
    {
        $entityManager = $args->getObjectManager();
        $unitOfWork = $entityManager->getUnitOfWork();

        foreach ($unitOfWork->getScheduledEntityUpdates() as $entity) {
            $this->createStatusHistory($entityManager, $entity);
        }
    }

    private function createStatusHistory(EntityManagerInterface $entityManager, object $entity)
    {
        if (!$entity instanceof Payment && !$entity instanceof Token)
            return;

        $statusHistoryRepository = $entityManager->getRepository(StatusHistory::class);
        $statusType = $entity instanceof Payment ? StatusType::PAYMENT : StatusType::TOKEN;
        $tokenEntity = $entity instanceof Payment ? $entity->getToken() : $entity;

        foreach ($entityManager->getUnitOfWork()->getEntityChangeSet($entity) as $key => $changeSet) {
            if ($key !== 'status')
                continue;

            [$oldValue, $newValue] = $changeSet;
            $statusHistoryEntity = $statusHistoryRepository->prepare($statusType, $tokenEntity, $oldValue, $newValue);
            
            $entityManager->persist($statusHistoryEntity);
            $entityManager->getUnitOfWork()->computeChangeSets();
        }
    }
}