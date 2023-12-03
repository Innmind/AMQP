<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\Value;
use Innmind\IO\Readable\{
    Stream,
    Frame,
};
use Innmind\Socket\Client;
use Innmind\Immutable\{
    Str,
    Maybe,
};

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
     * @param Stream<Client> $stream
     *
     * @return Maybe<Unpacked<self>>
     */
    public static function unpack(Stream $stream): Maybe
    {
        return $stream
            ->frames(Frame\Chunk::of(8))
            ->one()
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
