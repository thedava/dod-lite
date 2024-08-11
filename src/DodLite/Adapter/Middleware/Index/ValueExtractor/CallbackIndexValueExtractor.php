<?php
declare(strict_types=1);

namespace DodLite\Adapter\Middleware\Index\ValueExtractor;

use Closure;

class CallbackIndexValueExtractor implements IndexValueExtractorInterface
{
    public function __construct(
        private readonly Closure $valueExtractorCallback,
    )
    {

    }

    public function extractValuesForIndex(string $collection, int|string $id, array $documentData): array
    {
        return call_user_func_array($this->valueExtractorCallback, func_get_args());
    }
}
