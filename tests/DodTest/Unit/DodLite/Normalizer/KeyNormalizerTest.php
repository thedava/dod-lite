<?php
declare(strict_types=1);

namespace DodTest\Unit\DodLite\Normalizer;

use DodLite\Normalizer\KeyNormalizer;

test('KeyNormalizer works as expected', function (string $input, string $expectedResult) {
    $normalizer = new KeyNormalizer();
    expect($normalizer->normalize($input))->toBe($expectedResult);
})->with([
    'Lowercase'                  => [
        'input'          => 'LoWeRcAsE',
        'expectedResult' => 'lowercase',
    ],
    'Umlauts'                    => [
        'input'          => 'ÄÖÜäöüß',
        'expectedResult' => 'aouaouss',
    ],
    'Spaces'                     => [
        'input'          => '   spaces   ',
        'expectedResult' => 'spaces',
    ],
    'Spaces in between'          => [
        'input'          => '   spaces in between   ',
        'expectedResult' => 'spaces_in_between',
    ],
    'Multiple Spaces in between' => [
        'input'          => '   multiple   spaces   in   between   ',
        'expectedResult' => 'multiple_spaces_in_between',
    ],
    'Special chars'              => [
        'input'          => 'What?!',
        'expectedResult' => 'what',
    ],
]);
