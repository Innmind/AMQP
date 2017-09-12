<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Client\Channel\Exchange;

use Innmind\AMQP\{
    Client\Channel\Exchange as ExchangeInterface,
    Model\Exchange\Declaration,
    Model\Exchange\Deletion,
    Transport\Connection,
    Transport\Frame\Channel
};

final class Exchange implements ExchangeInterface
{
    private $connection;
    private $channel;

    public function __construct(Connection $connection, Channel $channel)
    {
        $this->connection = $connection;
        $this->channel = $channel;
    }

    public function declare(Declaration $command): ExchangeInterface
    {
        $this->connection->send(
            $this->connection->protocol()->exchange()->declare(
                $this->channel,
                $command
            )
        );

        if ($command->shouldWait()) {
            $this->connection->wait('exchange.declare-ok');
        }

        return $this;
    }

    public function delete(Deletion $command): ExchangeInterface
    {
        $this->connection->send(
            $this->connection->protocol()->exchange()->delete(
                $this->channel,
                $command
            )
        );

        if ($command->shouldWait()) {
            $this->connection->wait('exchange.delete-ok');
        }

        return $this;
    }
}
