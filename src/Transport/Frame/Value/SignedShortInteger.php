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
 * @implements Value<int<-32768, 32767>>
 * @psalm-immutable
 */
final class SignedShortInteger implements Value
{
    /** @var int<-32768, 32767> */
    private int $original;

    /**
     * @param int<-32768, 32767> $value
     */
    private function __construct(int $value)
    {
        $this->original = $value;
    }

    /**
     * @psalm-pure
     *
     * @param int<-32768, 32767> $value
     */
    public static function of(int $value): self
    {
        self::definitionSet()->accept(Integer::of($value));

        return new self($value);
    }

    public static function unpack(Readable $stream): self
    {
        $chunk = $stream
            ->read(2)
            ->map(static fn($chunk) => $chunk->toEncoding('ASCII'))
            ->filter(static fn($chunk) => $chunk->length() === 2)
            ->match(
                static fn($chunk) => $chunk,
                static fn() => throw new \LogicException,
            );
        /** @var int<-32768, 32767> $value */
        [, $value] = \unpack('s', $chunk->toString());

        return new self($value);
    }

    /**
     * @return int<-32768, 32767>
     */
    public function original(): int
    {
        return $this->original;
    }

    public function symbol(): Symbol
    {
        return Symbol::signedShortInteger;
    }

    public function pack(): Str
    {
        return Str::of(\pack('s', $this->original));
    }

    /**
     * @psalm-pure
     */
    public static function definitionSet(): Set
    {
        return Range::inclusive(
            Integer::of(-32768),
            Integer::of(32767),
        );
    }
}
