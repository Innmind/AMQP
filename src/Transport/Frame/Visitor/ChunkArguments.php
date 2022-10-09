<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Visitor;

use Innmind\AMQP\Transport\Frame\Value;
use Innmind\Stream\Readable;
use Innmind\Immutable\Sequence;

final class ChunkArguments
{
    /** @var list<callable(Readable): Value> */
    private array $types;

    /**
     * @no-named-arguments
     *
     * @param list<callable(Readable): Value> $types
     */
    public function __construct(callable ...$types)
    {
        $this->types = $types;
    }

    /**
     * @return Sequence<Value>
     */
    public function __invoke(Readable $arguments): Sequence
    {
        /** @var Sequence<Value> */
        $sequence = Sequence::of();

        foreach ($this->types as $unpack) {
            $sequence = ($sequence)($unpack($arguments));
        }

        return $sequence;
    }
}
