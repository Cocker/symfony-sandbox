<?php

namespace App\Entity;

abstract class AbstractEntity
{
    public function exists(): bool {
        if (! method_exists($this, 'getId')) {
            throw new \LogicException('Entity must implement getId() method');
        }

        return $this->getId() !== null;
    }
}
