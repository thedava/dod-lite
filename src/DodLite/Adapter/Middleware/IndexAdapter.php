<?php
declare(strict_types=1);

namespace DodLite\Adapter\Middleware;

use ArrayObject;
use DateTime;
use DodLite\Adapter\AdapterException;
use DodLite\Adapter\AdapterInterface;
use Generator;

/**
 * Creates a custom index collection for faster listing
 */
class IndexAdapter implements AdapterInterface
{
    private const INDEX_COLLECTION = '.index';

    private ArrayObject $index;

    public function __construct(
        private readonly AdapterInterface $adapter,
        private readonly string           $indexCollection = self::INDEX_COLLECTION,
    )
    {
        $this->index = new ArrayObject();
    }

    private function loadIndex(string $collection): void
    {
        $this->index[$collection] = $this->adapter->has($this->indexCollection, $collection)
            ? $this->adapter->read($this->indexCollection, $collection)
            : [
                'collection' => $collection,
                'ids'        => [],
            ];
    }

    private function saveIndex(string $collection): void
    {
        ksort($this->index[$collection]['ids']);
        $this->adapter->write($this->indexCollection, $collection, $this->index[$collection]);
    }

    public function addToIndex(string $collection, int|string $id): void
    {
        $this->loadIndex($collection);
        $this->index[$collection]['ids'][$id] = [
            'id'      => $id,
            'created' => (new DateTime())->format('c'),
        ];
        $this->saveIndex($collection);
    }

    public function removeFromIndex(string $collection, int|string $id): void
    {
        $this->loadIndex($collection);
        unset($this->index[$collection]['ids'][$id]);
        $this->saveIndex($collection);
    }

    private function checkCollection(string $collection): void
    {
        if ($collection === $this->indexCollection) {
            throw new AdapterException('This collection name is reserved for the index');
        }
    }

    public function write(string $collection, int|string $id, array $data): void
    {
        $this->checkCollection($collection);
        $this->adapter->write($collection, $id, $data);
        $this->addToIndex($collection, $id);
    }

    public function read(string $collection, int|string $id): array
    {
        $this->checkCollection($collection);

        return $this->adapter->read($collection, $id);
    }

    public function has(string $collection, int|string $id): bool
    {
        $this->checkCollection($collection);
        $this->loadIndex($collection);

        return isset($this->index[$collection][$id]);
    }

    public function delete(string $collection, int|string $id): void
    {
        $this->checkCollection($collection);

        $this->adapter->delete($collection, $id);
        $this->removeFromIndex($collection, $id);
    }

    public function readAll(string $collection): Generator
    {
        $this->checkCollection($collection);

        $this->loadIndex($collection);
        foreach ($this->index[$collection]['ids'] as $id => $indexMeta) {
            yield $id => $this->adapter->read($collection, $id);
        }
    }
}
