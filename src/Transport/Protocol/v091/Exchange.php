<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Protocol\v091;

use Innmind\AMQP\{
    Model\Exchange\Declaration,
    Model\Exchange\Deletion,
    Transport\Frame,
    Transport\Frame\Channel as FrameChannel,
    Transport\Frame\Type,
    Transport\Frame\Value,
    Transport\Frame\Value\UnsignedShortInteger,
    Transport\Frame\Value\ShortString,
    Transport\Frame\Value\Bits,
    Transport\Frame\Value\Table,
    Transport\Protocol\Exchange as ExchangeInterface
};
use Innmind\Math\Algebra\Integer;
use Innmind\Immutable\{
    Str,
    Map
};

final class Exchange implements ExchangeInterface
{
    public function declare(FrameChannel $channel, Declaration $command): Frame
    {
        return new Frame(
            Type::method(),
            $channel,
            Methods::get('exchange.declare'),
            new UnsignedShortInteger(new Integer(0)), //ticket (reserved)
            new ShortString(new Str($command->name())),
            new ShortString(new Str((string) $command->type())),
            new Bits($command->isPassive()),
            new Bits($command->isDurable()),
            new Bits($command->isAutoDeleted()), //reserved
            new Bits(false), //internal (reserved)
            new Bits(!$command->shouldWait()),
            new Table(new Map('string', Value::class)) //todo: use $command->arguments()
        );
    }

    public function delete(FrameChannel $channel, Deletion $command): Frame
    {
        return new Frame(
            Type::method(),
            $channel,
            Methods::get('exchange.delete'),
            new UnsignedShortInteger(new Integer(0)), //ticket (reserved)
            new ShortString(new Str($command->name())),
            new Bits($command->onlyIfUnused()),
            new Bits(!$command->shouldWait())
        );
    }
}
