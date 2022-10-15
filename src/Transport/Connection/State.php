<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Connection;

use Innmind\Socket\Client;

enum State
{
    case opening;
    case opened;
    case closed;

    public function usable(Client $socket): bool
    {
        return match ($this) {
            self::opened => !$socket->closed(),
            default => false,
        };
    }

    public function listenable(Client $socket): bool
    {
        return match ($this) {
            self::opened, self::opening => !$socket->closed(),
            self::closed => false,
        };
    }

    public function openable(Client $socket): bool
    {
        return match ($this) {
            self::opening => !$socket->closed(),
            self::opened => false,
            self::closed => false,
        };
    }

    public function closed(Client $socket): bool
    {
        return match ($this) {
            self::opening => true,
            self::opened => $socket->closed(),
            self::closed => true,
        };
    }
}
