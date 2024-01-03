<?php
declare(strict_types=1);

namespace DodLite\Exceptions\Adapter;

class FileAdapterFunctionFailedException extends FileAdapterException
{
    public function __construct(
        private readonly string $function,
        private readonly string $path,
        private readonly mixed  $result,
        string                  $adapterRootPath,
        int                     $adapterFilePermissions,
        int                     $adapterDirectoryPermissions,
        bool                    $adapterUseGlob,
    )
    {
        parent::__construct(
            sprintf(
                '%s() returned %s for path "%s"',
                $function,
                $result === false ? 'false' : json_encode($result),
                $path
            ),
            $adapterRootPath,
            $adapterFilePermissions,
            $adapterDirectoryPermissions,
            $adapterUseGlob,
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
}
