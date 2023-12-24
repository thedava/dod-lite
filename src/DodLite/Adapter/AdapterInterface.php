<?php
declare(strict_types=1);

namespace DodLite\Adapter;

use DodLite\Data\Document;
use DodLite\KeyNotFoundException;

interface AdapterInterface
{
    public function write(string $collection, Document $data): void;

    /**
     * @throws KeyNotFoundException
     */
    public function read(string $collection, string $key): Document;

    public function has(string $collection, string $key): bool;

    /**
     * @throws KeyNotFoundException
     */
    public function delete(string $collection, string $key): void;

    /**
     * @return array<Document>
     */
    public function readFiltered(string $collection, callable $filter): array;

    /**
     * @return array<Document>
     */
    public function readAll(string $collection): array;
}
