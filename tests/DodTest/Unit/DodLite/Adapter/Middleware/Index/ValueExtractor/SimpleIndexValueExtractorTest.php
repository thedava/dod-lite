<?php
declare(strict_types=1);

namespace DodTest\Unit\DodLite\Adapter\Middleware\Index\ValueExtractor;

use DodLite\Adapter\Middleware\Index\ValueExtractor\SimpleIndexValueExtractor;

test('ValueExtractor works', function (array $documentData, array $expectedExtractedValues) {
    $valueExtractor = new SimpleIndexValueExtractor(['type']);

    expect($valueExtractor->extractValuesForIndex('test', 1, $documentData))->toBe($expectedExtractedValues);
})->with([
    'Type foo'  => [
        ['foo' => '1', 'type' => 'foo'],
        ['type' => 'foo'],
    ],
    'Type bar'  => [
        ['bar' => '2', 'type' => 'bar'],
        ['type' => 'bar'],
    ],
    'Type baz'  => [
        ['baz' => '3', 'type' => 'baz'],
        ['type' => 'baz'],
    ],
    'Type null' => [
        [],
        ['type' => null],
    ],
]);
