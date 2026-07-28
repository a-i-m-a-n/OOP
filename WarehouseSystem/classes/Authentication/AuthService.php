<?php

declare(strict_types=1);

namespace WarehouseSystem\Authentication;

use RuntimeException;
use WarehouseSystem\Logger;
use WarehouseSystem\Repository\UserRepository;
use WarehouseSystem\User;

class AuthService
{
    private UserRepository $userRepository;
    private PasswordHasher $passwordHasher;
    private Logger $logger;

    public function __construct(UserRepository $userRepository, PasswordHasher $passwordHasher, Logger $logger)
    {
        $this->userRepository = $userRepository;
        $this->passwordHasher = $passwordHasher;
        $this->logger = $logger;
    }

    public function register(string $username, string $password): User
    {
        $username = trim($username);

        if ($username === '') {
            throw new RuntimeException('Username cannot be empty.');
        }
        if (strlen($password) < 4) {
            throw new RuntimeException('Password must be at least 4 characters long.');
        }
        if ($this->userRepository->exists($username)) {
            throw new RuntimeException("Username '{$username}' is already taken.");
        }

        $user = new User($username, $this->passwordHasher->hash($password));
        $this->userRepository->save($user);

        $this->logger->info('User Registered', ['Username' => $username]);

        return $user;
    }

    public function login(string $username, string $password): User
    {
        $username = trim($username);
        $user = $this->userRepository->findByUsername($username);

        if ($user === null || !$user->verifyPassword($password)) {
            $this->logger->warning('Failed login attempt', ['Username' => $username]);
            throw new RuntimeException('Invalid username or password.');
        }

        $this->logger->info('User Logged In', ['Username' => $user->getUsername()]);

        return $user;
    }

    public function logout(User $user): void
    {
        $this->logger->info('User Logged Out', ['Username' => $user->getUsername()]);
    }
}
