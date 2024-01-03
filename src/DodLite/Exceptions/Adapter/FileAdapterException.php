<?php
declare(strict_types=1);

namespace DodLite\Exceptions\Adapter;

class FileAdapterException extends AbstractDodAdapterException
{
    public function __construct(
        string                  $message,
        private readonly string $adapterRootPath,
        private readonly int    $adapterFilePermissions,
        private readonly int    $adapterDirectoryPermissions,
        private readonly bool   $adapterUseGlob,
    )
    {
        parent::__construct($message);
    }

    public function getAdapterRootPath(): string
    {
        return $this->adapterRootPath;
    }

    public function getAdapterFilePermissions(): int
    {
        return $this->adapterFilePermissions;
    }

    public function getAdapterDirectoryPermissions(): int
    {
        return $this->adapterDirectoryPermissions;
    }

    public function getAdapterUseGlob(): bool
    {
        return $this->adapterUseGlob;
    }
}
