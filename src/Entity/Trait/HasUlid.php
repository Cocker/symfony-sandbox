<?php

declare(strict_types=1);

namespace App\Entity\Trait;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Ulid;

trait HasUlid
{
    #[ORM\Column(type: UlidType::NAME, unique: true)]
    #[Groups(['v1_metadata'])]
    private Ulid $ulid;

    public function getUlid(): Ulid
    {
        return $this->ulid;
    }

    public function setUlid(Ulid $ulid): void
    {
        $this->ulid = $ulid;
    }

    #[ORM\PrePersist]
    public function generateUlid(): void
    {
        $this->ulid = new Ulid();
    }
}
