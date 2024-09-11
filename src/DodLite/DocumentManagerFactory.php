<?php
declare(strict_types=1);

namespace DodLite;

use DodLite\Adapter\AdapterFactory;
use DodLite\Adapter\Middleware\IndexAdapter;

class DocumentManagerFactory
{
    public static function createLocalFile(string $folderPath): DocumentManager
    {
        return new DocumentManager(
            AdapterFactory::createFromLocalFolderPath($folderPath)
        );
    }

    public static function createIndexedLocalFile(string $folderPath): DocumentManager
    {
        return new DocumentManager(
            new IndexAdapter(
                AdapterFactory::createFromLocalFolderPath($folderPath)
            )
        );
    }

    public static function createIndexCachedLocalFile(string $folderPath): DocumentManager
    {
        return new DocumentManager(
            new IndexAdapter(
                AdapterFactory::createMemoryCached(
                    AdapterFactory::createFromLocalFolderPath($folderPath)
                )
            )
        );
    }

    public static function createCachedLocalFile(string $folderPath): DocumentManager
    {
        return new DocumentManager(
            AdapterFactory::createMemoryCached(
                AdapterFactory::createFromLocalFolderPath($folderPath)
            )
        );
    }
}
