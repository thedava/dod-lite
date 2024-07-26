<?php
declare(strict_types=1);

namespace DodLite\Adapter;

use ArrayObject;
use DodLite\DisposableInterface;
use DodLite\Exceptions\NotFoundException;
use Generator;

class MemoryAdapter implements AdapterInterface, DisposableInterface
{
    private ArrayObject $memory;

    public function __construct()
    {
        $this->memory = new ArrayObject();
    }

    public function dispose(): void
    {
        $this->memory->exchangeArray([]);
    }

    private function getCollection(string $collection): ArrayObject
    {
        return $this->memory[$collection] ??= new ArrayObject();
    }

    public function write(string $collection, int|string $id, array $data): void
    {
        $this->getCollection($collection)[$id] = $data;
    }

    public function read(string $collection, int|string $id): array
    {
        return $this->memory[$collection][$id]
            ?? throw new NotFoundException($collection, $id);
    }

    public function has(string $collection, int|string $id): bool
    {
        return $this->getCollection($collection)->offsetExists($id);
    }

    public function delete(string $collection, int|string $id): void
    {
        $this->getCollection($collection)->offsetUnset($id);
    }

    public function readAll(string $collection): Generator
    {
        foreach ($this->getCollection($collection) as $id => $data) {
            yield $id => $data;
        }
    }

    public function getAllCollectionNames(): Generator
    {
        yield from array_keys($this->memory->getArrayCopy());
    }
}
