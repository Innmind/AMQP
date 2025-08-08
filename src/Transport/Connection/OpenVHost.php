<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Connection;

use Innmind\AMQP\{
    Transport\Connection,
    Transport\Frame\Method,
    Model\Connection\Open,
};
use Innmind\Url\Path;
use Innmind\Immutable\Attempt;

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
     * @return Attempt<Connection>
     */
    public function __invoke(Connection $connection): Attempt
    {
        return $connection
            ->request(
                fn($protocol) => $protocol->connection()->open(
                    Open::of($this->vhost),
                ),
                Method::connectionOpenOk,
            )
            ->attempt(static fn($failure) => $failure)
            ->map(static fn() => $connection);
    }
}
