<?php
declare(strict_types=1);

namespace DodLite\Exceptions;

use DodLite\DodException;
use Throwable;

class NotFoundException extends DodException
{
    public function __construct(
        private readonly string          $collection,
        private readonly string|int|null $id,
        ?Throwable                       $previous = null,
    )
    {
        if ($id === null) {
            parent::__construct(sprintf('Collection "%s" not found', $collection), previous: $previous);
        } else {
            parent::__construct(sprintf('Document with id "%s" not found in collection "%s"', $id, $collection), previous: $previous);
        }
    }

    public function getCollection(): string
    {
        return $this->collection;
    }

    public function getId(): int|string|null
    {
        return $this->id;
    }
}
