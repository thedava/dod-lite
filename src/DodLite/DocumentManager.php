<?php
declare(strict_types=1);

namespace DodLite;

use ArrayObject;

class DocumentManager
{
    private readonly ArrayObject $collections;

    public function __construct(
        private readonly Adapter\AdapterInterface $adapter,
    )
    {
        $this->collections = new ArrayObject();
    }

    public function getAdapter(): Adapter\AdapterInterface
    {
        return $this->adapter;
    }

    public function getCollection(string $collection): Collection
    {
        if (!$this->collections->offsetExists($collection)) {
            $this->collections->offsetSet($collection, new Collection($collection, $this));
        }

        return $this->collections->offsetGet($collection);
    }
}
