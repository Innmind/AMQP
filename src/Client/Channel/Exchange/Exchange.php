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
        $_ = $this
            ->connection
            ->send(fn($protocol) => $protocol->exchange()->declare(
                $this->channel,
                $command,
            ))
            ->match(
                static fn() => null,
                static fn() => throw new \RuntimeException,
            );

        if ($command->shouldWait()) {
            $this->connection->wait(Method::exchangeDeclareOk);
        }
    }

    public function delete(Deletion $command): void
    {
        $_ = $this
            ->connection
            ->send(fn($protocol) => $protocol->exchange()->delete(
                $this->channel,
                $command,
            ))
            ->match(
                static fn() => null,
                static fn() => throw new \RuntimeException,
            );

        if ($command->shouldWait()) {
            $this->connection->wait(Method::exchangeDeleteOk);
        }
    }
}
