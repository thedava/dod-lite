<?php
declare(strict_types=1);

namespace DodLite\Adapter\Middleware\Index\PreFilter;

use DodLite\Filter\FilterInterface;

interface IndexPreFilterInterface extends FilterInterface
{
    public function isIndexValueIncluded(array $extractedIndexData): bool;
}
