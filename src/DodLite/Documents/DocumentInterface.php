<?php
declare(strict_types=1);

namespace DodLite\Documents;

interface DocumentInterface
{
    public function getId(): string|int;

    public function getContent(): array;

    public function setContent(array $content): void;

    public function updateContent(array $updates): void;
}
