<?php
declare(strict_types=1);

namespace DodLite\Adapter;

use ArrayObject;
use DodLite\Exceptions\NotFoundException;
use Generator;

class MemoryAdapter implements AdapterInterface
{
    private ArrayObject $memory;

    public function __construct()
    {
        $this->memory = new ArrayObject();
    }

    public function write(string $collection, int|string $id, array $data): void
    {
        if (!isset($this->memory[$collection])) {
            $this->memory[$collection] = new ArrayObject();
        }

        $this->memory[$collection][$id] = $data;
    }

    public function read(string $collection, int|string $id): array
    {
        return $this->memory[$collection][$id]
            ?? throw new NotFoundException($collection, $id);
    }

    public function has(string $collection, int|string $id): bool
    {
        return isset($this->memory[$collection][$id]);
    }

    public function delete(string $collection, int|string $id): void
    {
        unset($this->memory[$collection][$id]);
    }

    public function readAll(string $collection): Generator
    {
        foreach ($this->memory[$collection] ?? [] as $id => $data) {
            yield $id => $data;
        }
    }

    public function getAllCollectionNames(): Generator
    {
        yield from array_keys($this->memory->getArrayCopy());
    }
}
