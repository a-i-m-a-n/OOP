<?php

declare(strict_types=1);

namespace RideFareSystem;

class Logger
{
    public const INFO = 'INFO';
    public const WARNING = 'WARNING';
    public const ERROR = 'ERROR';

    private string $logFilePath;

    public function __construct(string $logFilePath)
    {
        $this->logFilePath = $logFilePath;

        $dir = dirname($logFilePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
    }

    public function info(string $message, array $context = []): void
    {
        $this->write(self::INFO, $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->write(self::WARNING, $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->write(self::ERROR, $message, $context);
    }

    private function write(string $level, string $message, array $context): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $line = "[{$level}] {$timestamp} - {$message}";

        if (!empty($context)) {
            $parts = [];
            foreach ($context as $key => $value) {
                $parts[] = "{$key}: {$value}";
            }
            $line .= ' | ' . implode(', ', $parts);
        }

        $line .= PHP_EOL;

        file_put_contents($this->logFilePath, $line, FILE_APPEND | LOCK_EX);
    }
}
