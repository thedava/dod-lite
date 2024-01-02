<?php
declare(strict_types=1);

namespace DodLite\Adapter\Middleware;

use ArrayObject;
use DateTime;
use DodLite\Adapter\AdapterInterface;
use DodLite\Adapter\MetaAdapterInterface;
use Generator;

/**
 * Creates a custom index collection for faster listing
 */
class IndexAdapter extends AbstractMetaAdapter implements AdapterInterface, MetaAdapterInterface
{
    private const FEATURE = 'index';

    private ArrayObject $index;

    public function __construct(
        private readonly AdapterInterface $adapter,
        private readonly string $indexCollection = self::META_COLLECTION,
    )
    {
        parent::__construct($adapter);

        $this->index = new ArrayObject();
    }

    private function loadIndex(string $collection): void
    {
        $indexCollection = $this->getMetaCollectionName($collection, self::FEATURE);

        $this->index[$collection] = $this->adapter->has($this->indexCollection, $indexCollection)
            ? $this->adapter->read($this->indexCollection, $indexCollection)
            : [
                'collection' => $collection,
                'ids'        => [],
            ];
    }

    private function saveIndex(string $collection): void
    {
        ksort($this->index[$collection]['ids']);
        $this->adapter->write($this->indexCollection, $this->getMetaCollectionName($collection, self::FEATURE), $this->index[$collection]);
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

    public function write(string $collection, int|string $id, array $data): void
    {
        $this->adapter->write($collection, $id, $data);
        $this->addToIndex($collection, $id);
    }

    public function has(string $collection, int|string $id): bool
    {
        $this->loadIndex($collection);

        return isset($this->index[$collection]['ids'][$id]);
    }

    public function delete(string $collection, int|string $id): void
    {
        $this->adapter->delete($collection, $id);
        $this->removeFromIndex($collection, $id);
    }

    public function readAll(string $collection): Generator
    {
        $this->loadIndex($collection);
        foreach ($this->index[$collection]['ids'] as $id => $indexMeta) {
            yield $id => $this->adapter->read($collection, $id);
        }
    }
}
