<?php
declare(strict_types=1);

namespace DodLite\Collections;

use DodLite\Documents\DocumentInterface;
use DodLite\Exceptions\DeleteFailedException;
use DodLite\Exceptions\NotFoundException;
use DodLite\Exceptions\WriteFailedException;
use DodLite\Filter\FilterInterface;

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

    /**
     * @param string|int $id      The primary identifier of the new document
     * @param array      $content The content of the new document
     * @param bool       $write   Whether to write the document directly to the database or just create an instance of the class
     */
    public function createDocument(string|int $id, array $content, bool $write = false): DocumentInterface;

    /**
     * @throws NotFoundException
     * @throws DeleteFailedException
     */
    public function deleteDocumentById(string|int $id): void;

    public function hasDocumentById(string|int $id): bool;

    public function getDocumentByFilter(FilterInterface $filter): ?DocumentInterface;

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
    public function getAllDocumentsByFilter(FilterInterface $filter, int $sort = SORT_ASC): array;

    public function clearDocumentCache(): void;
}
