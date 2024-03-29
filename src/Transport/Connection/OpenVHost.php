<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Connection;

use Innmind\AMQP\{
    Transport\Connection,
    Transport\Frame\Method,
    Model\Connection\Open,
};
use Innmind\Url\Path;
use Innmind\Immutable\Maybe;

/**
 * @internal
 */
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
            ->request(
                fn($protocol) => $protocol->connection()->open(
                    Open::of($this->vhost),
                ),
                Method::connectionOpenOk,
            )
            ->maybe()
            ->map(static fn() => $connection);
    }
}
