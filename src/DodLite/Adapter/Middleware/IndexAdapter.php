<?php
declare(strict_types=1);

namespace DodLite\Adapter\Middleware;

use ArrayObject;
use DateTime;
use DodLite\Adapter\AdapterInterface;
use DodLite\Adapter\MetaAdapterInterface;
use DodLite\Adapter\Middleware\Index\PreFilter\IndexPreFilterInterface;
use DodLite\Adapter\Middleware\Index\ValueExtractor\IndexValueExtractorInterface;
use DodLite\Adapter\Middleware\Index\ValueExtractor\NoIndexValueExtractor;
use DodLite\Filter\FilterInterface;
use DodLite\Filter\TrueFilter;
use DodLite\RefreshableInterface;
use Generator;

/**
 * Creates a custom index collection for faster listing
 */
class IndexAdapter extends AbstractMetaAdapter implements AdapterInterface, MetaAdapterInterface, RefreshableInterface
{
    private const FEATURE = 'index';

    private ArrayObject $index;

    private readonly IndexValueExtractorInterface $indexValueExtractor;

    public function __construct(
        private readonly AdapterInterface $adapter,
        private readonly string       $indexCollection = self::META_COLLECTION,
        ?IndexValueExtractorInterface $indexValueExtractor = null,
    )
    {
        parent::__construct($adapter);

        $this->index = new ArrayObject();
        $this->indexValueExtractor = $indexValueExtractor ?? new NoIndexValueExtractor();
    }

    public function deleteIndex(string $collection): void
    {
        $this->adapter->delete($this->indexCollection, $this->getMetaCollectionName($collection, self::FEATURE));
    }

    public function recreateIndex(string $collection): void
    {
        $this->deleteIndex($collection);
        $this->loadIndex($collection);
    }

    public function dispose(): void
    {
        foreach ($this->getAllCollectionNames() as $collectionName) {
            $this->deleteIndex($collectionName);
        }
    }

    public function refresh(): void
    {
        foreach ($this->getAllCollectionNames() as $collectionName) {
            $this->recreateIndex($collectionName);
        }
    }

    private function loadIndex(string $collection): void
    {
        $indexCollection = $this->getMetaCollectionName($collection, self::FEATURE);

        $this->index[$collection] = $this->adapter->has($this->indexCollection, $indexCollection)
            ? $this->adapter->read($this->indexCollection, $indexCollection)
            : [
                'initial' => true,
                'collection' => $collection,
                'ids'        => [],
            ];

        // Add existing data to index
        if (isset($this->index[$collection]['initial'])) {
            foreach ($this->adapter->readAll($collection, new TrueFilter()) as $id => $data) {
                $this->addToIndex($collection, $id, $data, persist: false);
            }

            unset($this->index[$collection]['initial']);
            $this->saveIndex($collection);
        }
    }

    private function saveIndex(string $collection): void
    {
        ksort($this->index[$collection]['ids']);
        $this->adapter->write($this->indexCollection, $this->getMetaCollectionName($collection, self::FEATURE), $this->index[$collection]);
    }

    public function addToIndex(string $collection, int|string $id, array $data, bool $persist = true): void
    {
        if ($persist) {
            $this->loadIndex($collection);
        }

        $now = new DateTime();
        $this->index[$collection]['ids'][$id] = [
            'id'              => $id,
            'created' => $this->index[$collection]['ids'][$id]['created'] ?? $now->format('c'),
            'updated'         => $now->format('c'),
            'extractedValues' => $this->indexValueExtractor->extractValuesForIndex($collection, $id, $data),
        ];

        if ($persist) {
            $this->saveIndex($collection);
        }
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
        $this->addToIndex($collection, $id, $data);
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

    public function readAll(string $collection, FilterInterface $filter): Generator
    {
        /** @var FilterInterface|IndexPreFilterInterface $filter */
        $isPreFilter = $filter instanceof IndexPreFilterInterface;

        $this->loadIndex($collection);
        foreach ($this->index[$collection]['ids'] as $id => $indexMeta) {
            if (!$isPreFilter || $filter->isIndexValueIncluded($indexMeta['extractedValues'])) {
                yield $id => $this->adapter->read($collection, $id);
            }
        }
    }
}
