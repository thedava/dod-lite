<?php
declare(strict_types=1);

namespace DodLite\Normalizer;

use ArrayObject;

class FileNameNormalizer implements NormalizerInterface
{
    private readonly ArrayObject $cache;

    public function __construct()
    {
        $this->cache = new ArrayObject();
    }

    public function normalizeString(string $data): string
    {
        $data = strtolower(trim($data));
        $data = str_replace(['á', 'à', 'â', 'ä', 'ã', 'æ', 'Ä'], 'a', $data);
        $data = str_replace(['é', 'è', 'ê', 'ë'], 'e', $data);
        $data = str_replace(['í', 'ì', 'î', 'ï'], 'i', $data);
        $data = str_replace(['ó', 'ò', 'ô', 'ö', 'õ', 'Ö'], 'o', $data);
        $data = str_replace(['ú', 'ù', 'û', 'ü', 'Ü'], 'u', $data);
        $data = str_replace(['ç'], 'c', $data);
        $data = str_replace(['ñ'], 'n', $data);
        $data = str_replace(['ß'], 'ss', $data);
        $data = preg_replace('/[^a-z0-9.+]/', '_', $data);
        $data = trim($data, '_');

        while (str_contains($data, '__')) {
            $data = str_replace('__', '_', $data);
        }

        return $data;
    }

    public function normalize(mixed $data): mixed
    {
        assert(is_string($data));
        if ($this->cache->offsetExists($data)) {
            return $this->cache->offsetGet($data);
        }

        $normalized = $this->normalizeString($data);
        $this->cache->offsetSet($data, $normalized);

        return $normalized;
    }
}
