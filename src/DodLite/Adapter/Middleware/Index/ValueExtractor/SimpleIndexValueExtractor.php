<?php
declare(strict_types=1);

namespace DodLite\Adapter\Middleware\Index\ValueExtractor;

class SimpleIndexValueExtractor implements IndexValueExtractorInterface
{
    public function __construct(
        private readonly array $fieldsToExtract,
    )
    {

    }

    public function extractValuesForIndex(array $documentData): array
    {
        $extractedValues = [];
        foreach ($this->fieldsToExtract as $field) {
            $extractedValues[$field] = $documentData[$field] ?? null;
        }

        return $extractedValues;
    }
}
