<?php
declare(strict_types=1);

namespace DodTest\Unit\DodLite\Normalizer;

use DodLite\Normalizer\DataToFileNormalizer;

test('DataToFileNormalizer works as expected', function (mixed $data, string $expectedResult) {
    $normalizer = new DataToFileNormalizer(pretty: false);
    expect($normalizer->normalize($data))->toBe($expectedResult);
})->with([
    'Valid JSON' => [
        'data'           => ['foo' => 'bar'],
        'expectedResult' => '{"foo":"bar"}',
    ],
]);
