<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Protocol;

use Innmind\AMQP\{
    Model\Exchange\Declaration,
    Model\Exchange\Deletion,
    Transport\Frame,
    Transport\Frame\Channel as FrameChannel,
    Transport\Frame\Type,
    Transport\Frame\Method,
    Transport\Frame\Value,
    Transport\Frame\Value\UnsignedShortInteger,
    Transport\Frame\Value\ShortString,
    Transport\Frame\Value\Bits,
    Transport\Frame\Value\Table,
};
use Innmind\Immutable\{
    Str,
    Map,
};

final class Exchange
{
    private ArgumentTranslator $translate;

    public function __construct(ArgumentTranslator $translator)
    {
        $this->translate = $translator;
    }

    public function declare(FrameChannel $channel, Declaration $command): Frame
    {
        $arguments = $command->arguments()->map(
            fn($_, $value) => ($this->translate)($value),
        );

        return Frame::method(
            $channel,
            Method::exchangeDeclare,
            UnsignedShortInteger::internal(0), // ticket (reserved)
            ShortString::of(Str::of($command->name())),
            ShortString::of(Str::of($command->type()->toString())),
            Bits::of(
                $command->isPassive(),
                $command->isDurable(),
                $command->isAutoDeleted(), // reserved
                false, // internal (reserved)
                !$command->shouldWait(),
            ),
            Table::of($arguments),
        );
    }

    public function delete(FrameChannel $channel, Deletion $command): Frame
    {
        return Frame::method(
            $channel,
            Method::exchangeDelete,
            UnsignedShortInteger::internal(0), // ticket (reserved)
            ShortString::of(Str::of($command->name())),
            Bits::of(
                $command->onlyIfUnused(),
                !$command->shouldWait(),
            ),
        );
    }
}
