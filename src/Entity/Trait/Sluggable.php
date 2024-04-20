<?php

declare(strict_types=1);

namespace App\Entity\Trait;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\String\Slugger\AsciiSlugger;

trait Sluggable
{
    #[ORM\Column(type: Types::TEXT, unique: true)]
    private ?string $slug = null;

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * @return string[]
     */
    public function sluggableFields(): array
    {
        return [];
    }

    #[ORM\PrePersist]
    public function generateSlug(): void
    {
        if (empty($this->sluggableFields())) {
            throw new \LogicException('No sluggable fields defined.');
        }

        $slugger = new AsciiSlugger();
        $stringToSlug = time(); // ensure that slugs are somewhat unique

        foreach ($this->sluggableFields() as $field) {
            $fieldGetter = 'get' . ucfirst($field);

            if (! method_exists($this, $fieldGetter)) {
                throw new \LogicException("Entity missing a getter for field [$field]");
            }

            $stringToSlug .= $this->{$fieldGetter}();
        }

        $this->slug = $slugger->slug($stringToSlug)->toString();
    }

    #[ORM\PreUpdate]
    public function updateSlugIfNeeded(PreUpdateEventArgs $eventArgs): void
    {
        if (! $this->hasChangedFields($eventArgs)) {
            return;
        }

        $this->generateSlug();
    }

    private function hasChangedFields(PreUpdateEventArgs $eventArgs): bool
    {
        foreach ($this->sluggableFields() as $field) {
            if ($eventArgs->hasChangedField($field)) {
                return true;
            }
        }

        return false;
    }
}
