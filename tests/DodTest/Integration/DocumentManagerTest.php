<?php
declare(strict_types=1);

namespace DodTest\Integration;

use DodLite\Adapter\MemoryAdapter;
use DodLite\Collections\Collection;
use DodLite\Collections\CollectionBuilderInterface;
use DodLite\Collections\CollectionInterface;
use DodLite\DocumentManager;
use DodLite\Documents\Document;
use DodLite\Documents\DocumentBuilderInterface;
use DodLite\Documents\DocumentInterface;
use DodLite\Exceptions\AlreadyExistsException;

test('Basic actions work', function () {
    $manager = new DocumentManager(new MemoryAdapter());
    $collection = $manager->getCollection('pest');

    // Write data directly
    expect($collection->hasDocumentById('data'))->toBeFalse();
    $collection->writeData('data', ['source' => 'data']);

    // Write data via Document object
    expect($collection->hasDocumentById('document'))->toBeFalse();
    $documentObject = $collection->createDocument('document', ['source' => 'document']);
    $collection->writeDocument($documentObject);

    // Check that the keys exists now
    expect($collection->hasDocumentById('data'))
        ->toBeTrue()
        ->and($collection->hasDocumentById('document'))
        ->toBeTrue();

    // Check that we get a Document object even for data written directly
    $document = $collection->getDocumentById('data');
    expect($document->getContent())->toBe(['source' => 'data']);

    // Check that the Document object we get for the document object given is identical
    $document2 = $collection->getDocumentById('document');
    expect($document2)->toBe($documentObject);

    // Check that we can get all documents
    $documents = $collection->getAllDocuments();
    expect($documents)->toBe([
        'data'     => $document,
        'document' => $document2,
    ]);

    // Check that we can delete the document by id
    $collection->deleteDocumentById('data');
    expect($collection->hasDocumentById('data'))->toBeFalse();

    // Check that we can delete the document by object
    $collection->deleteDocument($documentObject);
    expect($collection->hasDocumentById('document'))->toBeFalse();
});

test('Moving documents works', function () {
    $manager = new DocumentManager(new MemoryAdapter());

    // Create document in source collection
    $collection1 = $manager->getCollection('pest1');
    $document = $collection1->createDocument('document', ['collection' => 'pest1']);
    $collection1->writeDocument($document);

    // Move document
    $collection2 = $manager->getCollection('pest2');
    $manager->moveDocument($document, $collection1, $collection2);
    expect($collection1->hasDocumentById('document'))
        ->toBeFalse()
        ->and($collection2->hasDocumentById('document'))
        ->toBeTrue()
        ->and($collection2->getDocumentById('document')->getContent())
        ->toBe(['collection' => 'pest1']);

    // Move document back (but this time by id only)
    $manager->moveDocumentById('document', $collection2, $collection1);
    expect($collection1->hasDocumentById('document'))
        ->toBeTrue()
        ->and($collection2->hasDocumentById('document'))
        ->toBeFalse();
});

test('Overriding documents via move works', function () {
    $manager = new DocumentManager(new MemoryAdapter());

    // Create document in source collection
    $collection1 = $manager->getCollection('pest1');
    $document = $collection1->createDocument('document', ['collection' => 'pest1']);
    $collection1->writeDocument($document);

    // Create document in target collection
    $collection2 = $manager->getCollection('pest2');
    $document2 = $collection2->createDocument('document', ['collection' => 'pest2']);
    $collection2->writeDocument($document2);

    // Move document
    $manager->moveDocument($document, $collection1, $collection2, true);
    expect($collection1->hasDocumentById('document'))
        ->toBeFalse()
        ->and($collection2->hasDocumentById('document'))
        ->toBeTrue()
        ->and($collection2->getDocumentById('document')->getContent())
        ->toBe(['collection' => 'pest1']);
});

test('Moving documents fails if target document exists', function () {
    $manager = new DocumentManager(new MemoryAdapter());

    // Create document in source collection
    $collection1 = $manager->getCollection('pest1');
    $document = $collection1->createDocument('document', ['collection' => 'pest1']);
    $collection1->writeDocument($document);

    // Create document in target collection
    $collection2 = $manager->getCollection('pest2');
    $document2 = $collection2->createDocument('document', ['collection' => 'pest2']);
    $collection2->writeDocument($document2);

    // Move document
    $manager->moveDocument($document, $collection1, $collection2);
})->throws(AlreadyExistsException::class);

test('Custom collection and document builder works', function () {
    $manager = new DocumentManager(
        new MemoryAdapter(),
        // Add custom data to every document
        documentBuilder: new class() implements DocumentBuilderInterface {
            public function createDocument(string|int $id, array $content): DocumentInterface
            {
                return new class($id, $content) extends Document {
                    public function getContent(): array
                    {
                        return array_merge(parent::getContent(), ['custom' => 'custom']);
                    }
                };
            }
        },
        // Append "-custom" to every collection name
        collectionBuilder: new class() implements CollectionBuilderInterface {
            public function createCollection(string $name, DocumentManager $manager): CollectionInterface
            {
                return new class($name, $manager) extends Collection {
                    public function getName(): string
                    {
                        return parent::getName() . '-custom';
                    }
                };
            }
        },
    );

    $collection = $manager->getCollection('pest');
    expect($collection->getName())->toBe('pest-custom');

    $document = $collection->createDocument('document', ['source' => 'document']);
    expect($document->getContent())->toBe(['source' => 'document', 'custom' => 'custom']);
});
