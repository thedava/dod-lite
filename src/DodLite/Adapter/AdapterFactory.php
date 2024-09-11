<?php
declare(strict_types=1);

namespace DodLite\Adapter;

use DodLite\Adapter\Middleware\FallbackAdapter;
use DodLite\Adapter\Middleware\ReplicateAdapter;

class AdapterFactory
{
    public static function createFromLocalFolderPath(string $folderPath): AdapterInterface
    {
        return new FileAdapter($folderPath);
    }

    public static function createMemoryCached(AdapterInterface $adapter): AdapterInterface
    {
        return new ReplicateAdapter(
            new FallbackAdapter(
                new MemoryAdapter(),
                $adapter,
                updatePrimaryOnFailedRead: true
            ),
            $adapter
        );
    }
}
