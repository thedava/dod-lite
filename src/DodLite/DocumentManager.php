<?php
declare(strict_types=1);

namespace DodLite;

use ArrayObject;
use DodLite\Adapter\AdapterInterface;
use DodLite\Collections\CollectionAwareInterface;
use DodLite\Collections\CollectionBuilderInterface;
use DodLite\Collections\CollectionInterface;
use DodLite\Documents\DocumentBuilderInterface;

class DocumentManager implements CollectionAwareInterface
{
    private readonly DocumentBuilderInterface $documentBuilder;

    private readonly CollectionBuilderInterface $collectionBuilder;

    /**
     * @var ArrayObject<string, CollectionInterface>
     */
    private ArrayObject $collections;

    public function __construct(
        private readonly AdapterInterface $adapter,
        ?DocumentBuilderInterface         $documentBuilder = null,
        ?CollectionBuilderInterface       $collectionBuilder = null,
    )
    {
        $this->documentBuilder = $documentBuilder ?? new Documents\DefaultDocumentBuilder();
        $this->collectionBuilder = $collectionBuilder ?? new Collections\DefaultCollectionBuilder();

        $this->collections = new ArrayObject();
    }

    public function getAdapter(): AdapterInterface
    {
        return $this->adapter;
    }

    public function getDocumentBuilder(): DocumentBuilderInterface
    {
        return $this->documentBuilder;
    }

    public function clearCollectionCache(): void
    {
        $this->clearDocumentCache();

        // Clear all collections and sub-collections
        foreach ($this->collections as $collection) {
            if ($collection instanceof CollectionAwareInterface) {
                $collection->clearCollectionCache();
            }
        }
        $this->collections = new ArrayObject();
    }

    public function clearDocumentCache(): void
    {
        foreach ($this->collections as $collection) {
            $collection->clearDocumentCache();
        }
    }

    public function getCollection(string $name): CollectionInterface
    {
        if (!isset($this->collections[$name])) {
            $this->collections[$name] = $this->collectionBuilder->createCollection($name, $this);
        }

        return $this->collections[$name];
    }
}
