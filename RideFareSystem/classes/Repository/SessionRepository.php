<?php

declare(strict_types=1);

namespace RideFareSystem\Repository;

class SessionRepository
{
    private string $filePath;

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
            file_put_contents($this->filePath, json_encode(['username' => null]));
        }
    }

    public function getCurrentUsername(): ?string
    {
        $raw = file_get_contents($this->filePath);
        $decoded = json_decode((string) $raw, true);

        if (!is_array($decoded)) {
            return null;
        }

        return $decoded['username'] ?? null;
    }

    public function setCurrentUsername(?string $username): void
    {
        file_put_contents(
            $this->filePath,
            json_encode(['username' => $username], JSON_PRETTY_PRINT),
            LOCK_EX
        );
    }

    public function clear(): void
    {
        $this->setCurrentUsername(null);
    }
}
