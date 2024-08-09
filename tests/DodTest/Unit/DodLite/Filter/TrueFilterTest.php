<?php
declare(strict_types=1);

namespace DodTest\Unit\DodLite\Filter;

use DodLite\Documents\Document;
use DodLite\Documents\DocumentInterface;
use DodLite\Filter\TrueFilter;

test('Filter works', function (DocumentInterface $document) {
    $filter = new TrueFilter();

    expect($filter->isDocumentIncluded($document))->toBeTrue();
})->with(function () {
    yield 'Case 1' => [new Document('phpunit-test-case-1', ['foo' => '1'])];
    yield 'Case 2' => [new Document('phpunit-test-case-2', ['bar' => '2'])];
    yield 'Case 3' => [new Document('phpunit-test-case-3', ['baz' => '3'])];
    yield 'Case 4' => [new Document('phpunit-test-case-4', [])];
});
