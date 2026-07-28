<?php

declare(strict_types=1);

namespace RideFareSystem\Repository;

use RideFareSystem\User;
use RuntimeException;

class UserRepository
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
     * @return User[]
     */
    public function all(): array
    {
        $raw = file_get_contents($this->filePath);
        if ($raw === false || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            throw new RuntimeException("Corrupt users file: {$this->filePath}");
        }

        return array_map(fn (array $row) => User::fromArray($row), $decoded);
    }

    public function findByUsername(string $username): ?User
    {
        foreach ($this->all() as $user) {
            if (strcasecmp($user->getUsername(), $username) === 0) {
                return $user;
            }
        }

        return null;
    }

    public function exists(string $username): bool
    {
        return $this->findByUsername($username) !== null;
    }

    public function save(User $user): void
    {
        $users = $this->all();
        $users[] = $user;
        $this->persist($users);
    }

    /**
     * @param User[] $users
     */
    private function persist(array $users): void
    {
        $data = array_map(fn (User $u) => $u->toArray(), $users);
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            throw new RuntimeException('Failed to encode users data as JSON.');
        }

        file_put_contents($this->filePath, $json, LOCK_EX);
    }
}
