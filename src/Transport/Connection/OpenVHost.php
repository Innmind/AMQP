<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Connection;

use Innmind\AMQP\{
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
        $connection->send($connection->protocol()->connection()->open(
            Open::of($this->vhost),
        ));
        $connection->wait(Method::connectionOpenOk);

        return $connection;
    }
}
