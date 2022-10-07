<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Client\Channel\Queue;

use Innmind\AMQP\{
    Client\Channel\Queue as QueueInterface,
    Model\Queue\Declaration,
    Model\Queue\DeclareOk,
    Model\Queue\Deletion,
    Model\Queue\DeleteOk,
    Model\Queue\Binding,
    Model\Queue\Unbinding,
    Model\Queue\Purge,
    Model\Queue\PurgeOk,
    Model\Count,
    Transport\Connection,
    Transport\Frame\Channel,
    Transport\Frame\Value,
    Transport\Frame\Method,
};

final class Queue implements QueueInterface
{
    private Connection $connection;
    private Channel $channel;

    public function __construct(Connection $connection, Channel $channel)
    {
        $this->connection = $connection;
        $this->channel = $channel;
    }

    public function declare(Declaration $command): ?DeclareOk
    {
        $this->connection->send(
            $this->connection->protocol()->queue()->declare(
                $this->channel,
                $command,
            ),
        );

        if (!$command->shouldWait()) {
            return null;
        }

        $frame = $this->connection->wait(Method::queueDeclareOk);
        /** @var Value\ShortString */
        $name = $frame->values()->get(0)->match(
            static fn($value) => $value,
            static fn() => throw new \LogicException,
        );
        /** @var Value\UnsignedLongInteger */
        $message = $frame->values()->get(1)->match(
            static fn($value) => $value,
            static fn() => throw new \LogicException,
        );
        /** @var Value\UnsignedLongInteger */
        $consumer = $frame->values()->get(2)->match(
            static fn($value) => $value,
            static fn() => throw new \LogicException,
        );

        return new DeclareOk(
            $name->original()->toString(),
            new Count($message->original()->value()),
            new Count($consumer->original()->value()),
        );
    }

    public function delete(Deletion $command): ?DeleteOk
    {
        $this->connection->send(
            $this->connection->protocol()->queue()->delete(
                $this->channel,
                $command,
            ),
        );

        if (!$command->shouldWait()) {
            return null;
        }

        $frame = $this->connection->wait(Method::queueDeleteOk);
        /** @var Value\UnsignedLongInteger */
        $message = $frame->values()->first()->match(
            static fn($value) => $value,
            static fn() => throw new \LogicException,
        );

        return new DeleteOk(new Count(
            $message->original()->value(),
        ));
    }

    public function bind(Binding $command): void
    {
        $this->connection->send(
            $this->connection->protocol()->queue()->bind(
                $this->channel,
                $command,
            ),
        );

        if ($command->shouldWait()) {
            $this->connection->wait(Method::queueBindOk);
        }
    }

    public function unbind(Unbinding $command): void
    {
        $this->connection->send($this->connection->protocol()->queue()->unbind(
            $this->channel,
            $command,
        ));
        $this->connection->wait(Method::queueUnbindOk);
    }

    public function purge(Purge $command): ?PurgeOk
    {
        $this->connection->send(
            $this->connection->protocol()->queue()->purge(
                $this->channel,
                $command,
            ),
        );

        if (!$command->shouldWait()) {
            return null;
        }

        $frame = $this->connection->wait(Method::queuePurgeOk);
        /** @var Value\UnsignedLongInteger */
        $message = $frame->values()->first()->match(
            static fn($value) => $value,
            static fn() => throw new \LogicException,
        );

        return new PurgeOk(new Count(
            $message->original()->value(),
        ));
    }
}
