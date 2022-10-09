<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\Value;
use Innmind\Math\{
    Algebra\Integer,
    Algebra,
    DefinitionSet\Set,
    DefinitionSet\Range,
};
use Innmind\Stream\Readable;

/**
 * @implements Value<int<0, max>>
 * @psalm-immutable
 */
final class UnsignedLongLongInteger implements Value
{
    /** @var int<0, max> */
    private int $original;

    /**
     * @param int<0, max> $value
     */
    private function __construct(int $value)
    {
        $this->original = $value;
    }

    /**
     * @psalm-pure
     * @internal
     *
     * @param int<0, max> $value
     */
    public static function internal(int $value): self
    {
        return new self($value);
    }

    /**
     * @psalm-pure
     *
     * @param int<0, max> $value
     */
    public static function of(int $value): self
    {
        self::definitionSet()->accept(Integer::of($value));

        return new self($value);
    }

    public static function unpack(Readable $stream): self
    {
        $chunk = $stream
            ->read(8)
            ->map(static fn($chunk) => $chunk->toEncoding('ASCII'))
            ->filter(static fn($chunk) => $chunk->length() === 8)
            ->match(
                static fn($chunk) => $chunk,
                static fn() => throw new \LogicException,
            );

        /** @var int<0, max> $value */
        [, $value] = \unpack('J', $chunk->toString());

        return new self($value);
    }

    /**
     * @return int<0, max>
     */
    public function original(): int
    {
        return $this->original;
    }

    public function pack(): string
    {
        return \pack('J', $this->original);
    }

    /**
     * @psalm-pure
     */
    public static function definitionSet(): Set
    {
        return Range::inclusive(
            Integer::of(0),
            Algebra\Value::infinite,
        );
    }
}
