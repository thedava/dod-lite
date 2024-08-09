<?php
declare(strict_types=1);

namespace DodTest\Unit\DodLite\Collections;

use DodLite\Adapter\MemoryAdapter;
use DodLite\Collections\CollectionInterface;
use DodLite\DocumentManager;
use DodLite\Documents\DocumentInterface;
use DodLite\Filter\AbstractFilter;
use DodLite\Filter\CallbackFilter;
use DodLite\Filter\FilterInterface;
use DodLite\Filter\TrueFilter;

$createSeededCollection = function (): CollectionInterface {
    $documentManager = new DocumentManager(new MemoryAdapter());
    $collection = $documentManager->getCollection('phpunit');

    $collection->writeData(1, ['type' => 'foo', 'group' => 1]);
    $collection->writeData(2, ['type' => 'bar', 'group' => 1]);
    $collection->writeData(3, ['type' => 'baz', 'group' => 1]);

    $collection->writeData(4, ['type' => 'foo', 'group' => 2]);
    $collection->writeData(5, ['type' => 'bar', 'group' => 2]);
    $collection->writeData(6, ['type' => 'baz', 'group' => 2]);

    $collection->writeData(7, ['type' => 'foo', 'group' => 3]);
    $collection->writeData(8, ['type' => 'bar', 'group' => 3]);
    $collection->writeData(9, ['type' => 'baz', 'group' => 3]);

    return $collection;
};


test('getAllDocuments works', function () use ($createSeededCollection) {
    // Check initial document count
    $collection = $createSeededCollection();
    expect(count($collection->getAllDocuments()))->toBe(9);

    // Duplicate every document
    foreach ($collection->getAllDocuments() as $document) {
        $collection->writeData($document->getId() + 9, $document->getContent());
    }
    expect(count($collection->getAllDocuments()))->toBe(18);

    // Add a single document
    $collection->writeData('phpunit', []);
    expect(count($collection->getAllDocuments()))->toBe(19);
});


test('getAllDocumentsByFilter works', function (FilterInterface $filter, int $expectedResultCount) use ($createSeededCollection) {
    $collection = $createSeededCollection();

    // Add empty document (should be ignored by every filter except TrueFilter)
    $collection->writeData('phpunit', []);

    $result = $collection->getAllDocumentsByFilter($filter);
    expect($filter->isExecuted())->toBeTrue();
    expect(count($result))->toBe($expectedResultCount);
})->with(function () {
    $createFilter = function (string $field, string|int $expectedValue): FilterInterface {
        return new class($field, $expectedValue) extends AbstractFilter {
            public function __construct(
                private readonly string     $field,
                private readonly string|int $value,
            )
            {

            }

            public function isDocumentIncluded(DocumentInterface $document): bool
            {
                return ($document->getContent()[$this->field] ?? '') === $this->value;
            }
        };
    };

    yield 'Filter nothing' => [new TrueFilter(), 10];
    yield 'Filter everything' => [new CallbackFilter(fn(DocumentInterface $document) => false), 0];

    yield 'Filter "foo"' => [$createFilter('type', 'foo'), 3];
    yield 'Filter "bar"' => [$createFilter('type', 'bar'), 3];
    yield 'Filter "baz"' => [$createFilter('type', 'baz'), 3];

    yield 'Filter Group 1' => [$createFilter('group', 1), 3];
    yield 'Filter Group 2' => [$createFilter('group', 2), 3];
    yield 'Filter Group 3' => [$createFilter('group', 3), 3];

    yield 'Filter id 1, 2, 4, 5, 8' => [new CallbackFilter(fn(DocumentInterface $document) => in_array($document->getId(), [1, 2, 4, 5, 8])), 5];

    yield 'Filter even' => [new CallbackFilter(fn(DocumentInterface $document) => is_int($document->getId()) && $document->getId() % 2 === 0), 4];
    yield 'Filter odd' => [new CallbackFilter(fn(DocumentInterface $document) => is_int($document->getId()) && $document->getId() % 2 !== 0), 5];

    yield 'Filter int ids' => [new CallbackFilter(fn(DocumentInterface $document) => is_int($document->getId())), 9];
    yield 'Filter string ids' => [new CallbackFilter(fn(DocumentInterface $document) => is_string($document->getId())), 1];
});


test('getDocumentByFilter works', function (FilterInterface $filter, int $expectedRetrievedId) use ($createSeededCollection) {
    $collection = $createSeededCollection();

    $document = $collection->getDocumentByFilter($filter);
    expect($document?->getId() ?? 0)->toBe($expectedRetrievedId);
})->with(function () {
    for ($i = 1; $i <= 9; $i++) {
        // Filter directly by id
        yield sprintf('Retrieve document %d by id', $i) => [new CallbackFilter(fn(DocumentInterface $document) => $document->getId() === $i), $i];
    }

    yield 'First with type bar' => [new CallbackFilter(fn(DocumentInterface $document) => $document->getContent()['type'] === 'bar'), 2];
    yield 'First with group 2' => [new CallbackFilter(fn(DocumentInterface $document) => $document->getContent()['group'] === 2), 4];

    yield 'No Result' => [new CallbackFilter(fn(DocumentInterface $document) => false), 0];
});
