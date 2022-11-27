<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Client;

use Innmind\AMQP\Transport\Connection;

final class State
{
    private Connection $connection;
    private mixed $userState;

    private function __construct(Connection $connection, mixed $userState)
    {
        $this->connection = $connection;
        $this->userState = $userState;
    }

    public static function of(Connection $connection, mixed $userState): self
    {
        return new self($connection, $userState);
    }

    public function connection(): Connection
    {
        return $this->connection;
    }

    public function userState(): mixed
    {
        return $this->userState;
    }
}
