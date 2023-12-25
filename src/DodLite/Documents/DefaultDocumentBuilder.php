<?php
declare(strict_types=1);

namespace DodLite\Documents;

class DefaultDocumentBuilder implements DocumentBuilderInterface
{
    public function createDocument(string|int $id, array $content): DocumentInterface
    {
        return new Document($id, $content);
    }
}
