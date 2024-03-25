<?php

namespace App\Entity\Trait;

use Carbon\CarbonImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

trait Timestampable
{
    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE, options: ['default' => 'CURRENT_TIMESTAMP'])]
    #[Groups(['timestamps'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE, options: ['default' => 'CURRENT_TIMESTAMP'])]
    #[Groups(['timestamps'])]
    private \DateTimeImmutable $updatedAt;

    public function getCreatedAt(): CarbonImmutable
    {
        return CarbonImmutable::create($this->createdAt);
    }

    public function getUpdatedAt(): CarbonImmutable
    {
        return CarbonImmutable::create($this->updatedAt);
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function updateTimestamps(): void
    {
        $now = CarbonImmutable::now();

        $this->updatedAt = $now;

        if (! $this->exists()) {
            $this->createdAt = $now->clone();
        }
    }
}
