<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Protocol\v091;

use Innmind\AMQP\{
    Model\Queue\Declaration,
    Model\Queue\Deletion,
    Model\Queue\Binding,
    Model\Queue\Unbinding,
    Model\Queue\Purge,
    Transport\Frame,
    Transport\Frame\Channel as FrameChannel,
    Transport\Frame\Type,
    Transport\Frame\Value,
    Transport\Frame\Value\UnsignedShortInteger,
    Transport\Frame\Value\ShortString,
    Transport\Frame\Value\Bits,
    Transport\Frame\Value\Table,
    Transport\Protocol\Queue as QueueInterface
};
use Innmind\Math\Algebra\Integer;
use Innmind\Immutable\{
    Str,
    Map
};

final class Queue implements QueueInterface
{
    public function declare(FrameChannel $channel, Declaration $command): Frame
    {
        $name = '';

        if (!$command->shouldAutoGenerateName()) {
            $name = $command->name();
        }

        return new Frame(
            Type::method(),
            $channel,
            Methods::get('queue.declare'),
            new UnsignedShortInteger(new Integer(0)), //ticket (reserved)
            new ShortString(new Str($name)),
            new Bits($command->isPassive()),
            new Bits($command->isDurable()),
            new Bits($command->isExclusive()),
            new Bits($command->isAutoDeleted()),
            new Bits(!$command->shouldWait()),
            new Table(new Map('string', Value::class)) //todo: use $command->arguments()
        );
    }

    public function delete(FrameChannel $channel, Deletion $command): Frame
    {
        return new Frame(
            Type::method(),
            $channel,
            Methods::get('queue.delete'),
            new UnsignedShortInteger(new Integer(0)), //ticket (reserved)
            new ShortString(new Str($command->name())),
            new Bits($command->onlyIfUnused()),
            new Bits($command->onlyIfEmpty()),
            new Bits(!$command->shouldWait())
        );
    }

    public function bind(FrameChannel $channel, Binding $command): Frame
    {
        return new Frame(
            Type::method(),
            $channel,
            Methods::get('queue.bind'),
            new UnsignedShortInteger(new Integer(0)), //ticket (reserved)
            new ShortString(new Str($command->queue())),
            new ShortString(new Str($command->exchange())),
            new ShortString(new Str($command->routingKey())),
            new Bits(!$command->shouldWait()),
            new Table(new Map('string', Value::class)) //todo: use $command->arguments()
        );
    }

    public function unbind(FrameChannel $channel, Unbinding $command): Frame
    {
        return new Frame(
            Type::method(),
            $channel,
            Methods::get('queue.unbind'),
            new UnsignedShortInteger(new Integer(0)), //ticket (reserved)
            new ShortString(new Str($command->queue())),
            new ShortString(new Str($command->exchange())),
            new ShortString(new Str($command->routingKey())),
            new Table(new Map('string', Value::class)) //todo: use $command->arguments()
        );
    }

    public function purge(FrameChannel $channel, Purge $command): Frame
    {
        return new Frame(
            Type::method(),
            $channel,
            Methods::get('queue.purge'),
            new UnsignedShortInteger(new Integer(0)), //ticket (reserved)
            new ShortString(new Str($command->name())),
            new Bits(!$command->shouldWait())
        );
    }
}
