<?php
declare(strict_types=1);

namespace DodLite\Adapter;

use DodLite\Exceptions\DeleteFailedException;
use DodLite\Exceptions\NotFoundException;
use Generator;

class NullAdapter implements AdapterInterface
{
    public function write(string $collection, string|int $id, array $data): void
    {
        // do nothing
    }

    public function read(string $collection, string|int $id): array
    {
        throw new NotFoundException($collection, $id);
    }

    public function has(string $collection, string|int $id): bool
    {
        return false;
    }

    public function delete(string $collection, string|int $id): void
    {
        throw new DeleteFailedException($collection, $id);
    }

    public function readAll(string $collection): Generator
    {
        yield from [];
    }
}
