<?php
declare(strict_types=1);

namespace DodLite\Adapter\Middleware;

use DodLite\Adapter\AdapterInterface;
use Generator;

/**
 * An adapter that passes through all calls to the given adapter. Meant to be overridden by custom adapters.
 */
class PassThroughAdapter implements AdapterInterface
{
    public function __construct(
        private readonly AdapterInterface $adapter,
    )
    {
    }

    public function write(string $collection, int|string $id, array $data): void
    {
        $this->adapter->write($collection, $id, $data);
    }

    public function read(string $collection, int|string $id): array
    {
        return $this->adapter->read($collection, $id);
    }

    public function has(string $collection, int|string $id): bool
    {
        return $this->adapter->has($collection, $id);
    }

    public function delete(string $collection, int|string $id): void
    {
        $this->adapter->delete($collection, $id);
    }

    /**
     * @return Generator<string|int, array>
     */
    public function readAll(string $collection): Generator
    {
        yield from $this->adapter->readAll($collection);
    }

    /**
     * @return Generator<string>
     */
    public function getAllCollectionNames(): Generator
    {
        yield from $this->adapter->getAllCollectionNames();
    }
}
