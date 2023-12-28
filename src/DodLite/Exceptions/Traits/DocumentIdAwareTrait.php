<?php
declare(strict_types=1);

namespace DodLite\Exceptions\Traits;

trait DocumentIdAwareTrait
{
    private string|int $documentId;

    public function getDocumentId(): string|int
    {
        return $this->documentId;
    }
}
