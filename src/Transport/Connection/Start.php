<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Connection;

use Innmind\AMQP\{
    Transport\Connection,
    Transport\Frame\Method,
    Model\Connection\StartOk,
};
use Innmind\Url\Authority;
use Innmind\Immutable\Attempt;

/**
 * @internal
 */
final class Start
{
    public function __construct(private Authority $authority)
    {
    }

    /**
     * @return Attempt<Connection>
     */
    public function __invoke(Connection $connection): Attempt
    {
        // at this point the server could respond with a simple text "AMQP0xyz"
        // where xyz represent the version of the protocol it supports meaning
        // we should restart the opening sequence with this version of the
        // protocol but since this package only support 0.9.1 we can simply
        // stop opening the connection
        return $connection
            ->respondTo(
                Method::connectionStart,
                fn($protocol) => $protocol->connection()->startOk(
                    StartOk::of(
                        $this->authority->userInformation()->user(),
                        $this->authority->userInformation()->password(),
                    ),
                ),
            )
            ->map(static fn() => $connection);
    }
}
