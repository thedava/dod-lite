<?php
declare(strict_types=1);

namespace DodLite\Normalizer;

interface NormalizerInterface
{
    public function normalize(mixed $data): mixed;
}
