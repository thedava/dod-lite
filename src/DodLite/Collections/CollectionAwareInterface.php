<?php
declare(strict_types=1);

namespace DodLite\Collections;

use Generator;

interface CollectionAwareInterface
{
    public function getCollection(string $name): CollectionInterface;

    /**
     * @return Generator<string, CollectionInterface>
     */
    public function getAllCollections(): Generator;

    public function clearCollectionCache(): void;
}
