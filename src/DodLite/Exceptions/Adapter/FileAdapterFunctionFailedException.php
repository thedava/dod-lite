<?php
declare(strict_types=1);

namespace DodLite\Exceptions\Adapter;

class FileAdapterFunctionFailedException extends DodAdapterException
{
    public function __construct(
        private readonly string $function,
        private readonly string $path,
        private readonly mixed  $result,
        private readonly string $adapterRootPath,
        private readonly int    $adapterFilePermissions,
        private readonly int    $adapterDirectoryPermissions,
        private readonly bool   $adapterUseGlob,
    )
    {
        parent::__construct(
            sprintf(
                '%s() returned %s for path "%s"',
                $function,
                $result === false ? 'false' : json_encode($result),
                $path
            ),
        );
    }

    public function getFunction(): string
    {
        return $this->function;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getResult(): mixed
    {
        return $this->result;
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
