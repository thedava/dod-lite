<?php
declare(strict_types=1);

namespace DodLite\Exceptions;

use DodLite\DodException;
use Throwable;

class WriteFailedException extends DodException
{
    public function __construct(
        private readonly string     $collection,
        private readonly string|int $id,
        ?Throwable                  $previous = null,
    )
    {
        parent::__construct(sprintf('Failed to write document with id "%s" in collection "%s"', $id, $collection), previous: $previous);
    }

    public function getCollection(): string
    {
        return $this->collection;
    }

    public function getId(): string|int
    {
        return $this->id;
    }
}
