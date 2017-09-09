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
    Transport\Protocol\Queue as QueueInterface,
    Transport\Protocol\ArgumentTranslator
};
use Innmind\Math\Algebra\Integer;
use Innmind\Immutable\{
    Str,
    Map,
    MapInterface
};

final class Queue implements QueueInterface
{
    private $translate;

    public function __construct(ArgumentTranslator $translator)
    {
        $this->translate = $translator;
    }

    public function declare(FrameChannel $channel, Declaration $command): Frame
    {
        $name = '';

        if (!$command->shouldAutoGenerateName()) {
            $name = $command->name();
        }

        return Frame::command(
            $channel,
            Methods::get('queue.declare'),
            new UnsignedShortInteger(new Integer(0)), //ticket (reserved)
            new ShortString(new Str($name)),
            new Bits(
                $command->isPassive(),
                $command->isDurable(),
                $command->isExclusive(),
                $command->isAutoDeleted(),
                !$command->shouldWait()
            ),
            $this->translate($command->arguments())
        );
    }

    public function delete(FrameChannel $channel, Deletion $command): Frame
    {
        return Frame::command(
            $channel,
            Methods::get('queue.delete'),
            new UnsignedShortInteger(new Integer(0)), //ticket (reserved)
            new ShortString(new Str($command->name())),
            new Bits(
                $command->onlyIfUnused(),
                $command->onlyIfEmpty(),
                !$command->shouldWait()
            )
        );
    }

    public function bind(FrameChannel $channel, Binding $command): Frame
    {
        return Frame::command(
            $channel,
            Methods::get('queue.bind'),
            new UnsignedShortInteger(new Integer(0)), //ticket (reserved)
            new ShortString(new Str($command->queue())),
            new ShortString(new Str($command->exchange())),
            new ShortString(new Str($command->routingKey())),
            new Bits(!$command->shouldWait()),
            $this->translate($command->arguments())
        );
    }

    public function unbind(FrameChannel $channel, Unbinding $command): Frame
    {
        return Frame::command(
            $channel,
            Methods::get('queue.unbind'),
            new UnsignedShortInteger(new Integer(0)), //ticket (reserved)
            new ShortString(new Str($command->queue())),
            new ShortString(new Str($command->exchange())),
            new ShortString(new Str($command->routingKey())),
            $this->translate($command->arguments())
        );
    }

    public function purge(FrameChannel $channel, Purge $command): Frame
    {
        return Frame::command(
            $channel,
            Methods::get('queue.purge'),
            new UnsignedShortInteger(new Integer(0)), //ticket (reserved)
            new ShortString(new Str($command->name())),
            new Bits(!$command->shouldWait())
        );
    }

    /**
     * @param MapInterface<string, mixed> $arguments
     */
    private function translate(MapInterface $arguments): Table
    {
        return new Table(
            $arguments->reduce(
                new Map('string', Value::class),
                function(Map $carry, string $key, $value): Map {
                    return $carry->put(
                        $key,
                        ($this->translate)($value)
                    );
                }
            )
        );
    }
}
