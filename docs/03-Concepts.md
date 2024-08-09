# Concepts

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

The DocumentManager implements the `\DodLite\Collections\CollectionAwareInterface`. Here is a list of the most important methods:

```php
// Get collection(s)
public function getCollection(string $name): CollectionInterface
public function getAllCollections(bool $includeSubCollections = false): Generator

// Move documents
public function moveDocument(DocumentInterface $document, CollectionInterface $sourceCollection, CollectionInterface $targetCollection, bool $overrideExisting = false): void
public function moveDocumentById(string|int $id, CollectionInterface $sourceCollection, CollectionInterface $targetCollection, bool $overrideExisting = false): void
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
public function getDocumentByFilter(\DodLite\Filter\FilterInterface $filter): ?DocumentInterface;

// Read all documents
public function getAllDocuments(int $sort = SORT_ASC): array;
public function getAllDocumentsByFilter(\DodLite\Filter\FilterInterface $filter, int $sort = SORT_ASC): array;
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
