<?php
declare(strict_types=1);

namespace DodLite\Adapter\Middleware;

use DodLite\Adapter\AdapterInterface;
use DodLite\Exceptions\Adapter\ReplicationFailedException;
use DodLite\Exceptions\NotFoundException;
use DodLite\Filter\FilterInterface;
use Generator;
use Throwable;

class FallbackAdapter extends PassThroughAdapter implements AdapterInterface
{
    public function __construct(
        private readonly AdapterInterface $primaryAdapter,
        private readonly AdapterInterface $secondaryAdapter,
        private readonly bool $updatePrimaryOnFailedRead,
    )
    {
        parent::__construct($this->secondaryAdapter);
    }

    private function syncPrimary(string $collection, int|string $id): array
    {
        try {
            $data = $this->secondaryAdapter->read($collection, $id);

            if ($this->updatePrimaryOnFailedRead) {
                $this->primaryAdapter->write($collection, $id, $data);
            }

            return $data;
        } catch (NotFoundException $e) {
            if ($this->updatePrimaryOnFailedRead) {
                throw new ReplicationFailedException('read', $collection, $id, $e);
            } else {
                throw $e;
            }
        } catch (Throwable $e) {
            throw new ReplicationFailedException('write', $collection, $id, $e);
        }
    }

    public function read(string $collection, int|string $id): array
    {
        try {
            return $this->primaryAdapter->read($collection, $id);
        } catch (NotFoundException) {
            return $this->syncPrimary($collection, $id);
        }
    }

    public function has(string $collection, int|string $id): bool
    {
        if ($this->primaryAdapter->has($collection, $id)) {
            return true;
        }

        if ($this->secondaryAdapter->has($collection, $id)) {
            $this->syncPrimary($collection, $id);

            return true;
        }

        return false;
    }

    public function readAll(string $collection, FilterInterface $filter): Generator
    {
        try {
            return $this->primaryAdapter->readAll($collection, $filter);
        } catch (NotFoundException) {
            return $this->secondaryAdapter->readAll($collection, $filter);
        }
    }

    public function getAllCollectionNames(): Generator
    {
        try {
            return $this->primaryAdapter->getAllCollectionNames();
        } catch (NotFoundException) {
            return $this->secondaryAdapter->getAllCollectionNames();
        }
    }
}
