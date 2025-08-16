<?php
declare(strict_types=1);

namespace DodLite\Adapter;

/**
 * File adapter that performs atomic writes and deletes:
 * - Writes to a temporary file and renames it into place (atomic on Unix).
 * - Deletes by renaming to a tombstone and then unlinking.
 */
final class AtomicFileAdapter extends FileAdapter
{
    private const TEMP_FOLDER_WRITE = '.__write__.';
    private const TEMP_FOLDER_DELETE = '.__del__.';

    public function __construct(
        string                $rootPath,
        int                   $filePermissions = 0777,
        int                   $directoryPermissions = 0777,
        bool                  $useGlob = false,
        private readonly bool $durable = false // true => call fsync() for durability
    )
    {
        parent::__construct($rootPath, $filePermissions, $directoryPermissions, $useGlob);
    }

    protected function ensureFileExistsWithMode(string $path): void
    {
        // No need to pre-create the file in atomic mode
    }

    /**
     * Write payload atomically via tmp-file + rename.
     */
    protected function writeString(string $path, string $payload): void
    {
        $dir = dirname($path);
        $base = basename($path);
        $tmp = $dir . DIRECTORY_SEPARATOR . $base . self::TEMP_FOLDER_WRITE . bin2hex(random_bytes(6));

        $fp = @fopen($tmp, 'xb');
        if ($fp === false) {
            throw $this->functionFailed('fopen', false, $tmp);
        }

        try {
            $off = 0;
            $len = strlen($payload);
            while ($off < $len) {
                $w = fwrite($fp, substr($payload, $off));
                if ($w === false) {
                    throw $this->functionFailed('fwrite', false, $tmp);
                }
                $off += $w;
            }
            fflush($fp);
            if ($this->durable && function_exists('fsync')) {
                @fsync($fp); // best effort
            }
        } finally {
            fclose($fp);
        }

        if (!@rename($tmp, $path)) {
            @unlink($tmp);
            throw $this->functionFailed('rename', false, $path);
        }

        // Ensure final file permissions
        @chmod($path, $this->filePermissions);
    }

    /**
     * Delete by renaming to a tombstone file, then unlinking.
     */
    protected function unlinkPath(string $path): void
    {
        $dir = dirname($path);
        $tomb = $dir . DIRECTORY_SEPARATOR . basename($path) . self::TEMP_FOLDER_DELETE . bin2hex(random_bytes(4));

        if (@rename($path, $tomb)) {
            if (!@unlink($tomb)) {
                // try rollback if unlink fails
                @rename($tomb, $path);
                throw $this->functionFailed('unlink', false, $tomb);
            }

            return;
        }

        // fallback: direct delete
        if (!@unlink($path)) {
            throw $this->functionFailed('unlink', false, $path);
        }
    }
}
