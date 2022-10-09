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
 * @implements Value<int<0, 4294967295>>
 * @psalm-immutable
 */
final class UnsignedLongInteger implements Value
{
    /** @var int<0, 4294967295> */
    private int $original;

    /**
     * @param int<0, 4294967295> $value
     */
    private function __construct(int $value)
    {
        $this->original = $value;
    }

    /**
     * @psalm-pure
     * @internal
     *
     * @param int<0, 4294967295> $value
     */
    public static function internal(int $value): self
    {
        return new self($value);
    }

    /**
     * @psalm-pure
     *
     * @param int<0, 4294967295> $value
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

        /** @var int<0, 4294967295> $value */
        [, $value] = \unpack('N', $chunk->toString());

        return new self($value);
    }

    /**
     * @return int<0, 4294967295>
     */
    public function original(): int
    {
        return $this->original;
    }

    public function pack(): string
    {
        return \pack('N', $this->original);
    }

    /**
     * @psalm-pure
     */
    public static function definitionSet(): Set
    {
        return Range::inclusive(
            Integer::of(0),
            Integer::of(4294967295),
        );
    }
}
