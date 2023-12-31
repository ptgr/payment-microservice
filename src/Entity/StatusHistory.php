<?php

namespace App\Entity;

use App\Enum\StatusType;
use App\Repository\StatusHistoryRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StatusHistoryRepository::class)]
#[ORM\Index(columns: ["type"])]
class StatusHistory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(enumType: StatusType::class)]
    private ?StatusType $type = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Token $token;

    #[ORM\Column(length: 100)]
    private ?string $old = null;

    #[ORM\Column(length: 100)]
    private ?string $new = null;

    #[ORM\Column]
    private \DateTimeImmutable $updated_at;

    public function __construct()
    {
        $this->setUpdatedAt();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?StatusType
    {
        return $this->type;
    }

    public function setType(StatusType $type): static
    {
        $this->type = $type;

        return $this;
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

    public function getOld(): ?string
    {
        return $this->old;
    }

    public function setOld(string $old): static
    {
        $this->old = $old;

        return $this;
    }

    public function getNew(): ?string
    {
        return $this->new;
    }

    public function setNew(string $new): static
    {
        $this->new = $new;

        return $this;
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
