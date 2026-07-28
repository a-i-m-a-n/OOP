<?php

declare(strict_types=1);

spl_autoload_register(function (string $class): void {
    $prefix = 'RideFareSystem\\';
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $relativePath = str_replace('\\', DIRECTORY_SEPARATOR, $relative) . '.php';
    $file = __DIR__ . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . $relativePath;

    if (is_file($file)) {
        require $file;
    }
});

use RideFareSystem\Authentication\PasswordHasher;
use RideFareSystem\Exceptions\AuthException;
use RideFareSystem\Exceptions\ValidationException;
use RideFareSystem\Logger;
use RideFareSystem\Receipt;
use RideFareSystem\Repository\RideRepository;
use RideFareSystem\Repository\SessionRepository;
use RideFareSystem\Repository\UserRepository;
use RideFareSystem\Ride;
use RideFareSystem\Services\AuthService;
use RideFareSystem\Services\FareCalculator;
use RideFareSystem\Services\RideService;

$dataDir = __DIR__ . '/data';
$logger = new Logger(__DIR__ . '/logs/app.log');

$userRepository = new UserRepository($dataDir . '/users.json');
$rideRepository = new RideRepository($dataDir . '/rides.json');
$sessionRepository = new SessionRepository($dataDir . '/session.json');

$authService = new AuthService($userRepository, $sessionRepository, new PasswordHasher(), $logger);
$rideService = new RideService($rideRepository, new FareCalculator(), $logger);


function prompt(string $label): string
{
    echo $label;
    $line = fgets(STDIN);
    return $line === false ? '' : trim($line);
}

function promptFloat(string $label): float
{
    while (true) {
        $value = prompt($label);
        if (is_numeric($value) && (float) $value > 0) {
            return (float) $value;
        }
        echo "  Please enter a positive number.\n";
    }
}

function promptInt(string $label): int
{
    while (true) {
        $value = prompt($label);
        if (ctype_digit($value) && (int) $value > 0) {
            return (int) $value;
        }
        echo "  Please enter a positive whole number.\n";
    }
}

function promptYesNo(string $label): bool
{
    while (true) {
        $value = strtolower(prompt($label . ' (y/n): '));
        if (in_array($value, ['y', 'yes'], true)) {
            return true;
        }
        if (in_array($value, ['n', 'no'], true)) {
            return false;
        }
        echo "  Please answer y or n.\n";
    }
}

function promptBookingTime(string $label): string
{
    while (true) {
        $value = prompt($label);
        if ($value === '') {
            return date('H:i');
        }
        if (preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $value)) {
            return $value;
        }
        echo "  Please enter a time as HH:MM (24-hour), or leave blank for now.\n";
    }
}

function promptChoice(string $label, array $options): string
{
    while (true) {
        echo $label . "\n";
        foreach ($options as $key => $text) {
            echo "  {$key}) {$text}\n";
        }
        $choice = prompt('> ');
        if (array_key_exists($choice, $options)) {
            return $choice;
        }
        echo "  Invalid choice.\n";
    }
}

function printBanner(string $text): void
{
    echo "\n" . str_repeat('=', 50) . "\n";
    echo $text . "\n";
    echo str_repeat('=', 50) . "\n";
}

function handleRegister(AuthService $authService): void
{
    printBanner('REGISTER');
    $username = prompt('Choose a username: ');
    $password = prompt('Choose a password: ');

    $authService->register($username, $password);
    echo "Registration successful! You can now log in.\n";
}

function handleLogin(AuthService $authService): void
{
    printBanner('LOGIN');
    $username = prompt('Username: ');
    $password = prompt('Password: ');

    $user = $authService->login($username, $password);
    echo "Welcome back, {$user->getUsername()}!\n";
}

function handleBookRide(AuthService $authService, RideService $rideService): void
{
    $user = $authService->requireLoggedInUser();

    printBanner('BOOK A RIDE');

    $rideTypeChoice = promptChoice('Select ride type:', [
        '1' => 'Economy',
        '2' => 'Premium',
        '3' => 'Bike',
    ]);
    $rideTypeMap = ['1' => 'Economy', '2' => 'Premium', '3' => 'Bike'];
    $rideType = $rideTypeMap[$rideTypeChoice];

    $distanceKm = promptFloat('Distance (km): ');
    $durationMinutes = promptInt('Duration (minutes): ');
    $bookingTime = promptBookingTime('Booking time (HH:MM, blank = now): ');

    $isAirportRide = false;
    if ($rideType === 'Bike') {
        echo "Bike rides are not eligible for airport bookings.\n";
    } else {
        $isAirportRide = promptYesNo('Is this an airport ride?');
    }

    $couponCode = prompt('Coupon code (leave blank for none): ');
    $coupon = $rideService->resolveCoupon($couponCode);

    $ride = $rideService->bookRide(
        $user,
        $rideType,
        $distanceKm,
        $durationMinutes,
        $bookingTime,
        $isAirportRide,
        $coupon
    );

    echo "\n";
    (new Receipt($ride))->print();
}

function handleViewHistory(AuthService $authService, RideService $rideService): void
{
    $user = $authService->requireLoggedInUser();
    $rides = $rideService->getHistory($user);

    printBanner('RIDE HISTORY');

    if (empty($rides)) {
        echo "You have no completed rides yet.\n";
        return;
    }

    foreach ($rides as $index => $ride) {
        /** @var Ride $ride */
        $breakdown = $ride->getFareBreakdown();
        $num = $index + 1;
        $fare = number_format($breakdown['final_fare'] ?? 0.0, 2);
        echo "{$num}. [{$ride->getBookingTime()}] {$ride->getRideType()} — "
            . "{$ride->getDistanceKm()}km / {$ride->getDurationMinutes()}min — Final Fare: Rs{$fare}\n";
    }
}

function handleLogout(AuthService $authService): void
{
    $authService->logout();
    echo "You have been logged out.\n";
}

echo "--------------------------------------------------\n";
echo "   RIDE FARE CALCULATION SYSTEM\n";
echo "---------------------------------------------------\n";

$running = true;

while ($running) {
    try {
        $currentUser = $authService->currentUser();

        if ($currentUser === null) {
            $choice = promptChoice("\nWelcome — please choose an option:", [
                '1' => 'Register',
                '2' => 'Login',
                '3' => 'Exit',
            ]);

            match ($choice) {
                '1' => handleRegister($authService),
                '2' => handleLogin($authService),
                '3' => $running = false,
            };
        } else {
            $choice = promptChoice("\nLogged in as {$currentUser->getUsername()} — choose an option:", [
                '1' => 'Book a Ride',
                '2' => 'View Ride History',
                '3' => 'Logout',
                '4' => 'Exit',
            ]);

            match ($choice) {
                '1' => handleBookRide($authService, $rideService),
                '2' => handleViewHistory($authService, $rideService),
                '3' => handleLogout($authService),
                '4' => $running = false,
            };
        }
    } catch (ValidationException|AuthException $e) {
        echo "\nError: {$e->getMessage()}\n";
        $logger->error($e->getMessage());
    } catch (\Throwable $e) {
        // Never leak internal details to the user — log them instead.
        echo "\nSomething went wrong. Please try again.\n";
        $logger->error('Unexpected error: ' . $e->getMessage());
    }
}

echo "\nGoodbye!\n";
