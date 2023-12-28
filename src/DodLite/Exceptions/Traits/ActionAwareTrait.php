<?php
declare(strict_types=1);

namespace DodLite\Exceptions\Traits;

trait ActionAwareTrait
{
    private string $action;

    public function getAction(): string
    {
        return $this->action;
    }
}
