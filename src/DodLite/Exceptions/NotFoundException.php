<?php
declare(strict_types=1);

namespace DodLite\Exceptions;

use DodLite\DodException;
use Throwable;

class NotFoundException extends DodException
{
    use Traits\CollectionAwareTrait;

    public function __construct(
        string                           $collection,
        private readonly string|int|null $documentId,
        ?Throwable                       $previous = null,
    )
    {
        $this->collection = $collection;

        if ($documentId === null) {
            parent::__construct(sprintf('Collection "%s" not found', $collection), previous: $previous);
        } else {
            parent::__construct(sprintf('Document with id "%s" not found in collection "%s"', $documentId, $collection), previous: $previous);
        }
    }

    public function getDocumentId(): int|string|null
    {
        return $this->documentId;
    }
}
