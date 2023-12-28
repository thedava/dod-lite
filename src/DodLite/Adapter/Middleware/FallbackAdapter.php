<?php
declare(strict_types=1);

namespace DodLite\Adapter\Middleware;

use DodLite\Adapter\AdapterInterface;
use DodLite\Exceptions\NotFoundException;
use DodLite\Exceptions\ReplicationFailedException;
use Generator;
use Throwable;

class FallbackAdapter extends PassThroughAdapter implements AdapterInterface
{
    public function __construct(
        private readonly AdapterInterface $mainAdapter,
        private readonly AdapterInterface $fallbackAdapter,
        private readonly bool             $updateFallbackOnFailedRead,
    )
    {
        parent::__construct($this->fallbackAdapter);
    }

    private function syncMainByFallback(string $collection, int|string $id): array
    {
        $data = $this->fallbackAdapter->read($collection, $id);

        if ($this->updateFallbackOnFailedRead) {
            try {
                $this->mainAdapter->write($collection, $id, $data);
            } catch (Throwable $e) {
                throw new ReplicationFailedException('write', $collection, $id, $e);
            }
        }

        return $data;
    }

    public function read(string $collection, int|string $id): array
    {
        try {
            return $this->mainAdapter->read($collection, $id);
        } catch (NotFoundException) {
            return $this->syncMainByFallback($collection, $id);
        }
    }

    public function has(string $collection, int|string $id): bool
    {
        if ($this->mainAdapter->has($collection, $id)) {
            return true;
        }

        if ($this->fallbackAdapter->has($collection, $id)) {
            $this->syncMainByFallback($collection, $id);

            return true;
        }

        return false;
    }

    public function readAll(string $collection): Generator
    {
        try {
            return $this->mainAdapter->readAll($collection);
        } catch (NotFoundException) {
            return $this->fallbackAdapter->readAll($collection);
        }
    }
}
