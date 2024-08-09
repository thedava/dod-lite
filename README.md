# Document-oriented Database Lite

[![.github/workflows/tests.yml](https://github.com/thedava/dod-lite/actions/workflows/tests.yml/badge.svg)](https://github.com/thedava/dod-lite/actions/workflows/tests.yml)

A simple file based document-oriented pseudo database.

<strong style="color: red;">This library is still in alpha phase! Use at own risk!</strong>

The main goal was to have a library that kind of combines the functionality of SQLite and a document-oriented database like MongoDB:
Store data simply in a file without the need of a separate running database.

## Installation

via Composer

```bash
composer require thedava/dod-lite
```

## Usage

The core component of DodLite is the DocumentManager. It is used to manage collections and provides some utility functionality like moving Documents between collections.
The full documentation of the DocumentManager and an explanation of the basic concepts of DodLite can be found [here](docs/03-Concepts.md).

```php
// Create a new DocumentManager with the MemoryAdapter
$documentManager = new \DodLite\DocumentManager(
    new \DodLite\Adapter\MemoryAdapter()
);

// Get/Create collection "docs"
$collection = $documentManager->getCollection('docs');
```

### Writing data

```php
// Create a new document
$document = $collection->createDocument('README', [
    'file' => 'README.md',
    'headline' => 'Document-oriented Database Lite',
    'description' => 'A simple file based document-oriented pseudo database.',
]);

// Persist document
$collection->writeDocument($document);

// Create another document and persist it immediately
$document = $collection->createDocument('Exceptions', [
    'file' => '05-Exceptions.md',
    'headline' => 'Exceptions',
    'description' => 'DodLite employs a consistent error handling system',
], write: true);

// Write data directly without a document
$collection->writeData('Adapters', [
    'file' => '04-Adapters.md',
    'headline' => 'Adapters',
    'description' => 'DodLite uses adapters to store data',
]);

// Create a document manually and persist it
$document = new \DodLite\Documents\Document('Concepts', [
    'file' => '03-Concepts.md',
    'headline' => 'Concepts',
    'description' => 'Basic principles',
]);
$collection->writeDocument($document);
```

### Updating data

```php
// Retrieve document
$document = $collection->getDocument('Concepts');

// Update content manually
$content = $document->getContent();
$content['description'] = 'DodLite uses collections and documents to store data';
$document->setContent($content);

// Update content via helper method (array_replace_recursive)
$document->updateContent([
    'headline' => 'Concepts and Basic principles',
]);

// Persist document
$collection->writeDocument($document);
```

### Reading data

```php
// Get document
$document = $collection->getDocument('Adapters');
var_dump($document->getContent()); // { 'file' => '04-Adapters.md', ... }

// Get the first document that matches a filter
$document = $collection->getDocumentByFilter(
    new \DodLite\Filter\CallbackFilter(fn(Document $document) => $document->getContent()['file'] === '05-Exceptions.md')
);

// Get all documents
$documents = $collection->getAllDocuments();
foreach ($documents as $id => $document) {
    var_dump($document->getContent());
}

// Get all documents filtered
$documents = $collection->getAllDocumentsByFilter(
    new \DodLite\Filter\CallbackFilter(fn(Document $document) => str_ends_with($document->getContent()['file'], '.md'))
);
foreach ($documents as $id => $document) {
    var_dump($document->getContent());
}
```

### Check if data exists

```php
// Check if README exists
var_dump($collection->hasDocumentById('README')); // true
```

### Deleting data

```php
// Delete document directly by id
$collection->deleteDocumentById('Adapters');

// Delete a document object
$document = $collection->getDocument('Exceptions');
$collection->deleteDocument($document);
```

## Adapters

DodLite uses adapters for storing data. Adapters are responsible for reading and writing data. They also provide additional functionality that is built on top of other adapters.
For a full list of adapters see the [Adapters](docs/04-Adapters.md) documentation.


## Error Handling

Every error that is thrown in DodLite is either a `\DodLite\DodException` or a derivative of it.
For a full explanation of all exceptions see the [Exceptions](docs/05-Exceptions.md) documentation.

## Extensions

* [dod-lite-flysystem](https://github.com/thedava/dod-lite-flysystem) - Flysystem adapter for DodLite

## Docs

* [Concepts](docs/03-Concepts.md)
* [Adapters](docs/04-Adapters.md)
* [Exceptions](docs/05-Exceptions.md)
* [Utilities](docs/06-Utils.md)
