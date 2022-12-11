<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Visitor;

use Innmind\AMQP\Transport\Frame\Value;
use Innmind\Stream\Readable;
use Innmind\Immutable\{
    Sequence,
    Maybe,
};

/**
 * @internal
 */
final class ChunkArguments
{
    /** @var Sequence<callable(Readable): Maybe<Value>> */
    private Sequence $types;

    /**
     * @no-named-arguments
     *
     * @param list<callable(Readable): Maybe<Value>> $types
     */
    public function __construct(callable ...$types)
    {
        $this->types = Sequence::of(...$types);
    }

    /**
     * @return Maybe<Sequence<Value>>
     */
    public function __invoke(Readable $arguments): Maybe
    {
        /** @var Sequence<Value> */
        $values = Sequence::of();

        /** @psalm-suppress MixedArgumentTypeCoercion */
        return $this
            ->types
            ->reduce(
                Maybe::just($values),
                fn(Maybe $maybe, $unpack) => $this->unpack(
                    $maybe,
                    $unpack,
                    $arguments,
                ),
            );
    }

    /**
     * @param Maybe<Sequence<Value>> $maybe
     * @param callable(Readable): Maybe<Value> $unpack
     *
     * @return Maybe<Sequence<Value>>
     */
    private function unpack(
        Maybe $maybe,
        callable $unpack,
        Readable $arguments,
    ): Maybe {
        return $maybe->flatMap(
            static fn($values) => $unpack($arguments)->map(
                static fn($value) => ($values)($value),
            ),
        );
    }
}
