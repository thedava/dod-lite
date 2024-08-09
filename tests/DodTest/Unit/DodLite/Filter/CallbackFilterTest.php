<?php
declare(strict_types=1);

namespace DodTest\Unit\DodLite\Filter;

use DodLite\Documents\Document;
use DodLite\Documents\DocumentInterface;
use DodLite\Filter\CallbackFilter;

test('Filter works', function (callable $callback, DocumentInterface $document, bool $expectedResult) {
    $filter = new CallbackFilter($callback);

    expect($filter->isDocumentIncluded($document))->toBe($expectedResult);
})->with(function () {
    // "Complex" callback with logic
    $includeBoolCallback = fn(DocumentInterface $document) => ($document->getContent()['include'] ?? false) === true;
    yield 'Explicit included' => [$includeBoolCallback, new Document('phpunit-test-case-1', ['include' => true]), true];
    yield 'Explicit excluded' => [$includeBoolCallback, new Document('phpunit-test-case-2', ['include' => false]), false];
    yield 'Implicit excluded' => [$includeBoolCallback, new Document('phpunit-test-case-3', []), false];

    // Simple callbacks
    $falseCallback = fn(DocumentInterface $document) => false;
    $trueCallback = fn(DocumentInterface $document) => true;
    foreach (['false' => $falseCallback, 'true' => $trueCallback] as $label => $callback) {
        $expected = $label === 'true';

        yield sprintf('Case "%s": 1', $label) => [$callback, new Document('phpunit-test-case-1', ['foo' => '1']), $expected];
        yield sprintf('Case "%s": 2', $label) => [$callback, new Document('phpunit-test-case-2', ['bar' => '2']), $expected];
        yield sprintf('Case "%s": 3', $label) => [$callback, new Document('phpunit-test-case-3', ['baz' => '3']), $expected];
        yield sprintf('Case "%s": 4', $label) => [$callback, new Document('phpunit-test-case-4', []), $expected];
    }
});
