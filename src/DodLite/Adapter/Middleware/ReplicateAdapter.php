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
        $hasOriginalData = $this->mainAdapter->has($collection, $id);
        if ($hasOriginalData) {
            $originalData = $this->mainAdapter->read($collection, $id);
        }

        // Try to write data to main adapter directly
        $this->mainAdapter->write($collection, $id, $data);

        $replicationSuccessful = false;
        try {
            // Try to replicate data
            $this->replicaAdapter->write($collection, $id, $data);
            $replicationSuccessful = true;
        } catch (WriteFailedException $e) {
            throw new ReplicationFailedException('write', $collection, $id, $e);
        } finally {
            if (!$replicationSuccessful) {
                if ($hasOriginalData) {
                    // Try to restore original data
                    $this->mainAdapter->write($collection, $id, $originalData);
                } else {
                    // Delete data from main adapter again
                    $this->mainAdapter->delete($collection, $id);
                }
            }
        }
    }

    public function delete(string $collection, int|string $id): void
    {
        $hasOriginalData = $this->mainAdapter->has($collection, $id);
        if ($hasOriginalData) {
            $originalData = $this->mainAdapter->read($collection, $id);
        }

        $this->mainAdapter->delete($collection, $id);

        $replicationSuccessful = false;
        try {
            $this->replicaAdapter->delete($collection, $id);
            $replicationSuccessful = true;
        } catch (DodException $e) {
            throw new ReplicationFailedException('delete', $collection, $id, $e);
        } finally {
            if (!$replicationSuccessful && $hasOriginalData) {
                // Try to restore original data
                $this->mainAdapter->write($collection, $id, $originalData);
            }
        }
    }
}
