<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Client\Channel\Exchange;

use Innmind\AMQP\{
    Client\Channel\Exchange as ExchangeInterface,
    Model\Exchange\Declaration,
    Model\Exchange\Deletion,
    Transport\Connection,
    Transport\Frame\Channel,
    Transport\Frame\Method,
};

final class Exchange implements ExchangeInterface
{
    private Connection $connection;
    private Channel $channel;

    public function __construct(Connection $connection, Channel $channel)
    {
        $this->connection = $connection;
        $this->channel = $channel;
    }

    public function declare(Declaration $command): void
    {
        $this->connection->send(
            $this->connection->protocol()->exchange()->declare(
                $this->channel,
                $command,
            ),
        );

        if ($command->shouldWait()) {
            $this->connection->wait(Method::exchangeDeclareOk);
        }
    }

    public function delete(Deletion $command): void
    {
        $this->connection->send(
            $this->connection->protocol()->exchange()->delete(
                $this->channel,
                $command,
            ),
        );

        if ($command->shouldWait()) {
            $this->connection->wait(Method::exchangeDeleteOk);
        }
    }
}
