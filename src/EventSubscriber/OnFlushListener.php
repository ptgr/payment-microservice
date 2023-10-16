<?php

namespace App\EventSubscriber;

use App\Entity\Payment;
use App\Entity\StatusHistory;
use App\Entity\Token;
use App\Enum\StatusType;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;

class OnFlushListener implements EventSubscriber
{
    public function getSubscribedEvents()
    {
        return [Events::onFlush];
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $entityManager = $args->getEntityManager();
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

        foreach ($entityManager->getUnitOfWork()->getEntityChangeSet($entity) as $key => $changeSet) {
            if ($key !== 'status')
                continue;

            [$oldValue, $newValue] = $changeSet;
            $statusHistoryEntity = $statusHistoryRepository->prepare($statusType, $entity->getId(), $oldValue, $newValue);
            
            $entityManager->persist($statusHistoryEntity);
            $entityManager->getUnitOfWork()->computeChangeSets();
        }
    }
}