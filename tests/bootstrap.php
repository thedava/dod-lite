<?php

require dirname(__DIR__) . '/vendor/autoload.php';

const DOD_TEST_ROOT = __DIR__;
const DOD_TEMP_ROOT = DOD_TEST_ROOT . DIRECTORY_SEPARATOR . 'temp';

define('DOD_TEST_ENV', $_ENV['DOD_TEST_ENV'] ?? 'local');

function createDodTempDir(string $identifier): string
{
    do {
        $tempDir = DOD_TEMP_ROOT . DIRECTORY_SEPARATOR . $identifier . '-' . uniqid('', true);
    } while (is_dir($tempDir));

    mkdir($tempDir, 0777, true);

    return $tempDir;
}

function clearDodTempDir(?string $identifier = null): void
{
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(DOD_TEMP_ROOT, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($files as $fileInfo) {
        if ($identifier === null || str_contains($fileInfo->getRealPath(), $identifier)) {
            $todo = ($fileInfo->isDir() ? 'rmdir' : 'unlink');
            $todo($fileInfo->getRealPath());
        }
    }
}
