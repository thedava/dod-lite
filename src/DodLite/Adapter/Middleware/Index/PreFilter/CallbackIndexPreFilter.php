<?php
declare(strict_types=1);

namespace DodLite\Adapter\Middleware\Index\PreFilter;

use Closure;
use DodLite\Filter\CallbackFilter;

class CallbackIndexPreFilter extends CallbackFilter implements IndexPreFilterInterface
{
    public function __construct(
        Closure                  $isDocumentIncludedCallback,
        private readonly Closure $isIndexValueIncludedCallback,
    )
    {
        parent::__construct($isDocumentIncludedCallback);
    }

    public function isIndexValueIncluded(array $extractedIndexData): bool
    {
        return call_user_func_array($this->isIndexValueIncludedCallback, func_get_args());
    }
}
