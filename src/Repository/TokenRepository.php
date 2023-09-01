<?php

namespace App\Repository;

use App\Entity\Item;
use App\Entity\Method;
use App\Entity\Token;
use App\Service\Credential;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Token::class);
    }

    public function generate(Method $method, Item ...$items): string
    {
        $this->getEntityManager()->getConnection()->executeQuery("SELECT pg_advisory_lock(:lockKey)", ['lockKey' => $method->getId()]);

        $tokenKey = "";
        do {
            $tokenKey = $method->getId() .  bin2hex(openssl_random_pseudo_bytes(20));
            $tokenExistsCount = $this->count(['token' => $tokenKey]);
        } while($tokenExistsCount > 0);

        $credential = (new Credential())->get($method->getId(), $items[0]->getCurrencyCode());

        foreach ($items as $item) {
            $token = new Token();
            $token->setToken($tokenKey);
            $token->setMethod($method);
            $token->setAccountKey($credential);
            $token->setItem($item);

            $this->getEntityManager()->persist($token);
        }

        $this->getEntityManager()->flush();
        return $tokenKey;
    }
}