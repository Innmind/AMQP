<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Visitor;

use Innmind\AMQP\Transport\Frame\Value;
use Innmind\Stream\Readable;
use Innmind\Immutable\Sequence;

final class ChunkArguments
{
    private array $types;

    public function __construct(string ...$types)
    {
        $this->types = $types;
    }

    /**
     * @return Sequence<Value>
     */
    public function __invoke(Readable $arguments): Sequence
    {
        $sequence = Sequence::of(Value::class);

        foreach ($this->types as $type) {
            $sequence = ($sequence)([$type, 'unpack']($arguments));
        }

        return $sequence;
    }
}
