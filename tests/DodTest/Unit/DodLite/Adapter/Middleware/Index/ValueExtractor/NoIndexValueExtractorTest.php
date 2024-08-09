<?php
declare(strict_types=1);

namespace DodTest\Unit\DodLite\Adapter\Middleware\Index\ValueExtractor;

use DodLite\Adapter\Middleware\Index\ValueExtractor\NoIndexValueExtractor;

test('ValueExtractor works', function (array $documentData, array $expectedExtractedValues) {
    $valueExtractor = new NoIndexValueExtractor();

    expect($valueExtractor->extractValuesForIndex($documentData))->toBe($expectedExtractedValues);
})->with([
    'Case 1' => [
        ['foo' => '1'],
        [],
    ],
    'Case 2' => [
        ['bar' => '2'],
        [],
    ],
    'Case 3' => [
        ['baz' => '3'],
        [],
    ],
    'Case 4' => [
        [],
        [],
    ],
]);
