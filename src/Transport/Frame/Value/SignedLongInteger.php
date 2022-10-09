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
use Innmind\Immutable\Str;

/**
 * @implements Value<int<-2147483648, 2147483647>>
 * @psalm-immutable
 */
final class SignedLongInteger implements Value
{
    /** @var int<-2147483648, 2147483647> */
    private int $original;

    /**
     * @param int<-2147483648, 2147483647> $value
     */
    private function __construct(int $value)
    {
        $this->original = $value;
    }

    /**
     * @psalm-pure
     *
     * @param int<-2147483648, 2147483647> $value
     */
    public static function of(int $value): self
    {
        self::definitionSet()->accept(Integer::of($value));

        return new self($value);
    }

    public static function unpack(Readable $stream): self
    {
        $chunk = $stream
            ->read(4)
            ->map(static fn($chunk) => $chunk->toEncoding('ASCII'))
            ->filter(static fn($chunk) => $chunk->length() === 4)
            ->match(
                static fn($chunk) => $chunk,
                static fn() => throw new \LogicException,
            );
        /** @var int<-2147483648, 2147483647> $value */
        [, $value] = \unpack('l', $chunk->toString());

        return new self($value);
    }

    /**
     * @return int<-2147483648, 2147483647>
     */
    public function original(): int
    {
        return $this->original;
    }

    public function pack(): Str
    {
        return Str::of(\pack('l', $this->original));
    }

    /**
     * @psalm-pure
     */
    public static function definitionSet(): Set
    {
        return Range::inclusive(
            Integer::of(-2147483648),
            Integer::of(2147483647),
        );
    }
}
