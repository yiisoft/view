<?php

declare(strict_types=1);

namespace Yiisoft\View\Tests\Stubs;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;

final class LoggerStub implements LoggerInterface
{
    use LoggerTrait;

    private array $logs = [];

    public function log($level, $message, array $context = [])
    {
        $this->logs[$level][] = $message;
    }

    public function has($level, $message): bool
    {
        return array_key_exists($level, $this->logs) && in_array($message, $this->logs[$level]);
    }

    public function getAll(): array
    {
        return $this->logs;
    }
}
