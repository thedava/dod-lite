<?php
declare(strict_types=1);

namespace DodLite\Filter;

use DodLite\Documents\DocumentInterface;

class TrueFilter implements FilterInterface
{
    public function markAsExecuted(): void
    {
        // do nothing
    }

    public function isExecuted(): bool
    {
        return true;
    }

    public function isDocumentIncluded(DocumentInterface $document): bool
    {
        return true;
    }
}
