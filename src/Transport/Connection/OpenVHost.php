<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Connection;

use Innmind\AMQP\{
    Transport\Connection,
    Transport\Frame\Method,
    Model\Connection\Open,
};
use Innmind\Url\Path;
use Innmind\Immutable\{
    Maybe,
    Predicate\Instance,
};

final class OpenVHost
{
    private Path $vhost;

    public function __construct(Path $vhost)
    {
        $this->vhost = $vhost;
    }

    /**
     * @return Maybe<Connection>
     */
    public function __invoke(Connection $connection): Maybe
    {
        return $connection
            ->send(fn($protocol) => $protocol->connection()->open(
                Open::of($this->vhost),
            ))
            ->wait(Method::connectionOpenOk)
            ->match(
                static fn($connection) => Maybe::just($connection),
                static fn($connection) => Maybe::just($connection),
                static fn() => Maybe::nothing(),
            )
            ->keep(Instance::of(Connection::class));
    }
}
