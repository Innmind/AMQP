<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Visitor;

use Innmind\AMQP\{
    Transport\Frame\Value,
    Exception\ArgumentsRemainUnparsed
};
use Innmind\Immutable\{
    Str,
    StreamInterface,
    Stream
};

final class Arguments
{
    private $types;

    public function __construct(string ...$types)
    {
        $this->types = $types;
    }

    /**
     * @return StreamInterface<Value>
     */
    public function __invoke(Str $arguments): StreamInterface
    {
        $arguments = $arguments->toEncoding('ASCII');
        $stream = new Stream(Value::class);

        foreach ($this->types as $type) {
            $argument = [$type, 'cut']($arguments);
            $stream = $stream->add([$type, 'fromString']($argument));
            $arguments = $arguments->substring($argument->length());
        }

        if ($arguments->length() !== 0) {
            throw new ArgumentsRemainUnparsed((string) $arguments);
        }

        return $stream;
    }
}
