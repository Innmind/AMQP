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
 */
final class SignedLongInteger implements Value
{
    private static ?Set $definitionSet = null;

    private Integer $original;

    public function __construct(Integer $value)
    {
        $this->original = $value;
    }

    public static function of(Integer $value): self
    {
        self::definitionSet()->accept($value);

        return new self($value);
    }

    public static function unpack(Readable $stream): self
    {
        $chunk = $stream->read(4)->match(
            static fn($chunk) => $chunk,
            static fn() => throw new \LogicException,
        );
        /** @var int $value */
        [, $value] = \unpack('l', $chunk->toString());

        return new self(Integer::of($value));
    }

    public function original(): Integer
    {
        return $this->original;
    }

    public function pack(): string
    {
        return \pack('l', $this->original->value());
    }

    public static function definitionSet(): Set
    {
        return self::$definitionSet ?? self::$definitionSet = Range::inclusive(
            Integer::of(-2147483648),
            Integer::of(2147483647),
        );
    }
}
