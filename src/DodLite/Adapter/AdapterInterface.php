<?php
declare(strict_types=1);

namespace DodLite\Adapter;

use DodLite\Exceptions\DeleteFailedException;
use DodLite\Exceptions\NotFoundException;
use DodLite\Exceptions\WriteFailedException;
use Generator;

interface AdapterInterface
{
    /**
     * @throws WriteFailedException
     */
    public function write(string $collection, string|int $id, array $data): void;

    /**
     * @throws NotFoundException
     */
    public function read(string $collection, string|int $id): array;

    public function has(string $collection, string|int $id): bool;

    /**
     * @throws NotFoundException
     * @throws DeleteFailedException
     */
    public function delete(string $collection, string|int $id): void;

    /**
     * @return Generator<string|int, array>
     */
    public function readAll(string $collection): Generator;

    /**
     * @return Generator<string>
     */
    public function getAllCollectionNames(): Generator;
}
