<?php

declare(strict_types=1);

/**
 * Massive class doing unrelated jobs: fetching, billing, emailing, reporting.
 * Violates SRP, OCP (switch explosion) and ISP/DIP (hard-coded dependencies).
 */
class MegaOrderProcessor
{
    public function process(array $order): void
    {
        $this->log("Starting order for {$order['customer']}");

        if ($order['type'] === 'digital') {
            $this->download($order['sku']);
        } elseif ($order['type'] === 'physical') {
            $this->ship($order['sku'], $order['address']);
        } else {
            $this->invoice($order);
        }

        if ($order['amount'] > 1000) {
            $this->sendVipEmail($order['customer'], $order['amount']);
        } else {
            $this->sendStandardEmail($order['customer']);
        }
    }

    private function download(string $sku): void
    {
        echo "Downloading asset {$sku}\n";
    }

    private function ship(string $sku, string $address): void
    {
        echo "Shipping {$sku} to {$address}\n";
    }

    private function invoice(array $order): void
    {
        echo "Manual invoice for {$order['customer']} ({$order['amount']})\n";
    }

    private function sendVipEmail(string $customer, float $amount): void
    {
        echo "VIP email to {$customer} for {$amount}\n";
    }

    private function sendStandardEmail(string $customer): void
    {
        echo "Standard email to {$customer}\n";
    }

    private function log(string $message): void
    {
        echo "[LOG] {$message}\n";
    }
}

/**
 * LSP violation: Square inherits Rectangle but breaks width/height assumptions.
 */
class Rectangle
{
    protected int $width = 0;
    protected int $height = 0;

    public function setWidth(int $width): void
    {
        $this->width = $width;
    }

    public function setHeight(int $height): void
    {
        $this->height = $height;
    }

    public function area(): int
    {
        return $this->width * $this->height;
    }
}

class Square extends Rectangle
{
    public function setWidth(int $width): void
    {
        parent::setWidth($width);
        parent::setHeight($width);
    }

    public function setHeight(int $height): void
    {
        parent::setWidth($height);
        parent::setHeight($height);
    }
}

/**
 * ISP/DIP violation: Robot forced to implement eat().
 */
interface WorkerContract
{
    public function work(): void;
    public function eat(): void;
}

class HumanWorker implements WorkerContract
{
    public function work(): void
    {
        echo "Human working...\n";
    }

    public function eat(): void
    {
        echo "Human lunch break.\n";
    }
}

class RobotWorker implements WorkerContract
{
    public function work(): void
    {
        echo "Robot assembling parts...\n";
    }

    public function eat(): void
    {
        // Robots do not eat, but contract forces them to.
        echo "Robot pretending to eat oil sandwich.\n";
    }
}

/**
 * High-level module tied to a concrete logger, violating DIP.
 */
class FileLogger
{
    public function write(string $message): void
    {
        echo "[LOG] {$message}\n";
    }
}

class NotificationService
{
    public function __construct(private FileLogger $logger)
    {
    }

    public function notify(string $user, string $message): void
    {
        $this->logger->write("Notify {$user}: {$message}");
        echo "Notification sent to {$user}\n";
    }
}

// Demo script.
$processor = new MegaOrderProcessor();
$processor->process([
    'customer' => 'Alice',
    'sku' => 'SKU-42',
    'type' => 'physical',
    'address' => '42 Rue Imaginaire',
    'amount' => 1500,
]);

$shape = new Square();
$shape->setWidth(5);
$shape->setHeight(10); // inconsistent state vs Rectangle expectation
echo "Square area computed as {$shape->area()}\n";

$notifier = new NotificationService(new FileLogger());
$notifier->notify('Bob', 'Your order shipped');

$workers = [new HumanWorker(), new RobotWorker()];
foreach ($workers as $worker) {
    $worker->work();
    $worker->eat();
}

