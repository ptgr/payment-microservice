<?php

namespace App\Entity;

use App\Repository\TokenItemRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TokenItemRepository::class)]
class TokenItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Token $token;

    #[ORM\OneToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Item $item;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getToken(): Token
    {
        return $this->token;
    }

    public function setToken(Token $token): static
    {
        $this->token = $token;

        return $this;
    }

    public function getItem(): Item
    {
        return $this->item;
    }

    public function setItem(Item $item): static
    {
        $this->item = $item;

        return $this;
    }
}