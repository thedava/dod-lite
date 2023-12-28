<?php
declare(strict_types=1);

namespace DodLite\Adapter\Middleware;

use DodLite\Adapter\AdapterInterface;
use DodLite\DodException;
use DodLite\Exceptions\ReplicationFailedException;
use DodLite\Exceptions\WriteFailedException;

class ReplicateAdapter extends PassThroughAdapter implements AdapterInterface
{
    public function __construct(
        private readonly AdapterInterface $mainAdapter,
        private readonly AdapterInterface $replicaAdapter,
    )
    {
        parent::__construct($this->mainAdapter);
    }

    public function write(string $collection, int|string $id, array $data): void
    {
        try {
            $this->replicaAdapter->write($collection, $id, $data);
        } catch (WriteFailedException $e) {
            throw new ReplicationFailedException('write', $collection, $id, $e);
        }

        $this->mainAdapter->write($collection, $id, $data);
    }

    public function delete(string $collection, int|string $id): void
    {
        try {
            $this->replicaAdapter->delete($collection, $id);
        } catch (DodException $e) {
            throw new ReplicationFailedException('delete', $collection, $id, $e);
        }

        $this->mainAdapter->delete($collection, $id);
    }
}
