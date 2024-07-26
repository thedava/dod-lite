<?php
declare(strict_types=1);

namespace DodLite;

interface RefreshableInterface extends DisposableInterface
{
    public function refresh(): void;
}
