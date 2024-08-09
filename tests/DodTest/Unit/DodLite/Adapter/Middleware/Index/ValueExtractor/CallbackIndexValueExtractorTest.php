<?php
declare(strict_types=1);

namespace DodTest\Unit\DodLite\Adapter\Middleware\Index\ValueExtractor;

use DodLite\Adapter\Middleware\Index\ValueExtractor\CallbackIndexValueExtractor;

test('ValueExtractor works', function (array $documentData, array $expectedExtractedValues) {
    $valueExtractor = new CallbackIndexValueExtractor(function (array $documentData) {
        return [
            'result' => $documentData['value'] * $documentData['multiplier'],
        ];
    });

    expect($valueExtractor->extractValuesForIndex($documentData))->toBe($expectedExtractedValues);
})->with([
    '3x3'   => [
        ['value' => 3, 'multiplier' => 3],
        ['result' => 9],
    ],
    '20x2'  => [
        ['value' => 20, 'multiplier' => 2],
        ['result' => 40],
    ],
    '400x4' => [
        ['value' => 400, 'multiplier' => 4],
        ['result' => 1600],
    ],
]);
