<?php

namespace App\Entity;

use App\Enum\TokenStatus;
use App\Repository\TokenRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TokenRepository::class)]
class Token
{
    #[ORM\Id]
    #[ORM\Column(length: 50)]
    private string $id;

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

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): static
    {
        $this->id = $id;

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