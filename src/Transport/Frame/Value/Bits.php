<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\Value;
use Innmind\Stream\Readable;
use Innmind\Immutable\{
    Str,
    Sequence as Seq,
};
use function Innmind\Immutable\unwrap;

/**
 * @implements Value<Seq<bool>>
 */
final class Bits implements Value
{
    /** @var Seq<bool> */
    private Seq $original;

    public function __construct(bool $first, bool ...$bits)
    {
        $this->original = Seq::of('bool', $first, ...$bits);
    }

    public static function unpack(Readable $stream): self
    {
        $bits = $stream
            ->read(1)
            ->toEncoding('ASCII')
            ->chunk()
            ->toSequenceOf(
                'bool',
                static function(Str $bits): \Generator {
                    $bits = Str::of(\decbin(\ord($bits->toString())));
                    $bitsAsStrings = unwrap($bits->chunk());

                    foreach ($bitsAsStrings as $bit) {
                        yield (bool) (int) $bit->toString();
                    }
                },
            )
            ->reverse();

        return new self(...unwrap($bits));
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

        foreach (unwrap($this->original) as $i => $bit) {
            $bit = (int) $bit;
            $value |= $bit << $i;
        }

        return \chr($value);
    }
}
