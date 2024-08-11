<?php
declare(strict_types=1);

namespace DodLite\Adapter\Middleware\Index\ValueExtractor;

class NoIndexValueExtractor implements IndexValueExtractorInterface
{
    public function extractValuesForIndex(string $collection, int|string $id, array $documentData): array
    {
        // Do not extract any values for indexing
        return [];
    }
}
