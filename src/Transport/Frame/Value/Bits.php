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
    Sequence,
    Maybe,
};

/**
 * @implements Value<Sequence<bool>>
 * @psalm-immutable
 */
final class Bits implements Value
{
    /** @var Sequence<bool> */
    private Sequence $original;

    /**
     * @param Sequence<bool> $original
     */
    private function __construct(Sequence $original)
    {
        $this->original = $original;
    }

    /**
     * @psalm-pure
     * @no-named-arguments
     */
    public static function of(bool $first, bool ...$bits): self
    {
        return new self(Sequence::of($first, ...$bits));
    }

    /**
     * @param Stream<Client> $stream
     *
     * @return Maybe<self>
     */
    public static function unpack(Stream $stream): Maybe
    {
        return $stream
            ->frames(Frame\Chunk::of(1))
            ->one()
            ->filter(static fn($chunk) => $chunk->length() === 1)
            ->map(
                static fn($chunk) => $chunk
                    ->map(static fn($chunk) => \decbin(\ord($chunk)))
                    ->chunk()
                    ->map(static fn($bit) => (bool) (int) $bit->toString())
                    ->reverse(),
            )
            ->exclude(static fn($bits) => $bits->empty())
            ->map(static fn($bits) => new self($bits));
    }

    /**
     * @return Sequence<bool>
     */
    public function original(): Sequence
    {
        return $this->original;
    }

    public function symbol(): Symbol
    {
        return Symbol::bits;
    }

    public function pack(): Str
    {
        $value = $this
            ->original
            ->indices()
            ->zip($this->original)
            ->map(static fn($pair) => ((int) $pair[1]) << $pair[0])
            ->reduce(
                0,
                static fn(int $value, int $bit) => $value | $bit,
            );

        return Str::of(\chr($value));
    }
}
