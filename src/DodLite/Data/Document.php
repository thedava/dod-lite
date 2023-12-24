<?php
declare(strict_types=1);

namespace DodLite\Data;

class Document
{
    public function __construct(
        private readonly string $key,
        private array $content,
    )
    {

    }

    public function __toString(): string
    {
        return $this->getKey();
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getContent(): array
    {
        return $this->content;
    }

    public function setContent(array $content): void
    {
        $this->content = $content;
    }
}
