<?php
declare(strict_types=1);

namespace DodLite\Collections;

use ArrayObject;
use DodLite\Adapter\AdapterInterface;
use DodLite\DocumentManager;
use DodLite\Documents\DocumentInterface;
use DodLite\DodException;
use DodLite\Exceptions\NotFoundException;
use Generator;

class Collection implements CollectionInterface, CollectionAwareInterface
{
    private readonly AdapterInterface $adapter;

    /**
     * @var ArrayObject<string|int, DocumentInterface>
     */
    private ArrayObject $documents;

    /**
     * @var ArrayObject<string, Collection>
     */
    private ArrayObject $subCollections;

    public function __construct(
        private readonly string          $name,
        private readonly DocumentManager $manager,
    )
    {
        $this->adapter = $manager->getAdapter();

        $this->documents = new ArrayObject();
        $this->subCollections = new ArrayObject();
    }

    public function getName(): string
    {
        return $this->name;
    }

    private function createDocument(string|int $id, array $content): DocumentInterface
    {
        if (isset($this->documents[$id])) {
            $document = $this->documents[$id];

            // Update content of existing document
            $document->setContent($content);

            return $document;
        }

        $document = $this->manager->getDocumentBuilder()->createDocument($id, $content);
        $this->documents[$id] = $document;

        return $document;
    }

    public function clearCollectionCache(): void
    {
        foreach ($this->subCollections as $subCollection) {
            $subCollection->clearCollectionCache();
        }

        $this->subCollections = new ArrayObject();
    }

    public function clearDocumentCache(): void
    {
        $this->documents = new ArrayObject();
    }

    public function getCollection(string $name): CollectionInterface
    {
        return $this->manager->getCollection(sprintf('%s.%s', $this->name, $name));
    }

    public function writeDocument(DocumentInterface $document): void
    {
        $this->writeData($document->getId(), $document->getContent());
    }

    public function writeData(string|int $id, array $data): void
    {
        $this->adapter->write($this->name, $id, $data);
    }

    /**
     * @throws NotFoundException
     */
    public function getDocumentById(string|int $id): DocumentInterface
    {
        return $this->createDocument($id, $this->adapter->read($this->name, $id));
    }

    public function hasDocumentById(string|int $id): bool
    {
        return $this->adapter->has($this->name, $id);
    }

    /**
     * @throws \DodLite\Exceptions\NotFoundException
     */
    public function deleteDocumentById(string|int $id): void
    {
        $this->adapter->delete($this->name, $id);

        // Remove from cache
        if ($this->documents->offsetExists($id)) {
            $this->documents->offsetUnset($id);
        }
    }

    /**
     * @throws NotFoundException
     */
    public function deleteDocument(DocumentInterface $document): void
    {
        $this->deleteDocumentById($document->getId());
    }

    private function getAllDocumentsGenerator(): Generator
    {
        foreach ($this->adapter->readAll($this->name) as $id => $content) {
            yield $id => $this->createDocument($id, $content);
        }
    }

    private function sortDocuments(int $sort, array &$documents): void
    {
        switch ($sort) {
            case SORT_ASC:
                ksort($documents);
                break;
            case SORT_DESC:
                krsort($documents);
                break;
            default:
                throw new DodException(sprintf('Invalid sort type "%s"', $sort));
        }
    }

    /**
     * @return array<DocumentInterface>
     */
    public function getDocumentsByFilter(callable $filter, int $sort = SORT_ASC): array
    {
        $documents = [];
        foreach ($this->getAllDocumentsGenerator() as $document) {
            if ($filter($document)) {
                $documents[] = $document;
            }
        }

        $this->sortDocuments($sort, $documents);

        return $documents;
    }

    /**
     * @return array<DocumentInterface>
     */
    public function getAllDocuments(int $sort = SORT_ASC): array
    {
        $documents = iterator_to_array($this->getAllDocumentsGenerator());
        $this->sortDocuments($sort, $documents);

        return $documents;
    }

    public function getDocumentByFilter(callable $filter): ?DocumentInterface
    {
        foreach ($this->getAllDocumentsGenerator() as $document) {
            if ($filter($document)) {
                return $document;
            }
        }

        return null;
    }
}
