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
    Transport\Protocol\Exchange as ExchangeInterface,
    Transport\Protocol\ArgumentTranslator,
};
use Innmind\Math\Algebra\Integer;
use Innmind\Immutable\{
    Str,
    Map,
};

final class Exchange implements ExchangeInterface
{
    private ArgumentTranslator $translate;

    public function __construct(ArgumentTranslator $translator)
    {
        $this->translate = $translator;
    }

    public function declare(FrameChannel $channel, Declaration $command): Frame
    {
        /** @var Map<string, Value> */
        $arguments = $command
            ->arguments()
            ->reduce(
                Map::of('string', Value::class),
                function(Map $carry, string $key, $value): Map {
                    return $carry->put(
                        $key,
                        ($this->translate)($value)
                    );
                }
            );

        return Frame::method(
            $channel,
            Methods::get('exchange.declare'),
            new UnsignedShortInteger(new Integer(0)), //ticket (reserved)
            ShortString::of(Str::of($command->name())),
            ShortString::of(Str::of($command->type()->toString())),
            new Bits(
                $command->isPassive(),
                $command->isDurable(),
                $command->isAutoDeleted(), //reserved
                false, //internal (reserved)
                !$command->shouldWait()
            ),
            new Table($arguments),
        );
    }

    public function delete(FrameChannel $channel, Deletion $command): Frame
    {
        return Frame::method(
            $channel,
            Methods::get('exchange.delete'),
            new UnsignedShortInteger(new Integer(0)), //ticket (reserved)
            ShortString::of(Str::of($command->name())),
            new Bits(
                $command->onlyIfUnused(),
                !$command->shouldWait()
            )
        );
    }
}
