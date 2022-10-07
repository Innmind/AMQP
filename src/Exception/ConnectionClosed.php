<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Exception;

final class ConnectionClosed extends RuntimeException
{
    public static function byServer(string $message, int $code): self
    {
        return new self($message, $code);
    }
}
