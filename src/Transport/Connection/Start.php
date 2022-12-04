<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Connection;

use Innmind\AMQP\{
    Transport\Connection,
    Transport\Frame\Method,
    Model\Connection\StartOk,
};
use Innmind\Url\Authority;
use Innmind\Immutable\Maybe;

/**
 * @internal
 */
final class Start
{
    private Authority $authority;

    public function __construct(Authority $authority)
    {
        $this->authority = $authority;
    }

    /**
     * @return Maybe<Connection>
     */
    public function __invoke(Connection $connection): Maybe
    {
        // at this point the server could respond with a simple text "AMQP0xyz"
        // where xyz represent the version of the protocol it supports meaning
        // we should restart the opening sequence with this version of the
        // protocol but since this package only support 0.9.1 we can simply
        // stop opening the connection
        $connection->wait(Method::connectionStart);

        return $connection
            ->send(fn($protocol) => $protocol->connection()->startOk(
                StartOk::of(
                    $this->authority->userInformation()->user(),
                    $this->authority->userInformation()->password(),
                ),
            ))
            ->either()
            ->maybe();
    }
}
