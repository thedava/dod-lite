<?php
declare(strict_types=1);

namespace DodLite\Adapter\Middleware;

use DodLite\Adapter\AdapterInterface;
use DodLite\Exceptions\ReadOnlyException;

class ReadOnlyAdapter extends PassThroughAdapter implements AdapterInterface
{
    public function __construct(
        AdapterInterface      $adapter,
        private readonly bool $throwExceptionOnModification = true
    )
    {
        parent::__construct($adapter);
    }

    public function write(string $collection, int|string $id, array $data): void
    {
        if ($this->throwExceptionOnModification) {
            throw new ReadOnlyException('write', $collection, $id);
        }
    }

    public function delete(string $collection, int|string $id): void
    {
        if ($this->throwExceptionOnModification) {
            throw new ReadOnlyException('delete', $collection, $id);
        }
    }
}
