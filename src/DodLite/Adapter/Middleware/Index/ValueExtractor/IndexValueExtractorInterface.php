<?php
declare(strict_types=1);

namespace DodLite\Adapter\Middleware\Index\ValueExtractor;

interface IndexValueExtractorInterface
{
    public function extractValuesForIndex(array $documentData): array;
}
