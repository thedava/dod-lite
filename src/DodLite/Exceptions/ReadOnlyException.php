<?php
declare(strict_types=1);

namespace DodLite\Exceptions;

use Throwable;

class ReadOnlyException extends WriteFailedException
{
    use Traits\ActionAwareTrait;

    public function __construct(
        string     $action,
        string     $collection,
        string|int $documentId,
        ?Throwable $previous = null,
    )
    {
        $this->action = $action;

        parent::__construct(
            $collection,
            $documentId,
            previous: $previous,
            message: sprintf('Failed to %s document with id "%s" in collection "%s" because the adapter is read-only', $action, $documentId, $collection)
        );
    }
}
