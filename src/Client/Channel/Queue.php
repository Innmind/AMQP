<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Client\Channel;

use Innmind\AMQP\Model\Queue\{
    Declaration,
    DeclareOk,
    Deletion,
    DeleteOk,
    Binding,
    Unbinding,
    Purge,
    PurgeOk,
};

interface Queue
{
    /**
     * @return DeclareOk|null null if not waiting for response
     */
    public function declare(Declaration $command): ?DeclareOk;
    public function delete(Deletion $command): ?DeleteOk;
    public function bind(Binding $command): void;
    public function unbind(Unbinding $command): void;
    public function purge(Purge $command): ?PurgeOk;
}
