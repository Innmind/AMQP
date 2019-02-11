<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Visitor;

use Innmind\AMQP\Transport\Frame\Value;
use Innmind\Stream\Readable;
use Innmind\Immutable\{
    StreamInterface,
    Stream
};

final class ChunkArguments
{
    private $types;

    public function __construct(string ...$types)
    {
        $this->types = $types;
    }

    /**
     * @return StreamInterface<Value>
     */
    public function __invoke(Readable $arguments): StreamInterface
    {
        $stream = new Stream(Value::class);

        foreach ($this->types as $type) {
            $stream = $stream->add([$type, 'fromStream']($arguments));
        }

        return $stream;
    }
}
