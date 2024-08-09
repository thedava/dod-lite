<?php
declare(strict_types=1);

namespace DodLite\Filter;

abstract class AbstractFilter implements FilterInterface
{
    private bool $isExecuted = false;

    public function markAsExecuted(): void
    {
        $this->isExecuted = true;
    }

    public function isExecuted(): bool
    {
        return $this->isExecuted;
    }
}
