<?php
declare(strict_types=1);

namespace DodLite\Collections;

interface CollectionAwareInterface
{
    public function getCollection(string $name): CollectionInterface;

    public function clearCollectionCache(): void;
}
