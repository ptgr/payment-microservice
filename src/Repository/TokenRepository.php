<?php

namespace App\Repository;

use App\Entity\Provider;
use App\Entity\Token;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Token::class);
    }

    public function generate(Provider $provider, string $credential): Token
    {
        $this->getEntityManager()->getConnection()->executeQuery("SELECT pg_advisory_lock(:lockKey)", ['lockKey' => $provider->getId()]);

        $tokenKey = "";
        do {
            $tokenKey = $provider->getId() . bin2hex(openssl_random_pseudo_bytes(20));
            $tokenExistsCount = $this->count(['id' => $tokenKey]);
        } while ($tokenExistsCount > 0);

        $token = new Token();
        $token->setId($tokenKey);
        $token->setProvider($provider);
        $token->setAccountKey($credential);

        $this->getEntityManager()->persist($token);
        $this->getEntityManager()->flush();

        return $token;
    }
}