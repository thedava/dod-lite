<?php
declare(strict_types=1);

namespace DodLite\Exceptions;

use DodLite\DodException;
use Throwable;

class AlreadyExistsException extends DodException
{
    use Traits\CollectionAwareTrait;
    use Traits\DocumentIdAwareTrait;

    public function __construct(
        string     $collection,
        string|int $documentId,
        ?Throwable $previous = null,
    )
    {
        $this->collection = $collection;
        $this->documentId = $documentId;

        parent::__construct(sprintf('There is already a document with id "%s" in collection "%s"', $documentId, $collection), previous: $previous);
    }
}
