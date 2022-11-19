<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Connection;

use Innmind\AMQP\{
    Transport\Connection,
    Transport\Frame\Method,
    Model\Connection\Open,
};
use Innmind\Url\Path;

final class OpenVHost
{
    private Path $vhost;

    public function __construct(Path $vhost)
    {
        $this->vhost = $vhost;
    }

    public function __invoke(Connection $connection): Connection
    {
        return $connection
            ->send(fn($protocol) => $protocol->connection()->open(
                Open::of($this->vhost),
            ))
            ->wait(Method::connectionOpenOk)
            ->match(
                static fn($connection) => $connection,
                static fn($connection) => $connection,
                static fn() => throw new \RuntimeException,
            );
    }
}
