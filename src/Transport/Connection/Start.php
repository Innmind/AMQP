<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Connection;

use Innmind\AMQP\{
    Transport\Frame\Method,
    Model\Connection\StartOk,
};
use Innmind\Url\Authority;

final class Start
{
    private Authority $authority;

    public function __construct(Authority $authority)
    {
        $this->authority = $authority;
    }

    public function __invoke(Connection $connection): Connection
    {
        // at this point the server could respond with a simple text "AMQP0xyz"
        // where xyz represent the version of the protocol it supports meaning
        // we should restart the opening sequence with this version of the
        // protocol but since this package only support 0.9.1 we can simply
        // stop opening the connection
        $connection->wait(Method::connectionStart);
        $_ = $connection
            ->send($connection->protocol()->connection()->startOk(
                StartOk::of(
                    $this->authority->userInformation()->user(),
                    $this->authority->userInformation()->password(),
                ),
            ))
            ->match(
                static fn() => null,
                static fn() => throw new \RuntimeException,
            );

        return $connection;
    }
}
