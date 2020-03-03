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

final class Bits implements Value
{
    private ?string $value = null;
    private Seq $original;

    public function __construct(bool $first, bool ...$bits)
    {
        $this->original = Seq::of('bool', $first, ...$bits);
    }

    public static function unpack(Readable $stream): Value
    {
        return new self(
            ...unwrap($stream
                ->read(1)
                ->toEncoding('ASCII')
                ->chunk()
                ->reduce(
                    Seq::of('int'),
                    static function(Seq $bits, Str $bit): Seq {
                        return Str::of(\decbin(\ord($bit->toString())))
                            ->chunk()
                            ->reduce(
                                $bits,
                                static function(Seq $bits, Str $bit): Seq {
                                    return $bits->add((int) $bit->toString());
                                }
                            );
                    }
                )
                ->mapTo('bool', static function(int $bit): bool {
                    return (bool) $bit;
                })
                ->reverse()),
        );
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
        if (\is_null($this->value)) {
            $value = 0;

            foreach (unwrap($this->original) as $i => $bit) {
                $bit = (int) $bit;
                $value |= $bit << $i;
            }

            $this->value = \chr($value);
        }

        return $this->value;
    }
}
