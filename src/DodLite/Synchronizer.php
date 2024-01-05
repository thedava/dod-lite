<?php
declare(strict_types=1);

namespace DodLite;

use DodLite\Adapter\MetaAdapterInterface;
use DodLite\Collections\CollectionInterface;
use Generator;

/**
 * Synchronizes all keys from one document management to another.
 */
class Synchronizer
{
    public function __construct(
        private readonly DocumentManager $sourceManager,
        private readonly DocumentManager $targetManager,
        private readonly array           $excludeCollections = [],
        private readonly array           $includeCollections = [],
    )
    {

    }

    private function shouldSkipCollection(CollectionInterface $collection): bool
    {
        if ($this->includeCollections !== [] && !in_array($collection->getName(), $this->includeCollections, true)) {
            return true;
        }

        // Skip collections that are not supposed to be synchronized
        return $collection->getName() === MetaAdapterInterface::META_COLLECTION
            || in_array($collection->getName(), $this->excludeCollections, true);
    }

    private function getSourceCollections(): Generator
    {
        foreach ($this->sourceManager->getAllCollections(true) as $sourceCollection) {
            if (!$this->shouldSkipCollection($sourceCollection)) {
                yield $sourceCollection;
            }
        }
    }

    private function getTargetCollections(): Generator
    {
        foreach ($this->targetManager->getAllCollections(true) as $targetCollection) {
            if (!$this->shouldSkipCollection($targetCollection)) {
                yield $targetCollection;
            }
        }
    }

    private function synchronizeDocuments(): void
    {
        foreach ($this->getSourceCollections() as $sourceCollection) {
            // Write documents directly to target collection
            $targetCollection = $this->targetManager->getCollection($sourceCollection->getName());
            foreach ($sourceCollection->getAllDocuments() as $document) {
                $targetCollection->writeDocument($document);
            }
        }
    }

    private function synchronizeDeletes(): void
    {
        foreach ($this->getTargetCollections() as $targetCollection) {
            // Delete documents that are not in source collection
            $sourceCollection = $this->sourceManager->getCollection($targetCollection->getName());
            foreach ($targetCollection->getAllDocuments() as $document) {
                if (!$sourceCollection->hasDocumentById($document->getId())) {
                    $targetCollection->deleteDocument($document);
                }
            }
        }
    }

    public function synchronize(bool $synchronizeDeletes = true): void
    {
        $this->synchronizeDocuments();
        if ($synchronizeDeletes) {
            $this->synchronizeDeletes();
        }
    }
}
