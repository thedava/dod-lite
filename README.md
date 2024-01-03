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
The full documentation of the DocumentManager and a explanation of the basic concepts of DodLite can be found [here](docs/03-Concepts.md).

```php
// Create a new DocumentManager with the MemoryAdapter
$documentManager = new \DodLite\DocumentManager(
    new \DodLite\Adapter\MemoryAdapter()
);
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
