<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\Value;
use Innmind\Stream\Readable;
use Innmind\Immutable\{
    Str,
    Sequence as Seq,
};

/**
 * @implements Value<Seq<bool>>
 * @psalm-immutable
 */
final class Bits implements Value
{
    /** @var Seq<bool> */
    private Seq $original;

    public function __construct(bool $first, bool ...$bits)
    {
        $this->original = Seq::of($first, ...$bits);
    }

    public static function unpack(Readable $stream): self
    {
        $chunk = $stream
            ->read(1)
            ->map(static fn($chunk) => $chunk->toEncoding('ASCII'))
            ->filter(static fn($chunk) => $chunk->length() === 1)
            ->match(
                static fn($chunk) => $chunk,
                static fn() => throw new \LogicException,
            );
        $bits = $chunk
            ->map(static fn($chunk) => \decbin(\ord($chunk)))
            ->chunk()
            ->map(static fn($bit) => (bool) (int) $bit->toString())
            ->reverse()
            ->toList();

        return new self(...$bits);
    }

    /**
     * @return Seq<bool>
     */
    public function original(): Seq
    {
        return $this->original;
    }

    public function pack(): string
    {
        $value = 0;

        foreach ($this->original->toList() as $i => $bit) {
            $bit = (int) $bit;
            $value |= $bit << $i;
        }

        return \chr($value);
    }
}
