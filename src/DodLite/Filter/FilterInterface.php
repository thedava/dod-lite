<?php
declare(strict_types=1);

namespace DodLite\Filter;

use DodLite\Documents\DocumentInterface;

interface FilterInterface
{
    public function markAsExecuted(): void;

    public function isExecuted(): bool;

    public function isDocumentIncluded(DocumentInterface $document): bool;
}
