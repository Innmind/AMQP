<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\Value;
use Innmind\IO\Frame;
use Innmind\Immutable\{
    Str,
    Sequence,
    Either,
};

/**
 * @implements Value<Sequence<bool>>
 * @psalm-immutable
 */
final class Bits implements Value
{
    /**
     * @param Sequence<bool> $original
     */
    private function __construct(private Sequence $original)
    {
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
     * @psalm-pure
     *
     * @return Either<mixed, Value>
     */
    public static function wrap(mixed $value): Either
    {
        return match (true) {
            \is_bool($value) => Either::right(self::of($value)),
            default => Either::left($value),
        };
    }

    /**
     * @psalm-pure
     *
     * @return Frame<Unpacked<self>>
     */
    public static function frame(): Frame
    {
        return Frame::chunk(1)
            ->strict()
            ->map(
                static fn($chunk) => $chunk
                    ->map(static fn($chunk) => \decbin(\ord($chunk)))
                    ->chunk()
                    ->map(static fn($bit) => (bool) (int) $bit->toString())
                    ->reverse(),
            )
            ->filter(static fn($bits) => !$bits->empty())
            ->map(static fn($bits) => new self($bits))
            ->map(static fn($value) => Unpacked::of(1, $value));
    }

    /**
     * @return Sequence<bool>
     */
    #[\Override]
    public function original(): Sequence
    {
        return $this->original;
    }

    #[\Override]
    public function symbol(): Symbol
    {
        return Symbol::bits;
    }

    #[\Override]
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
