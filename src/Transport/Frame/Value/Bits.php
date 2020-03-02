<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\Value;
use Innmind\Stream\Readable;
use Innmind\Immutable\{
    StreamInterface,
    Stream,
    Str,
    Sequence as Seq,
};

final class Bits implements Value
{
    private ?string $value = null;
    private Stream $original;

    public function __construct(bool $first, bool ...$bits)
    {
        $this->original = Stream::of('bool', $first, ...$bits);
    }

    public static function fromStream(Readable $stream): Value
    {
        return new self(
            ...$stream
                ->read(1)
                ->toEncoding('ASCII')
                ->chunk()
                ->reduce(
                    new Seq,
                    static function(Seq $bits, Str $bit): Seq {
                        return (new Str(\decbin(\ord((string) $bit))))
                            ->chunk()
                            ->reduce(
                                $bits,
                                static function(Seq $bits, Str $bit): Seq {
                                    return $bits->add((int) (string) $bit);
                                }
                            );
                    }
                )
                ->map(static function(int $bit): bool {
                    return (bool) $bit;
                })
                ->reverse()
        );
    }

    /**
     * @return StreamInterface<bool>
     */
    public function original(): StreamInterface
    {
        return $this->original;
    }

    public function __toString(): string
    {
        if (\is_null($this->value)) {
            $value = 0;

            foreach ($this->original as $i => $bit) {
                $bit = (int) $bit;
                $value |= $bit << $i;
            }

            $this->value = \chr($value);
        }

        return $this->value;
    }
}
