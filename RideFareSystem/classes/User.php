<?php

declare(strict_types=1);

namespace RideFareSystem;

class User
{
    private string $username;
    private string $passwordHash;
    private string $createdAt;

    public function __construct(string $username, string $passwordHash, ?string $createdAt = null)
    {
        $this->username = $username;
        $this->passwordHash = $passwordHash;
        $this->createdAt = $createdAt ?? date('Y-m-d H:i:s');
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function verifyPassword(string $plainPassword): bool
    {
        return password_verify($plainPassword, $this->passwordHash);
    }

    /**
     * @return array{username: string, password_hash: string, created_at: string}
     */
    public function toArray(): array
    {
        return [
            'username' => $this->username,
            'password_hash' => $this->passwordHash,
            'created_at' => $this->createdAt,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['username'],
            $data['password_hash'],
            $data['created_at'] ?? null
        );
    }
}
