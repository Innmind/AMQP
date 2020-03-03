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
                $command
            )
        );

        if (!$command->shouldWait()) {
            return null;
        }

        $frame = $this->connection->wait('queue.declare-ok');

        return new DeclareOk(
            $frame->values()->get(0)->original()->toString(),
            new Count($frame->values()->get(1)->original()->value()),
            new Count($frame->values()->get(2)->original()->value())
        );
    }

    public function delete(Deletion $command): ?DeleteOk
    {
        $this->connection->send(
            $this->connection->protocol()->queue()->delete(
                $this->channel,
                $command
            )
        );

        if (!$command->shouldWait()) {
            return null;
        }

        $frame = $this->connection->wait('queue.delete-ok');

        return new DeleteOk(new Count(
            $frame->values()->first()->original()->value()
        ));
    }

    public function bind(Binding $command): QueueInterface
    {
        $this->connection->send(
            $this->connection->protocol()->queue()->bind(
                $this->channel,
                $command
            )
        );

        if ($command->shouldWait()) {
            $this->connection->wait('queue.bind-ok');
        }

        return $this;
    }

    public function unbind(Unbinding $command): QueueInterface
    {
        $this
            ->connection
            ->send($this->connection->protocol()->queue()->unbind(
                $this->channel,
                $command
            ))
            ->wait('queue.unbind-ok');

        return $this;
    }

    public function purge(Purge $command): ?PurgeOk
    {
        $this->connection->send(
            $this->connection->protocol()->queue()->purge(
                $this->channel,
                $command
            )
        );

        if (!$command->shouldWait()) {
            return null;
        }

        $frame = $this->connection->wait('queue.purge-ok');

        return new PurgeOk(new Count(
            $frame->values()->first()->original()->value()
        ));
    }
}
