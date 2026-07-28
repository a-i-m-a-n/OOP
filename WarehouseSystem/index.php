<?php

declare(strict_types=1);

spl_autoload_register(function (string $class): void {
    $prefix = 'WarehouseSystem\\';
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

use WarehouseSystem\Authentication\AuthService;
use WarehouseSystem\Authentication\PasswordHasher;
use WarehouseSystem\Bundle;
use WarehouseSystem\Customer;
use WarehouseSystem\Exceptions\WarehouseException;
use WarehouseSystem\Factory\ProductFactory;
use WarehouseSystem\InventoryManager;
use WarehouseSystem\Logger;
use WarehouseSystem\Order;
use WarehouseSystem\OrderItem;
use WarehouseSystem\OrderValidator;
use WarehouseSystem\Product;
use WarehouseSystem\Repository\BundleRepository;
use WarehouseSystem\Repository\OrderRepository;
use WarehouseSystem\Repository\ProductRepository;
use WarehouseSystem\Repository\UserRepository;
use WarehouseSystem\Services\OrderService;
use WarehouseSystem\User;

$dataDir = __DIR__ . '/data';
$logger = new Logger(__DIR__ . '/logs/app.log');

$userRepository = new UserRepository($dataDir . '/users.json');
$productRepository = new ProductRepository($dataDir . '/products.json');
$bundleRepository = new BundleRepository($dataDir . '/bundles.json');
$orderRepository = new OrderRepository($dataDir . '/orders.json');

$authService = new AuthService($userRepository, new PasswordHasher(), $logger);
$inventoryManager = new InventoryManager($productRepository, $bundleRepository, $logger);
$orderValidator = new OrderValidator($productRepository, $bundleRepository);
$orderService = new OrderService(
    $orderRepository,
    $productRepository,
    $bundleRepository,
    $inventoryManager,
    $orderValidator,
    $logger
);

function prompt(string $label): string
{
    echo $label;
    $line = fgets(STDIN);
    return $line === false ? '' : trim($line);
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

function promptNonNegativeInt(string $label): int
{
    while (true) {
        $value = prompt($label);
        if (ctype_digit($value)) {
            return (int) $value;
        }
        echo "  Please enter a whole number (0 or more).\n";
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
    echo "\n" . str_repeat('=', 55) . "\n";
    echo $text . "\n";
    echo str_repeat('=', 55) . "\n";
}

function handleRegister(AuthService $authService): void
{
    printBanner('STAFF REGISTRATION');
    $username = prompt('Choose a username: ');
    $password = prompt('Choose a password: ');

    $authService->register($username, $password);
    echo "Registration successful! You can now log in.\n";
}

function handleLogin(AuthService $authService): User
{
    printBanner('STAFF LOGIN');
    $username = prompt('Username: ');
    $password = prompt('Password: ');

    $user = $authService->login($username, $password);
    echo "Welcome back, {$user->getUsername()}!\n";

    return $user;
}

function formatStock(Product $product): string
{
    $available = $product->getAvailableStock() === PHP_INT_MAX ? 'Unlimited' : (string) $product->getAvailableStock();
    return sprintf(
        '%-8s %-28s %-16s Total:%-5s Reserved:%-5s Sold:%-5s Available:%s',
        $product->getSku(),
        $product->getName(),
        $product->getProductType(),
        $product->getTotalStock(),
        $product->getReservedStock(),
        $product->getSoldStock(),
        $available
    );
}

function handleViewCatalog(ProductRepository $productRepository): void
{
    printBanner('PRODUCT CATALOG');

    $products = $productRepository->all();
    if (empty($products)) {
        echo "No products in catalog yet.\n";
        return;
    }

    foreach ($products as $product) {
        echo formatStock($product) . "\n";
    }
}

function handleViewBundles(BundleRepository $bundleRepository, ProductRepository $productRepository): void
{
    printBanner('BUNDLES');

    $bundles = $bundleRepository->all();
    if (empty($bundles)) {
        echo "No bundles defined yet.\n";
        return;
    }

    foreach ($bundles as $bundle) {
        echo "{$bundle->getSku()} — {$bundle->getName()}\n";
        foreach ($bundle->getItems() as $bundleItem) {
            $product = $productRepository->findBySku($bundleItem->getSku());
            $label = $product !== null ? $product->getName() : $bundleItem->getSku();
            echo "    {$bundleItem->getQuantity()} x {$label} ({$bundleItem->getSku()})\n";
        }
    }
}

function handleAddProduct(ProductRepository $productRepository, Logger $logger): void
{
    printBanner('ADD PRODUCT');

    $typeChoice = promptChoice('Select product type:', [
        '1' => 'Physical',
        '2' => 'Digital',
        '3' => 'Limited Edition',
    ]);
    $typeMap = [
        '1' => ProductFactory::TYPE_PHYSICAL,
        '2' => ProductFactory::TYPE_DIGITAL,
        '3' => ProductFactory::TYPE_LIMITED_EDITION,
    ];
    $type = $typeMap[$typeChoice];

    $sku = strtoupper(trim(prompt('SKU: ')));
    if ($sku === '') {
        echo "SKU cannot be empty.\n";
        return;
    }
    if ($productRepository->exists($sku)) {
        echo "A product with SKU '{$sku}' already exists.\n";
        return;
    }

    $name = prompt('Name: ');
    $totalStock = $type === ProductFactory::TYPE_DIGITAL
        ? 0
        : promptNonNegativeInt('Total stock: ');

    $product = ProductFactory::create($type, $sku, $name, $totalStock);
    $productRepository->save($product);

    $logger->info('Product Created', ['Sku' => $sku, 'Name' => $name, 'Type' => $type]);
    echo "Product '{$name}' ({$sku}) added.\n";
}

/**
 * @return array<int, array{type: string, sku: string, quantity: int}>
 */
function collectOrderItems(ProductRepository $productRepository, BundleRepository $bundleRepository): array
{
    $items = [];

    while (true) {
        $typeChoice = promptChoice('Add to order:', [
            '1' => 'Product',
            '2' => 'Bundle',
            '3' => 'Done adding items',
        ]);

        if ($typeChoice === '3') {
            break;
        }

        $itemType = $typeChoice === '1' ? OrderItem::TYPE_PRODUCT : OrderItem::TYPE_BUNDLE;
        $sku = strtoupper(trim(prompt('SKU: ')));
        $quantity = promptInt('Quantity: ');

        $items[] = ['type' => $itemType, 'sku' => $sku, 'quantity' => $quantity];
        echo "  Added {$quantity} x {$sku}.\n";
    }

    return $items;
}

function handleCreateOrder(
    OrderService $orderService,
    ProductRepository $productRepository,
    BundleRepository $bundleRepository
): void {
    printBanner('CREATE ORDER');

    $customerName = prompt('Customer name: ');
    if (trim($customerName) === '') {
        echo "Customer name cannot be empty.\n";
        return;
    }

    $itemSpecs = collectOrderItems($productRepository, $bundleRepository);

    $order = $orderService->createOrder(new Customer($customerName), $itemSpecs);

    echo "\nOrder #{$order->getOrderId()} created for {$order->getCustomerName()} — status: {$order->getStatus()}\n";
    foreach ($order->getItems() as $item) {
        echo "    {$item->getQuantity()} x {$item->getName()} ({$item->getSku()}, {$item->getItemType()})\n";
    }
}

function handleViewAllOrders(OrderService $orderService): void
{
    printBanner('ALL ORDERS');

    $orders = $orderService->allOrders();
    if (empty($orders)) {
        echo "No orders yet.\n";
        return;
    }

    foreach ($orders as $order) {
        echo "#{$order->getOrderId()} — {$order->getCustomerName()} — {$order->getStatus()} ({$order->getCreatedAt()})\n";
        foreach ($order->getItems() as $item) {
            echo "    {$item->getQuantity()} x {$item->getName()} ({$item->getSku()}, {$item->getItemType()})\n";
        }
    }
}

function handleShipOrder(OrderService $orderService): void
{
    printBanner('SHIP ORDER');
    $orderId = promptInt('Order ID to ship: ');

    $order = $orderService->shipOrder($orderId);
    echo "Order #{$order->getOrderId()} is now {$order->getStatus()}.\n";
}

function handleCancelOrder(OrderService $orderService): void
{
    printBanner('CANCEL ORDER');
    $orderId = promptInt('Order ID to cancel: ');

    $order = $orderService->cancelOrder($orderId);
    echo "Order #{$order->getOrderId()} is now {$order->getStatus()}.\n";
}

function handleLogout(AuthService $authService, User $currentUser): void
{
    $authService->logout($currentUser);
    echo "You have been logged out.\n";
}

echo "-------------------------------------------------------\n";
echo "   WAREHOUSE INVENTORY & ORDER MANAGEMENT SYSTEM \n";
echo "-------------------------------------------------------\n";

$currentUser = null;
$running = true;

while ($running) {
    try {
        if ($currentUser === null) {
            $choice = promptChoice("\nWelcome — please choose an option:", [
                '1' => 'Register',
                '2' => 'Login',
                '3' => 'Exit',
            ]);

            match ($choice) {
                '1' => handleRegister($authService),
                '2' => $currentUser = handleLogin($authService),
                '3' => $running = false,
            };
        } else {
            $choice = promptChoice("\nLogged in as {$currentUser->getUsername()} — choose an option:", [
                '1' => 'View Product Catalog',
                '2' => 'View Bundles',
                '3' => 'Add New Product',
                '4' => 'Create Order',
                '5' => 'View All Orders',
                '6' => 'Ship an Order',
                '7' => 'Cancel an Order',
                '8' => 'Logout',
                '9' => 'Exit',
            ]);

            match ($choice) {
                '1' => handleViewCatalog($productRepository),
                '2' => handleViewBundles($bundleRepository, $productRepository),
                '3' => handleAddProduct($productRepository, $logger),
                '4' => handleCreateOrder($orderService, $productRepository, $bundleRepository),
                '5' => handleViewAllOrders($orderService),
                '6' => handleShipOrder($orderService),
                '7' => handleCancelOrder($orderService),
                '8' => handleLogout($authService, $currentUser),
                '9' => $running = false,
            };

            if ($choice === '8') {
                $currentUser = null;
            }
        }
    } catch (WarehouseException $e) {
        echo "\nError: {$e->getMessage()}\n";
        $logger->error($e->getMessage());
    } catch (\RuntimeException $e) {
        echo "\nError: {$e->getMessage()}\n";
        $logger->error($e->getMessage());
    } catch (\Throwable $e) {
        echo "\nSomething went wrong. Please try again.\n";
        $logger->error('Unexpected error: ' . $e->getMessage());
    }
}

echo "\nGoodbye!\n";
