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
};

final class NullQueue implements QueueInterface
{
    /**
     * {@inheritdoc}
     */
    public function declare(Declaration $command): ?DeclareOk
    {
        return null;
    }

    public function delete(Deletion $command): ?DeleteOk
    {
        return null;
    }

    public function bind(Binding $command): void
    {
        // pass
    }

    public function unbind(Unbinding $command): void
    {
        // pass
    }

    public function purge(Purge $command): ?PurgeOk
    {
        return null;
    }
}
