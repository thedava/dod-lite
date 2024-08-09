<?php
declare(strict_types=1);

namespace DodTest\Unit\DodLite\Filter;

use DodLite\Adapter\Middleware\Index\PreFilter\CallbackIndexPreFilter;
use DodLite\Documents\DocumentInterface;

test('PreFilter works', function (callable $callback, array $extractedData, bool $expectedResult) {
    $filter = new CallbackIndexPreFilter(
        fn(DocumentInterface $document) => true,
        $callback,
    );

    expect($filter->isIndexValueIncluded($extractedData))->toBe($expectedResult);
})->with(function () {
    // "Complex" callback with logic
    $includeBoolCallback = fn(array $data) => ($data['include'] ?? false) === true;
    yield 'Explicit included' => [$includeBoolCallback, ['include' => true], true];
    yield 'Explicit excluded' => [$includeBoolCallback, ['include' => false], false];
    yield 'Implicit excluded' => [$includeBoolCallback, [], false];

    // Simple callbacks
    foreach (['false' => fn(array $data) => false, 'true' => fn(array $data) => true] as $label => $callback) {
        $expected = $label === 'true';

        yield sprintf('Case "%s": 1', $label) => [$callback, ['foo' => '1'], $expected];
        yield sprintf('Case "%s": 2', $label) => [$callback, ['bar' => '2'], $expected];
        yield sprintf('Case "%s": 3', $label) => [$callback, ['baz' => '3'], $expected];
        yield sprintf('Case "%s": 4', $label) => [$callback, [], $expected];
    }
});
