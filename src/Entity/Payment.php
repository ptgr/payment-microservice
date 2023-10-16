<?php

namespace App\Entity;

use App\Enum\PaymentStatus;
use App\Repository\PaymentRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PaymentRepository::class)]
class Payment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'payment')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Token $token = null;

    #[ORM\Column]
    private ?int $amount = null;

    #[ORM\Column(length: 50)]
    private ?string $transaction_number = null;

    #[ORM\Column(enumType: PaymentStatus::class)]
    private PaymentStatus $status;

    #[ORM\Column]
    private \DateTimeImmutable $created_at;

    #[ORM\Column]
    private \DateTimeImmutable $updated_at;

    public function __construct()
    {
        $this->status = PaymentStatus::AUTHORIZED;
        $this->created_at = new \DateTimeImmutable();
        $this->setUpdatedAt();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getToken(): ?Token
    {
        return $this->token;
    }

    public function setToken(Token $token): static
    {
        $this->token = $token;

        return $this;
    }

    public function getAmount(): ?float
    {
        return $this->amount / 100;
    }

    public function setAmount(float $amount): static
    {
        $this->amount = (int) $amount * 100;

        return $this;
    }

    public function getTransactionNumber(): ?string
    {
        return $this->transaction_number;
    }

    public function setTransactionNumber(string $transaction_number): static
    {
        $this->transaction_number = $transaction_number;

        return $this;
    }

    public function getStatus(): PaymentStatus
    {
        return $this->status;
    }

    public function setStatus(PaymentStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->created_at;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(): static
    {
        $this->updated_at = new \DateTimeImmutable();
        return $this;
    }
}