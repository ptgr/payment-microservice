<?php

namespace App\Entity;

use App\Enum\TokenStatus;
use App\Repository\TokenRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TokenRepository::class)]
#[ORM\UniqueConstraint(columns:['token', 'item_id'])]
class Token
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'token', targetEntity: Item::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Item $item;

    #[ORM\Column(length: 50)]
    private ?string $token = null;

    private string $account_key = 'default';

    #[ORM\ManyToOne(inversedBy: 'method')]
    #[ORM\JoinColumn(nullable: false)]
    private Method $method;

    #[ORM\Column(enumType: TokenStatus::class)]
    private TokenStatus $status;

    #[ORM\Column]
    private \DateTimeImmutable $created_at;

    public function __construct()
    {
        $this->status = TokenStatus::ACTIVE;
        $this->created_at = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(string $token): static
    {
        $this->token = $token;

        return $this;
    }

    public function getAccountKey(): string
    {
        return $this->account_key;
    }

    public function setAccountKey(string $account_key): static
    {
        $this->account_key = $account_key;

        return $this;
    }

    public function getMethod(): Method
    {
        return $this->method;
    }

    public function setMethod(Method $method): static
    {
        $this->method = $method;

        return $this;
    }

    public function getStatus(): TokenStatus
    {
        return $this->status;
    }

    public function setStatus(TokenStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->created_at;
    }

}