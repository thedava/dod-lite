<?php
declare(strict_types=1);

namespace DodLite\Collections;

use DodLite\Documents\DocumentInterface;
use DodLite\Exceptions\DeleteFailedException;
use DodLite\Exceptions\NotFoundException;
use DodLite\Exceptions\WriteFailedException;

interface CollectionInterface extends CollectionAwareInterface
{
    public function getName(): string;

    /**
     * @throws NotFoundException
     * @throws DeleteFailedException
     */
    public function deleteDocument(DocumentInterface $document): void;

    /**
     * @return array<DocumentInterface>
     */
    public function getAllDocuments(int $sort = SORT_ASC): array;

    public function createDocument(string|int $id, array $content): DocumentInterface;

    /**
     * @throws NotFoundException
     * @throws DeleteFailedException
     */
    public function deleteDocumentById(string|int $id): void;

    public function hasDocumentById(string|int $id): bool;

    public function getDocumentByFilter(callable $filter): ?DocumentInterface;

    /**
     * @throws NotFoundException
     */
    public function getDocumentById(string|int $id): DocumentInterface;

    /**
     * @throws WriteFailedException
     */
    public function writeDocument(DocumentInterface $document): void;

    /**
     * @throws WriteFailedException
     */
    public function writeData(string|int $id, array $data): void;

    /**
     * @return array<DocumentInterface>
     * @throws NotFoundException
     */
    public function getDocumentsByFilter(callable $filter, int $sort = SORT_ASC): array;

    public function clearDocumentCache(): void;
}
