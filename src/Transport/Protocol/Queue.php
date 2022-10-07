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
use Innmind\Math\Algebra\Integer;
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
            new UnsignedShortInteger(Integer::of(0)), // ticket (reserved)
            ShortString::of(Str::of($name)),
            new Bits(
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
            new UnsignedShortInteger(Integer::of(0)), // ticket (reserved)
            ShortString::of(Str::of($command->name())),
            new Bits(
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
            new UnsignedShortInteger(Integer::of(0)), // ticket (reserved)
            ShortString::of(Str::of($command->queue())),
            ShortString::of(Str::of($command->exchange())),
            ShortString::of(Str::of($command->routingKey())),
            new Bits(!$command->shouldWait()),
            $this->translate($command->arguments()),
        );
    }

    public function unbind(FrameChannel $channel, Unbinding $command): Frame
    {
        return Frame::method(
            $channel,
            Method::queueUnbind,
            new UnsignedShortInteger(Integer::of(0)), // ticket (reserved)
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
            new UnsignedShortInteger(Integer::of(0)), // ticket (reserved)
            ShortString::of(Str::of($command->name())),
            new Bits(!$command->shouldWait()),
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

        return new Table($table);
    }
}
