<?php

declare(strict_types=1);

namespace WarehouseSystem\Repository;

use RuntimeException;

abstract class JsonFileRepository
{
    protected string $filePath;

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
        $this->ensureFileExists();
    }

    private function ensureFileExists(): void
    {
        $dir = dirname($this->filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        if (!file_exists($this->filePath)) {
            file_put_contents($this->filePath, '[]');
        }
    }

    /**
     * Shared-lock read of the raw row array. Safe for concurrent reads.
     */
    protected function readRows(): array
    {
        $handle = fopen($this->filePath, 'r');
        if ($handle === false) {
            throw new RuntimeException("Cannot open {$this->filePath} for reading.");
        }

        try {
            flock($handle, LOCK_SH);
            $raw = stream_get_contents($handle);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }

        if ($raw === false || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    protected function transact(callable $mutator): void
    {
        $handle = fopen($this->filePath, 'c+');
        if ($handle === false) {
            throw new RuntimeException("Cannot open {$this->filePath} for writing.");
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                throw new RuntimeException("Could not acquire lock on {$this->filePath}.");
            }

            $raw = stream_get_contents($handle);
            $rows = ($raw === false || trim($raw) === '') ? [] : json_decode($raw, true);
            if (!is_array($rows)) {
                $rows = [];
            }

            $newRows = $mutator($rows);

            $json = json_encode($newRows, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            if ($json === false) {
                throw new RuntimeException("Failed to encode data for {$this->filePath}.");
            }

            rewind($handle);
            ftruncate($handle, 0);
            fwrite($handle, $json);
            fflush($handle);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
}
