<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\Value;
use Innmind\IO\Readable\Stream;
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
     * @return Maybe<self>
     */
    public static function unpack(Stream $stream): Maybe
    {
        return $stream
            ->unwrap()
            ->read(8)
            ->map(static fn($chunk) => $chunk->toEncoding(Str\Encoding::ascii))
            ->filter(static fn($chunk) => $chunk->length() === 8)
            ->map(static function($chunk) {
                /** @var int $value */
                [, $value] = \unpack('q', $chunk->toString());

                return $value;
            })
            ->map(static fn($value) => new self($value));
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
