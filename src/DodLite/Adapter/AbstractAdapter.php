<?php
declare(strict_types=1);

namespace DodLite\Adapter;

use DodLite\KeyNotFoundException;

abstract class AbstractAdapter implements AdapterInterface
{
    public function has(string $collection, string $key): bool
    {
        try {
            $this->read($collection, $key);

            return true;
        } catch (KeyNotFoundException) {
            return false;
        }
    }

    public function readFiltered(string $collection, callable $filter): array
    {
        return array_filter($this->readAll($collection), $filter);
    }
}
