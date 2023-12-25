<?php
declare(strict_types=1);

namespace DodLite\Normalizer;

use Throwable;

class JsonEncodeNormalizer implements NormalizerInterface
{
    private readonly int $flags;

    public function __construct(bool $pretty = true)
    {
        $flags = JSON_THROW_ON_ERROR;
        if ($pretty) {
            $flags |= JSON_PRETTY_PRINT;
        }
        $this->flags = $flags;
    }

    public function normalize(mixed $data): string
    {
        try {
            return json_encode($data, flags: $this->flags);
        } catch (Throwable $e) {
            throw new DodNormalizerException('Normalization failed due to error: ' . $e->getMessage(), previous: $e);
        }
    }
}
