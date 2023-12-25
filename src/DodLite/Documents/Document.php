<?php
declare(strict_types=1);

namespace DodLite\Documents;

class Document implements DocumentInterface
{
    public function __construct(
        private readonly string|int $id,
        private array               $content,
    )
    {

    }

    public function getId(): string|int
    {
        return $this->id;
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
