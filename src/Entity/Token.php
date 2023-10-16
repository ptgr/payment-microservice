<?php

namespace App\Entity;

use App\Enum\StatusType;
use App\Enum\TokenStatus;
use App\Repository\TokenRepository;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TokenRepository::class)]
class Token
{
    #[ORM\Id]
    #[ORM\Column(length: 50)]
    private string $id;

    #[ORM\Column(length: 20)]
    private string $account_key;

    #[ORM\ManyToOne(inversedBy: 'provider')]
    #[ORM\JoinColumn(nullable: false)]
    private Provider $provider;

    #[ORM\Column(enumType: TokenStatus::class)]
    private TokenStatus $status;

    #[ORM\Column]
    private \DateTimeImmutable $created_at;

    public function __construct()
    {
        $this->account_key = "default";
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

    public function getProvider(): Provider
    {
        return $this->provider;
    }

    public function setProvider(Provider $provider): static
    {
        $this->provider = $provider;

        return $this;
    }

    public function getStatus(): TokenStatus
    {
        return $this->status;
    }

    #[ORM\PreUpdate]
    public function onStatusChanged(PreUpdateEventArgs $args): void
    {
        if (!$args->hasChangedField('status'))
            return;

        $statusHistory = new StatusHistory();
        $statusHistory->setType(StatusType::TOKEN);
        $statusHistory->setOld($args->getOldValue('status'));
        $statusHistory->setNew($args->getNewValue('status'));

        $args->getEntityManager()->persist($statusHistory);
    }

    public function setStatus(TokenStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->created_at;
    }

}