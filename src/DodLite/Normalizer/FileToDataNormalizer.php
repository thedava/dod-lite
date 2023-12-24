<?php
declare(strict_types=1);

namespace DodLite\Normalizer;

use Throwable;

class FileToDataNormalizer implements NormalizerInterface
{
    public function normalize(mixed $data): array
    {
        try {
            return json_decode($data, associative: true, flags: JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
            throw new DodNormalizerException('Normalization failed due to error: ' . $e->getMessage(), previous: $e);
        }
    }
}
