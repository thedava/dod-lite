<?php
declare(strict_types=1);

namespace DodLite\Exceptions\Traits;

trait CollectionAwareTrait
{
    private string $collection;

    public function getCollection(): string
    {
        return $this->collection;
    }
}
