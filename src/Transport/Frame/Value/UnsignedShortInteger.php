<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\Value;
use Innmind\Math\{
    Algebra\Integer,
    DefinitionSet\Set,
    DefinitionSet\Range,
};
use Innmind\Stream\Readable;

/**
 * @implements Value<Integer>
 * @psalm-immutable
 */
final class UnsignedShortInteger implements Value
{
    private Integer $original;

    public function __construct(Integer $value)
    {
        $this->original = $value;
    }

    /**
     * @psalm-pure
     */
    public static function of(Integer $value): self
    {
        self::definitionSet()->accept($value);

        return new self($value);
    }

    public static function unpack(Readable $stream): self
    {
        $chunk = $stream->read(2)->match(
            static fn($chunk) => $chunk,
            static fn() => throw new \LogicException,
        );

        /** @var int $value */
        [, $value] = \unpack('n', $chunk->toString());

        return new self(Integer::of($value));
    }

    public function original(): Integer
    {
        return $this->original;
    }

    public function pack(): string
    {
        return \pack('n', $this->original->value());
    }

    /**
     * @psalm-pure
     */
    public static function definitionSet(): Set
    {
        return Range::inclusive(
            Integer::of(0),
            Integer::of(65535),
        );
    }
}
