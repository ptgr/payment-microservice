<?php

namespace App\Repository;

use App\Entity\Item;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Item::class);
    }

    public function storeTokenPayload(array $payload): array
    {
        $items = [];

        foreach ($payload['items'] as $item) {
            $itemEntity = new Item;
            $itemEntity->setExternalId($item['external_id']);
            $itemEntity->setName($item['name']);
            $itemEntity->setQuantity($item['quantity']);
            $itemEntity->setPrice($item['price']);
            $itemEntity->setShipping($item['shipping']);
            $itemEntity->setDiscount($item['discount']);
            $itemEntity->setVat($payload['vat']);
            $itemEntity->setCurrencyCode($payload['currency_code']);

            $totalPrice = ($item['quantity'] * $item['price']);
            if ($item['discount'] > 0)
                $totalPrice -= $totalPrice * ($item['discount'] / 100);
            if ($payload['vat'] > 0)
                $totalPrice += $totalPrice * ($payload['vat'] / 100);

            $totalPrice += $item['shipping'];

            if ($totalPrice <= 0)
                continue;

            $itemEntity->setTotalPrice($totalPrice);

            $this->getEntityManager()->persist($itemEntity);
            $items[] = $itemEntity;
        }

        $this->getEntityManager()->flush();
        return $items;
    }
}
