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

class Collection implements CollectionInterface
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

    public function createDocument(string|int $id, array $content, bool $write = false): DocumentInterface
    {
        if (isset($this->documents[$id])) {
            $document = $this->documents[$id];

            // Update content of existing document
            $document->setContent($content);

            if ($write) {
                $this->writeDocument($document);
            }

            return $document;
        }

        $document = $this->manager->getDocumentBuilder()->createDocument($id, $content);
        $this->documents[$id] = $document;

        if ($write) {
            $this->writeDocument($document);
        }

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
        return $this->manager->getCollection(sprintf('%s.%s', $this->getName(), $name));
    }

    public function writeDocument(DocumentInterface $document): void
    {
        $this->writeData($document->getId(), $document->getContent());
    }

    public function writeData(string|int $id, array $data): void
    {
        $this->adapter->write($this->getName(), $id, $data);
    }

    /**
     * @throws NotFoundException
     */
    public function getDocumentById(string|int $id): DocumentInterface
    {
        return $this->createDocument($id, $this->adapter->read($this->getName(), $id));
    }

    public function hasDocumentById(string|int $id): bool
    {
        return $this->adapter->has($this->getName(), $id);
    }

    /**
     * @throws NotFoundException
     */
    public function deleteDocumentById(string|int $id): void
    {
        $this->adapter->delete($this->getName(), $id);

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
        foreach ($this->adapter->readAll($this->getName()) as $id => $content) {
            yield $id => $this->createDocument($id, $content);
        }
    }

    private function sortDocuments(int|string $sort, array &$documents): void
    {
        switch ($sort) {
            case SORT_ASC:
            case 'asc':
            case 'ASC':
                ksort($documents);
                break;

            case SORT_DESC:
            case 'desc':
            case 'DESC':
                krsort($documents);
                break;

            case SORT_REGULAR:
            case 'none':
                // No sorting
                break;

            default:
                throw new DodException(sprintf('Invalid sort type "%s" given', $sort));
        }
    }

    /**
     * @return array<DocumentInterface>
     */
    public function getAllDocumentsByFilter(callable $filter, int|string $sort = SORT_ASC): array
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
    public function getAllDocuments(int|string $sort = SORT_ASC): array
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

    public function getAllCollections(): Generator
    {
        // Only return subCollections of this collection
        foreach ($this->adapter->getAllCollectionNames() as $collectionName) {
            if (str_starts_with($collectionName, $this->getName() . '.')) {
                $parts = explode('.', $collectionName);
                if (count($parts) >= 2) {
                    yield $parts[1] => $this->getCollection($parts[1]);
                }
            }
        }
    }
}
