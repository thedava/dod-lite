<?php
declare(strict_types=1);

namespace DodTest\Unit\DodLite\Normalizer;

use DodLite\Normalizer\DodNormalizerException;
use DodLite\Normalizer\FileToDataNormalizer;

test('FileToDataNormalizer works as expected', function (mixed $data, array $expectedResult) {
    $normalizer = new FileToDataNormalizer();
    expect($normalizer->normalize($data))->toBe($expectedResult);
})->with([
    'Valid JSON' => [
        'data'           => '{"foo":"bar"}',
        'expectedResult' => ['foo' => 'bar'],
    ],
]);

test('Invalid JSON throws exception', function () {
    $normalizer = new FileToDataNormalizer();
    $normalizer->normalize('{"foo":"bar"');
})->throws(DodNormalizerException::class);
