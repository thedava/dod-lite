<?php
declare(strict_types=1);

namespace DodLite\Collections;

use DodLite\DocumentManager;

interface CollectionBuilderInterface
{
    public function createCollection(string $name, DocumentManager $manager): CollectionInterface;
}
