<?php
declare(strict_types=1);

namespace DodLite;

use DodLite\Adapter\AdapterInterface;
use DodLite\Data\Document;

class Collection
{
    private readonly AdapterInterface $adapter;

    public function __construct(
        private readonly string          $name,
        private readonly DocumentManager $manager,
    )
    {
        $this->adapter = $manager->getAdapter();
    }

    public function getSubCollection(string $subCollection): static
    {
        return $this->manager->getCollection(sprintf('%s.%s', $this->name, $subCollection));
    }

    public function write(Document $data): void
    {
        $this->adapter->write($this->name, $data);
    }

    /**
     * @throws KeyNotFoundException
     */
    public function read(string $key): Document
    {
        return $this->adapter->read($this->name, $key);
    }

    public function has(string $key): bool
    {
        return $this->adapter->has($this->name, $key);
    }

    /**
     * @throws KeyNotFoundException
     */
    public function delete(string $key): void
    {
        $this->adapter->delete($this->name, $key);
    }

    /**
     * @return array<Document>
     */
    public function readFiltered(callable $filter): array
    {
        return $this->adapter->readFiltered($this->name, $filter);
    }

    /**
     * @return array<Document>
     */
    public function readAll(): array
    {
        return $this->adapter->readAll($this->name);
    }
}
