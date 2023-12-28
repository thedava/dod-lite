<?php
declare(strict_types=1);

namespace DodLite\Exceptions;

use DodLite\DodException;
use Throwable;

class ReplicationFailedException extends DodException
{
    use Traits\CollectionAwareTrait;
    use Traits\DocumentIdAwareTrait;
    use Traits\ActionAwareTrait;


    public function __construct(
        string     $action,
        string     $collection,
        string|int $documentId,
        ?Throwable $previous = null,
    )
    {
        $this->action = $action;
        $this->collection = $collection;
        $this->documentId = $documentId;

        parent::__construct(sprintf('Failed to replicate %s document with id "%s" in collection "%s"', $action, $documentId, $collection), previous: $previous);
    }
}
