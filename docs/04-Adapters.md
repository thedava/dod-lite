# Adapters

DodLite uses adapters to store data. Adapters are classes need to implement the `DodLite\Adapter\AdapterInterface`.

There are mainly two types of adapters: (Storage) Adapters and Middleware Adapters.

## Storage Adapters

The only purpose of storage adapters is to store data. They implement the various methods to store data on file, in memory, in a database, etc.

### Bridge adapters

There are some external bridge adapters for libraries that are commonly used in PHP projects. These adapters are not part of the DodLite core but are provided as separate packages.

* [dod-lite-flysystem](https://github.com/thedava/dod-lite-flysystem) - Flysystem adapter for DodLite

### File

The `FileAdapter` provides a very simple way of storing data as files. The usage is pretty simple:

```php
// Store data locally in files
$documentManager = new \DodLite\DocumentManager(
    new \DodLite\Adapter\FileAdapter(
        '/path/to/your/storage',
        
        // Define file and directory permissions
        filePermissions: 0777,
        directoryPermissions: 0777,
        
        // Use either glob or DirectoryIterator
        useGlob: false
    )
);
```

The `FileAdapter` also brings its own custom Exception:

`FileAdapterFunctionFailedException`<br>
Internally thrown if a function call failed. Is never thrown directly but is always there as previous exception of another exception.
Provides additional debugging methods for the failed function call and the adapter configuration:

* `getFunction() : string`
* `getPath() : string`
* `getResult() : mixed`
* `getAdapterRootPath() : string`
* `getAdapterFilePermissions() : int`
* `getAdapterDirectoryPermissions() : int`
* `getAdapterUseGlob() : bool`

### Memory

The most basic adapter there is. It stores data in the memory. This adapter is useful for testing purposes or for performance reasons in combination with middleware adapters.

```php
// Store data in memory
$documentManager = new \DodLite\DocumentManager(
    new \DodLite\Adapter\MemoryAdapter()
);
```

### Null

The NullAdapter does not store any data. It is useful for testing purposes only.

```php
$documentManager = new \DodLite\DocumentManager(
    new \DodLite\Adapter\NullAdapter()
);
```

## Middleware Adapters

Middleware adapters do not store data themselves. They provide additional functionality on top of other adapters. Middleware adapters can be nested to provide more complex functionality.

### Index

The `IndexAdapter` provides an index for documents. This index is used to speed up the readAll functionality. The index is stored in a separate collection.

```php
// Store data in memory and use an index
$documentManager = new \DodLite\DocumentManager(
    new \DodLite\Adapter\Middleware\IndexAdapter(
        new \DodLite\Adapter\MemoryAdapter()
    )
);
```

### ReadOnly

The `ReadOnlyAdapter` prevents modifying data. This adapter is useful if you want to avoid that data is overwritten or deleted by accident.
You need to define the behaviour on writes via the constructor.

```php
// Store data in memory and make it read-only. Ignore writes entirely.
$documentManager = new \DodLite\DocumentManager(
    new \DodLite\Adapter\Middleware\ReadOnlyAdapter(
        new \DodLite\Adapter\MemoryAdapter(),
        throwExceptionOnModification: false
    )
);

// Store data in memory and make it read-only. Throws a ReadOnlyException on write/delete.
$documentManager = new \DodLite\DocumentManager(
    new \DodLite\Adapter\Middleware\ReadOnlyAdapter(
        new \DodLite\Adapter\MemoryAdapter(),
        throwExceptionOnModification: true
    )
);
```

### Fallback

The `FallbackAdapter` allows you to use two adapters at the same time. The primary adapter is used for all reads. If a read fails, the secondary adapter is used instead.
The primary adapter will be updated if configured to do so.

```php
// Store data in files but use a memory adapter for faster reads
$documentManager = new \DodLite\DocumentManager(
    new \DodLite\Adapter\Middleware\FallbackAdapter(
        new \DodLite\Adapter\MemoryAdapter(),
        new \DodLite\Adapter\FileAdapter(
           '/path/to/your/storage'
        ),
        updateFallbackOnFailedRead: true
    )
);
```

### Replicate

The `ReplicateAdapter` allows you to use two adapters at the same time. All modifications will be done on both adapters. All modifications will be done on the main adapter first
and then replicated to the secondary adapter. If a replication fails, the main adapter will be reverted to its previous state and a `ReplicationFailedException` will be thrown.

```php
// Replicate data to a backup device
$documentManager = new \DodLite\DocumentManager(
    new \DodLite\Adapter\Middleware\ReplicateAdapter(
        new \DodLite\Adapter\FileAdapter(
           '/path/to/your/storage'
        ),
        new \DodLite\Adapter\FileAdapter(
           '/path/to/your/backup/storage'
        )
    )
);
```

### Lock

The `LockAdapter` provides a basic lock functionality to avoid write conflicts. This is useful if you want to prevent multiple processes from writing to the database at the same time.

### PassThrough

The `PassThroughAdapter` is primarily designed to make it easier to create own adapters. It simply passes all calls to the underlying adapter and allows you to override the
methods you need to implement your custom adapter functionality.

## Utilizing middleware adapters

### Performance reads

You can use the `FallbackAdapter` and the `ReplicateAdapter` to implement performance reads.

```php
// Define a fast adapter. This could be a redis, memcached, etc.
$fastAdapter = new \DodLite\Adapter\MemoryAdapter();

// Define a slow but persistent adapter. This could be a file based adapter.
$slowAdapter = new \DodLite\Adapter\FileAdapter(
    new \League\Flysystem\Filesystem(
        new \League\Flysystem\Local\LocalFilesystemAdapter(
           '/path/to/your/storage'
        )
    )
);

// Create a new DocumentManager with performance reads
$documentManager = new \DodLite\DocumentManager(
    // Write all changes to both adapters
    new \DodLite\Adapter\Middleware\ReplicateAdapter(
        // Read primarily from the fast adapter
        new \DodLite\Adapter\Middleware\FallbackAdapter(
            $fastAdapter,
            $slowAdapter,
            updateFallbackOnFailedRead: true
        ),
        $slowAdapter
    )
);
```
