<?php

declare(strict_types=1);

namespace WarehouseSystem\Repository;

use WarehouseSystem\User;

class UserRepository extends JsonFileRepository
{
    /**
     * @return User[]
     */
    public function all(): array
    {
        return array_map(fn (array $row) => User::fromArray($row), $this->readRows());
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
        $this->transact(function (array $rows) use ($user) {
            foreach ($rows as $row) {
                if (strcasecmp($row['username'], $user->getUsername()) === 0) {
                    throw new \RuntimeException("Username '{$user->getUsername()}' is already taken.");
                }
            }
            $rows[] = $user->toArray();
            return $rows;
        });
    }
}
