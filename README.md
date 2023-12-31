# Document-oriented Database Lite

[![.github/workflows/tests.yml](https://github.com/thedava/dod-lite/actions/workflows/tests.yml/badge.svg)](https://github.com/thedava/dod-lite/actions/workflows/tests.yml)

A simple file based document-oriented pseudo database.

**This library is still in alpha phase! Use at own risk!**

The main goal was to have a library that kind of combines the functionality of SQLite and a document-oriented database like MongoDB:
Store data simply in a file without the need of a separate running database.

## Installation

via Composer

```bash
composer require thedava/dod-lite
```

## Extensions

* [dod-lite-flysystem](https://github.com/thedava/dod-lite-flysystem) - Flysystem adapter for DodLite

## Basic principles

DodLite uses collections and documents to store data. Documents are stored within collections and can only be retrieved by their unique identifier. DodLite does not
support complex queries. You can retrieve documents either by their unique identifier directly or by listing all documents of a collection. Writing and reading always
requires the unique identifier of a document.

DodLite is not able to handle large amounts of data. Its intention is to provide an extremely simple database for small projects. You can tweak performance by utilizing
middleware adapters such as the `IndexAdapter` and by employing SubCollections for data that isn't required constantly.

### DocumentManager

The DocumentManager is the core of this library. It is used to manage collections and provides some utility functionality like moving Documents between collections.
The DocumentManager requires an adapter to store data. The DocumentManager also provides the functionality to create new collections and documents via Builder classes.
You can define your own Builder classes by implementing the `DocumentBuilderInterface` and/or `CollectionBuilderInterface`.

```php
// Create a new DocumentManager with the MemoryAdapter
$documentManager = new \DodLite\DocumentManager(
    new \DodLite\Adapter\MemoryAdapter()
);

// Create a new DocumentManager with custom builder classes
$documentManager = new \DodLite\DocumentManager(
    new \DodLite\Adapter\MemoryAdapter(),
    new MyCustomDocumentBuilder(),
    new MyCustomCollectionBuilder()
);
```

### Collections

Collections are used to store documents. A collection is basically a list of documents. It provides the basic CRUD functionality for documents.

```php
// Get a collection (non-existing collections are created automatically by writing the first document)
$collection = $documentManager->getCollection('users');

// Create a new document
$document = $collection->createDocument(
    1,
    [
        'name' => 'Jane Doe',
        'age' => 42
    ],
    write: true
);

// Create a document by writing data directly
$collection->writeData(
    'john',
    [
        'name' => 'John Doe',
        'age' => 42
    ]
);

// Create a document and write it later
$document = $collection->createDocument(
    2,
    [
        'name' => 'Jane Doe',
        'age' => 42
    ],
    write: false
);
$collection->writeDocument($document);
```

Collections support a variety of methods. See the `CollectionInterface` for more information.

```php
// Deleting
public function deleteDocument(DocumentInterface $document): void;
public function deleteDocumentById(string|int $id): void;

// Writing data
public function createDocument(string|int $id, array $content, bool $write = false): DocumentInterface;
public function writeData(string|int $id, array $data): void;
public function writeDocument(DocumentInterface $document): void;

// Check existence
public function hasDocumentById(string|int $id): bool;

// Read single document
public function getDocumentById(string|int $id): DocumentInterface;
public function getDocumentByFilter(callable $filter): ?DocumentInterface;

// Read all documents
public function getAllDocuments(int $sort = SORT_ASC): array;
public function getDocumentsByFilter(callable $filter, int $sort = SORT_ASC): array;
```

#### SubCollections

Every Collection can have SubCollections. SubCollections are basically Collections that are stored using the parent collection as prefix. And SubCollections can have
SubCollections themselves. This allows you to structure your data in a tree-like structure. SubCollections are useful for storing data that is not required constantly
to increase the performance of the readAll functionality.

```php
// Collection for users
$collection = $documentManager->getCollection('users');

// SubCollection for disabled users only
$subCollection = $collection->getCollection('disabled');
```

### Documents

This library uses documents to store data. The Document class basically only contains a simple data array and a unique identifier.
The identifier is used to identify the document in the database. Strings and integers are supported as identifiers.

```php
// Create a new document with primary id 1
$document = new Document(1, [
    'name' => 'Jane Doe',
    'age' => 42
]);

// Create a second document with primary id "john" using the DocumentBuilder of a collection
$documentManager->getCollection('users')->createDocument('john', [
    'name' => 'John Doe',
    'age' => 42
]);
```

## Adapters

Adapters are used to store data on a medium of choice.

### File

The `FileAdapter` provides a very simple way of storing data as files. The usage is pretty simple:

```php
// Store data locally in files
$documentManager = new \DodLite\DocumentManager(
    new \DodLite\Adapter\FileAdapter(
           '/path/to/your/storage',
           
           // Define file and folder permissions
           permissions: 0666,
           
           // Use either glob or DirectoryIterator
           useGlob: false
        )
    )
);
```

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

## Error Handling

DodLite employs a consistent error handling system to ensure that all encountered errors are easy to identify and manage. Every error thrown by the library is either an
instance of `DodException` or a derivative of it. This allows you to efficiently catch and respond to errors. All errors contain the original exception as previous
exception if there is one.

### Exception Classes

Here is a list of the exception classes derived from `\DodLite\DodException`:

**AlreadyExistsException**<br>
`\DodLite\Exceptions\AlreadyExistsException`<br>
Thrown exclusively by the `moveDocument` functionality of the DocumentManager. Indicates that the target collection already contains a document with the same identifier.

**DeleteFailedException**<br>
`\DodLite\Exceptions\DeleteFailedException`<br>
Thrown when a document could not be deleted.

**LockException**<br>
`\DodLite\Exceptions\LockException`<br>
Thrown when a lock could not be acquired. This exception is exclusively thrown by the `LockAdapter`.

**NotFoundException**<br>
`\DodLite\Exceptions\NotFoundException`<br>
Thrown when a requested document is not found.

**ReadOnlyException**<br>
`\DodLite\Exceptions\ReadOnlyException`<br>
Thrown when a modification operation (like read or write) is attempted on a read-only adapter. This exception is exclusively thrown by the `ReadOnlyAdapter` and
only if configured to do so.

**ReplicationFailedException**<br>
`\DodLite\Exceptions\ReplicationFailedException`<br>
Thrown when a replication failed. This exception is exclusively thrown by the `ReplicateAdapter` and only if the operation on the replicated adapter failed. If the operation
failed on the main adapter regular exceptions like `WriteFailedException` will be thrown.

**WriteFailedException**<br>
`\DodLite\Exceptions\WriteFailedException`<br>
Thrown when a document could not be written.
