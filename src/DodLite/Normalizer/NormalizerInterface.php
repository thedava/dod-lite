<?php
declare(strict_types=1);

namespace DodLite\Normalizer;

interface NormalizerInterface
{
    /**
     * @throws DodNormalizerException
     */
    public function normalize(mixed $data): mixed;
}
