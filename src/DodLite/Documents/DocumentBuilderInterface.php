<?php
declare(strict_types=1);

namespace DodLite\Documents;

interface DocumentBuilderInterface
{
    public function createDocument(string|int $id, array $content): DocumentInterface;
}
