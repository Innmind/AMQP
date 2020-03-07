<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Visitor;

use Innmind\AMQP\Transport\Frame\Value;
use Innmind\Stream\Readable;
use Innmind\Immutable\Sequence;

final class ChunkArguments
{
    /** @var list<class-string<Value>> */
    private array $types;

    /**
     * @param list<class-string<Value>> $types
     */
    public function __construct(string ...$types)
    {
        $this->types = $types;
    }

    /**
     * @return Sequence<Value>
     */
    public function __invoke(Readable $arguments): Sequence
    {
        /** @var Sequence<Value> */
        $sequence = Sequence::of(Value::class);

        foreach ($this->types as $type) {
            /** @var Value */
            $value = [$type, 'unpack']($arguments);
            $sequence = ($sequence)($value);
        }

        return $sequence;
    }
}
