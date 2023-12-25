<?php
declare(strict_types=1);

namespace DodLite\Adapter;

use DodLite\Exceptions\NotFoundException;
use Generator;

interface AdapterInterface
{
    public function write(string $collection, string|int $id, array $data): void;

    /**
     * @throws \DodLite\Exceptions\NotFoundException
     */
    public function read(string $collection, string|int $id): array;

    public function has(string $collection, string|int $id): bool;

    /**
     * @throws \DodLite\Exceptions\NotFoundException
     */
    public function delete(string $collection, string|int $id): void;

    /**
     * @return Generator<string|int, array>
     */
    public function readAll(string $collection): Generator;
}
