<?php
declare(strict_types=1);

namespace DodLite\Exceptions;

use DodLite\DodException;
use Throwable;

class LockException extends DodException
{
    use Traits\CollectionAwareTrait;
    use Traits\ActionAwareTrait;

    public function __construct(
        string     $action,
        string     $collection,
        ?Throwable $previous = null,
    )
    {
        $this->action = $action;
        $this->collection = $collection;

        parent::__construct(sprintf('Could not %s lock for collection "%s"', $action, $collection), previous: $previous);
    }
}
