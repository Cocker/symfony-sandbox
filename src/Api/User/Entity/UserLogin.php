<?php

declare(strict_types=1);

namespace App\Api\User\Entity;

use App\Api\User\Repository\V1\UserLoginRepository;
use App\Entity\AbstractEntity;
use App\Entity\Trait\Timestampable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserLoginRepository::class)]
#[ORM\HasLifecycleCallbacks]
class UserLogin extends AbstractEntity
{
    use Timestampable;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING)]
    private string $ip;

    #[ORM\Column(name: 'user_agent', type: Types::TEXT)]
    private string $userAgent;

    #[ORM\ManyToOne(inversedBy: 'logins')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $causer = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIp(): string
    {
        return $this->ip;
    }

    public function setIp(string $ip): static
    {
        $this->ip = $ip;

        return $this;
    }

    public function getUserAgent(): string
    {
        return $this->userAgent;
    }

    public function setUserAgent(string $userAgent): static
    {
        $this->userAgent = $userAgent;

        return $this;
    }

    public function getCauser(): ?User
    {
        return $this->causer;
    }

    public function setCauser(?User $causer): static
    {
        $this->causer = $causer;

        return $this;
    }
}
