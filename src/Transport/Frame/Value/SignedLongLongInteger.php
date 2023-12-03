<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\Value;
use Innmind\IO\Readable\Frame;
use Innmind\Immutable\Str;

/**
 * @implements Value<int>
 * @psalm-immutable
 */
final class SignedLongLongInteger implements Value
{
    private int $original;

    private function __construct(int $value)
    {
        $this->original = $value;
    }

    /**
     * @psalm-pure
     */
    public static function of(int $value): self
    {
        return new self($value);
    }

    /**
     * @return Frame<Unpacked<self>>
     */
    public static function frame(): Frame
    {
        return Frame\Chunk::of(8)
            ->map(static function($chunk) {
                /** @var int $value */
                [, $value] = \unpack('q', $chunk->toString());

                return $value;
            })
            ->map(static fn($value) => new self($value))
            ->map(static fn($value) => Unpacked::of(8, $value));
    }

    public function original(): int
    {
        return $this->original;
    }

    public function symbol(): Symbol
    {
        return Symbol::signedLongLongInteger;
    }

    public function pack(): Str
    {
        return Str::of(\pack('q', $this->original));
    }
}
