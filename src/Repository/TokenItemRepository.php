<?php

namespace App\Repository;

use App\Entity\Item;
use App\Entity\Token;
use App\Entity\TokenItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TokenItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TokenItem::class);
    }

    public function store(Token $token, Item ...$items): void
    {
        foreach ($items as $item) {
            $tokenItemEntity = new TokenItem();
            $tokenItemEntity->setToken($token);
            $tokenItemEntity->setItem($item);

            $this->getEntityManager()->persist($tokenItemEntity);
        }

        $this->getEntityManager()->flush();
    }
}
