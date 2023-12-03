<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Visitor;

use Innmind\AMQP\Transport\Frame\{
    Value,
    Value\Unpacked,
};
use Innmind\IO\Readable\Stream;
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
    /** @var Sequence<callable(Stream<Client>): Maybe<Unpacked>> */
    private Sequence $types;

    /**
     * @no-named-arguments
     *
     * @param list<callable(Stream<Client>): Maybe<Unpacked>> $types
     */
    public function __construct(callable ...$types)
    {
        $this->types = Sequence::of(...$types);
    }

    /**
     * @param Stream<Client> $arguments
     *
     * @return Maybe<Sequence<Value>>
     */
    public function __invoke(Stream $arguments): Maybe
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
     * @param callable(Stream<Client>): Maybe<Unpacked> $unpack
     * @param Stream<Client> $arguments
     *
     * @return Maybe<Sequence<Value>>
     */
    private function unpack(
        Maybe $maybe,
        callable $unpack,
        Stream $arguments,
    ): Maybe {
        return $maybe->flatMap(
            static fn($values) => $unpack($arguments)->map(
                static fn($value) => ($values)($value->unwrap()),
            ),
        );
    }
}
