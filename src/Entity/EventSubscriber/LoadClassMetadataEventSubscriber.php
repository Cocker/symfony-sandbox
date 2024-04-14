<?php

declare(strict_types=1);

namespace App\Entity\EventSubscriber;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\PrePersist;
use Doctrine\ORM\Mapping\PreUpdate;

class LoadClassMetadataEventSubscriber
{
    public function __invoke(LoadClassMetadataEventArgs $eventArgs): void
    {
        $className = $eventArgs->getClassMetadata()->getName();

        if (! $this->dependsOnLifecycleEvents($className)) {
            return;
        }

        if ($this->hasLifecycleEventsAttribute($className)) {
            return;
        }

        throw new \LogicException(
            "Entity $className depends on lifecycle events, but has no HasLifecycleCallbacks attribute"
        );
    }

    private function dependsOnLifecycleEvents(string $className): bool
    {
        foreach (class_uses($className) as $trait) {
            $reflectionClass = new \ReflectionClass($trait);

            foreach ($reflectionClass->getMethods() as $method) {
                foreach ($method->getAttributes() as $attribute) {
                    if (in_array($attribute->getName(), [PrePersist::class, PreUpdate::class], true)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    private function hasLifecycleEventsAttribute(string $className): bool
    {
        $reflectionClass = new \ReflectionClass($className);

        foreach ($reflectionClass->getAttributes() as $attribute) {
            if ($attribute->getName() === HasLifecycleCallbacks::class) {
                return true;
            }
        }

        return false;
    }
}
