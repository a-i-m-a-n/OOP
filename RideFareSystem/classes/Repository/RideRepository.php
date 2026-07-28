<?php

declare(strict_types=1);

namespace RideFareSystem\Repository;

use RideFareSystem\Ride;
use RuntimeException;

class RideRepository
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
            file_put_contents($this->filePath, '[]');
        }
    }

    /**
     * @return Ride[]
     */
    public function all(): array
    {
        $raw = file_get_contents($this->filePath);
        if ($raw === false || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            throw new RuntimeException("Corrupt rides file: {$this->filePath}");
        }

        return array_map(fn (array $row) => Ride::fromArray($row), $decoded);
    }

    /**
     * @return Ride[]
     */
    public function findByUsername(string $username): array
    {
        return array_values(array_filter(
            $this->all(),
            fn (Ride $ride) => strcasecmp($ride->getUsername(), $username) === 0
        ));
    }

    public function countByUsername(string $username): int
    {
        return count($this->findByUsername($username));
    }

    public function save(Ride $ride): void
    {
        $rides = $this->all();
        $rides[] = $ride;
        $this->persist($rides);
    }

    /**
     * @param Ride[] $rides
     */
    private function persist(array $rides): void
    {
        $data = array_map(fn (Ride $r) => $r->toArray(), $rides);
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            throw new RuntimeException('Failed to encode rides data as JSON.');
        }

        file_put_contents($this->filePath, $json, LOCK_EX);
    }
}
