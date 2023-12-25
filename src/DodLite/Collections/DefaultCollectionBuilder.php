<?php
declare(strict_types=1);

namespace DodLite\Collections;

use DodLite\DocumentManager;

class DefaultCollectionBuilder implements CollectionBuilderInterface
{
    public function createCollection(string $name, DocumentManager $manager): CollectionInterface
    {
        return new Collection($name, $manager);
    }
}
