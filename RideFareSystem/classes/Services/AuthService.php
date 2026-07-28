<?php

declare(strict_types=1);

namespace RideFareSystem\Services;

use RideFareSystem\Authentication\PasswordHasher;
use RideFareSystem\Exceptions\AuthException;
use RideFareSystem\Exceptions\ValidationException;
use RideFareSystem\Logger;
use RideFareSystem\Repository\SessionRepository;
use RideFareSystem\Repository\UserRepository;
use RideFareSystem\User;

class AuthService
{
    private UserRepository $userRepository;
    private SessionRepository $sessionRepository;
    private PasswordHasher $passwordHasher;
    private Logger $logger;

    public function __construct(
        UserRepository $userRepository,
        SessionRepository $sessionRepository,
        PasswordHasher $passwordHasher,
        Logger $logger
    ) {
        $this->userRepository = $userRepository;
        $this->sessionRepository = $sessionRepository;
        $this->passwordHasher = $passwordHasher;
        $this->logger = $logger;
    }

    public function register(string $username, string $password): User
    {
        $username = trim($username);

        if ($username === '') {
            throw new ValidationException('Username cannot be empty.');
        }
        if (strlen($password) < 4) {
            throw new ValidationException('Password must be at least 4 characters long.');
        }
        if ($this->userRepository->exists($username)) {
            throw new AuthException("Username '{$username}' is already taken.");
        }

        $hash = $this->passwordHasher->hash($password);
        $user = new User($username, $hash);
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
            throw new AuthException('Invalid username or password.');
        }

        $this->sessionRepository->setCurrentUsername($user->getUsername());
        $this->logger->info('User Logged In', ['Username' => $user->getUsername()]);

        return $user;
    }

    public function logout(): void
    {
        $username = $this->sessionRepository->getCurrentUsername();
        $this->sessionRepository->clear();

        if ($username !== null) {
            $this->logger->info('User Logged Out', ['Username' => $username]);
        }
    }

    public function currentUser(): ?User
    {
        $username = $this->sessionRepository->getCurrentUsername();
        if ($username === null) {
            return null;
        }

        return $this->userRepository->findByUsername($username);
    }

    public function requireLoggedInUser(): User
    {
        $user = $this->currentUser();
        if ($user === null) {
            throw new AuthException('You must be logged in to do that.');
        }

        return $user;
    }
}
