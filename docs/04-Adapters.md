# Adapters

DodLite uses adapters to store data. Adapters are classes that implement the `DodLite\Adapter\AdapterInterface`.

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
Internally thrown if a function call failed. It is never thrown directly, but it's always there as previous exception of another exception.
It also provides additional debugging methods for the failed function call and the adapter configuration:

```php
public function getFunction(): string;
public function getPath(): string;
public function getResult(): mixed;
public function getAdapterRootPath(): string;
public function getAdapterFilePermissions(): int;
public function getAdapterDirectoryPermissions(): int;
public function getAdapterUseGlob(): bool;
```

### Memory

The most basic adapter there is. It stores data in the memory. This adapter is useful for testing purposes or for increasing performance in combination with middleware adapters.

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

The `IndexAdapter` provides an index for documents. This index is used to speed up the readAll functionality. The index is stored in a separate collection (default collection: `.meta`).

```php
// Store data in memory and use an index
$documentManager = new \DodLite\DocumentManager(
    new \DodLite\Adapter\Middleware\IndexAdapter(
        new \DodLite\Adapter\MemoryAdapter()
    )
);

// Use a custom collection for the index
$documentManager = new \DodLite\DocumentManager(
    new \DodLite\Adapter\Middleware\IndexAdapter(
        new \DodLite\Adapter\MemoryAdapter(),
        indexCollection: 'myIndexCollection'
    )
);
```

The `IndexAdapter` can also be used to speed up retrieval of data with the combination of value extraction and pre-filtering. This is useful if you have a lot of data and want to
filter what data is really retrieved from the underlying adapter (to improve overall performance).
By default, all documents will be loaded and then filtered by the collection. With value extraction and pre-filtering the filtering will be done before any document is retrieved
from the underlying storage adapter.

At first, you need to define the fields that should be indexed. You can do this by providing an instance of the `IndexValueExtractorInterface`.
There are 2 predefined implementations:

* `\DodLite\Adapter\Middleware\Index\ValueExtractor\CallbackIndexValueExtractor` - Allows you to define a callback that extracts the values from all stored documents
* `\DodLite\Adapter\Middleware\Index\ValueExtractor\SimpleIndexValueExtractor` - Allows you to define a list of fields that will be extracted from all stored documents

```php
// Define a custom index value extractor (in this example: Add the value of the "discriminator" field to the index)
$indexValueExtractor = new \DodLite\Adapter\Middleware\Index\ValueExtractor\SimpleIndexValueExtractor(['discriminator']);

// Pass the extractor to the IndexAdapter
$documentManager = new \DodLite\DocumentManager(
    new \DodLite\Adapter\Middleware\IndexAdapter(
        new \DodLite\Adapter\MemoryAdapter(),
        indexValueExtractor: $indexValueExtractor,
    )
);

// On storing a document, the index will also store the value of the "discriminator" field
$collection = $documentManager->getCollection('myCollection');
$collection->writeData(1, [
    'discriminator' => 'myValue',
    'otherField' => 'otherValue',
]);
$collection->writeData(2, [
    'discriminator' => 'myOtherValue',
    'otherField' => 'myValue',
]);
```

To benefit from the performance increase, you have to pass an instance of the `IndexPreFilterInterface` to the `getAllDocumentsByFilter`/`getDocumentByFilter` methods
to filter by your extracted values:

```php
$indexPreFilter = new \DodLite\Adapter\Middleware\Index\PreFilter\CallbackIndexPreFilter(
    isDocumentIncludedCallback: fn(\DodLite\Documents\DocumentInterface $document) => true, // Let the regular filter method pass through everything
    isIndexValueIncludedCallback: fn(array $indexValues) => $indexValues['discriminator'] === 'myValue',
);

// Get all documents with the specified discriminator
// The IndexAdapter will pre-filter which documents are retrieved from the StorageAdapter and which won't. In this example, only the document with the id 1
// will be retrieved directly from the MemoryAdapter. The document with the id 2 will be skipped entirely.
$collection->getAllDocumentsByFilter($indexPreFilter);
```

### ReadOnly

The `ReadOnlyAdapter` prevents modifying data. This adapter is useful if you want to avoid that data is overwritten or deleted by accident.
You need to define the behaviour on writes via the constructor as there is no default.

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
The primary adapter will be updated if configured to do so. Can be used with the `MemoryAdapter` and a slower adapter like the `FileAdapter` to speed up reads.

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
and then replicated to the replica adapter. If a replication fails, the main adapter will be reverted to its previous state and a `ReplicationFailedException` will be thrown.
All reads will be done on the main adapter only.

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
This adapter will utilize `sleep()` to wait for the lock to be released. If the lock is not released after the configured number of tries, a `LockFailedException` will be thrown.
The adapter will wait for exactly 1 second after every try (so the total wait time is the number of max tries in seconds).
`timeout` defines how old a lock can be before it is considered invalid. If a lock is older than the timeout, it will be ignored and overwritten.
Attention: This adapter should be used very deep in the adapter tree. Otherwise, it may not work as expected (e.g. if a MemoryAdapter is between this adapter and the real storage).

```php
// Will create locks that are valid for up to 5 seconds
// Will try to acquire a lock 10 times (and wait for a total of 10 seconds to retrieve it)
$adapter = new \DodLite\Adapter\Middleware\LockAdapter(
    new \DodLite\Adapter\MemoryAdapter(),
    timeout: 5,
    maxTries: 10
);
)
```

### PassThrough

The `PassThroughAdapter` is primarily designed to make it easier to create own adapters. It simply passes all calls to the underlying adapter and allows you to override the
methods you need to implement your custom adapter functionality.
It can also be used as a default adapter for "Do nothing"-cases in more complex situations (e.g. if you want to enforce the `ReadOnly` adapter for some cases but not for all).

```php
// Passes everything through to the MemoryAdapter
$adapter = new \DodLite\Adapter\Middleware\PassThroughAdapter(
    new \DodLite\Adapter\MemoryAdapter()
);
```

## Utilizing middleware adapters

### Performance reads

You can use the `FallbackAdapter` and the `ReplicateAdapter` to implement performance reads. This pattern will allow to use a fast adapter for (consecutive) reads and a slow
but persistent adapter for writes. It keeps both adapters synchronized and will update the fast adapter if data was changed on the slow adapter.

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
            updatePrimaryOnFailedRead: true
        ),
        $slowAdapter
    )
);
```
