<?php
declare(strict_types=1);

namespace DodLite\Adapter\Middleware\Index\ValueExtractor;

interface IndexValueExtractorInterface
{
    public function extractValuesForIndex(string $collection, int|string $id, array $documentData): array;
}
