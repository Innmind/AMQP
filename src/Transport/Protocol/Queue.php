<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Protocol;

use Innmind\AMQP\{
    Model\Queue\Declaration,
    Model\Queue\Deletion,
    Model\Queue\Binding,
    Model\Queue\Unbinding,
    Model\Queue\Purge,
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

final class Queue
{
    private ArgumentTranslator $translate;

    public function __construct(ArgumentTranslator $translator)
    {
        $this->translate = $translator;
    }

    public function declare(FrameChannel $channel, Declaration $command): Frame
    {
        $name = $command->name()->match(
            static fn($name) => $name,
            static fn() => '',
        );

        return Frame::method(
            $channel,
            Method::queueDeclare,
            UnsignedShortInteger::of(0), // ticket (reserved)
            ShortString::of(Str::of($name)),
            Bits::of(
                $command->isPassive(),
                $command->isDurable(),
                $command->isExclusive(),
                $command->isAutoDeleted(),
                !$command->shouldWait(),
            ),
            $this->translate($command->arguments()),
        );
    }

    public function delete(FrameChannel $channel, Deletion $command): Frame
    {
        return Frame::method(
            $channel,
            Method::queueDelete,
            UnsignedShortInteger::of(0), // ticket (reserved)
            ShortString::of(Str::of($command->name())),
            Bits::of(
                $command->onlyIfUnused(),
                $command->onlyIfEmpty(),
                !$command->shouldWait(),
            ),
        );
    }

    public function bind(FrameChannel $channel, Binding $command): Frame
    {
        return Frame::method(
            $channel,
            Method::queueBind,
            UnsignedShortInteger::of(0), // ticket (reserved)
            ShortString::of(Str::of($command->queue())),
            ShortString::of(Str::of($command->exchange())),
            ShortString::of(Str::of($command->routingKey())),
            Bits::of(!$command->shouldWait()),
            $this->translate($command->arguments()),
        );
    }

    public function unbind(FrameChannel $channel, Unbinding $command): Frame
    {
        return Frame::method(
            $channel,
            Method::queueUnbind,
            UnsignedShortInteger::of(0), // ticket (reserved)
            ShortString::of(Str::of($command->queue())),
            ShortString::of(Str::of($command->exchange())),
            ShortString::of(Str::of($command->routingKey())),
            $this->translate($command->arguments()),
        );
    }

    public function purge(FrameChannel $channel, Purge $command): Frame
    {
        return Frame::method(
            $channel,
            Method::queuePurge,
            UnsignedShortInteger::of(0), // ticket (reserved)
            ShortString::of(Str::of($command->name())),
            Bits::of(!$command->shouldWait()),
        );
    }

    /**
     * @param Map<string, mixed> $arguments
     */
    private function translate(Map $arguments): Table
    {
        $table = $arguments->map(
            fn($_, $value) => ($this->translate)($value),
        );

        return Table::of($table);
    }
}
