<?php
declare(strict_types=1);

namespace DodLite\Filter;

use Closure;
use DodLite\Documents\DocumentInterface;

class CallbackFilter extends AbstractFilter
{
    public function __construct(
        private readonly Closure $isDocumentIncludedCallback,
    )
    {

    }

    public function isDocumentIncluded(DocumentInterface $document): bool
    {
        return call_user_func_array($this->isDocumentIncludedCallback, [$document]);
    }
}
