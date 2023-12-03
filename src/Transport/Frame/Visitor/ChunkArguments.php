<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Visitor;

use Innmind\AMQP\Transport\Frame\{
    Value,
    Value\Unpacked,
};
use Innmind\IO\Readable\{
    Stream,
    Frame,
};
use Innmind\Socket\Client;
use Innmind\Immutable\{
    Sequence,
    Maybe,
};

/**
 * @internal
 */
final class ChunkArguments
{
    /** @var Sequence<Frame<Unpacked>> */
    private Sequence $frames;

    /**
     * @no-named-arguments
     *
     * @param list<Frame<Unpacked>> $frames
     */
    public function __construct(Frame ...$frames)
    {
        $this->frames = Sequence::of(...$frames);
    }

    /**
     * @param Stream<Client> $arguments
     *
     * @return Maybe<Sequence<Value>>
     */
    public function __invoke(Stream $arguments): Maybe
    {
        /**
         * @psalm-suppress NamedArgumentNotAllowed
         * @var Frame<Sequence<Value>>
         */
        $frame = $this->frames->match(
            static fn($first, $rest) => Frame\Composite::of(
                static fn(Value ...$values) => Sequence::of(...$values),
                $first,
                ...$rest->toList(),
            ),
            static fn() => Frame\NoOp::of(Sequence::of()),
        );

        return $arguments
            ->frames($frame)
            ->one();
    }
}
