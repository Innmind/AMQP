<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\Value;
use Innmind\IO\Readable\Frame;
use Innmind\Immutable\Str;

/**
 * @implements Value<Str>
 * @psalm-immutable
 */
final class LongString implements Value
{
    private Str $original;

    private function __construct(Str $string)
    {
        $this->original = $string;
    }

    /**
     * @psalm-pure
     *
     * @param literal-string $string Of maximum 4294967295 length
     */
    public static function literal(string $string): self
    {
        return new self(Str::of($string));
    }

    /**
     * @psalm-pure
     */
    public static function of(Str $string): self
    {
        /** @psalm-suppress InvalidArgument */
        $_ = UnsignedLongInteger::of($string->toEncoding(Str\Encoding::ascii)->length());

        return new self($string);
    }

    /**
     * @return Frame<Unpacked<self>>
     */
    public static function frame(): Frame
    {
        return UnsignedLongInteger::frame()->flatMap(
            static fn($length) => (match ($length->unwrap()->original()) {
                0 => Frame\NoOp::of(Str::of('')),
                default => Frame\Chunk::of($length->unwrap()->original()),
            })
                ->map(static fn($string) => new self($string))
                ->map(static fn($value) => Unpacked::of(
                    $length->read() + $length->unwrap()->original(),
                    $value,
                )),
        );
    }

    public function original(): Str
    {
        return $this->original;
    }

    public function symbol(): Symbol
    {
        return Symbol::longString;
    }

    public function pack(): Str
    {
        /** @psalm-suppress InvalidArgument */
        return UnsignedLongInteger::of(
            $this->original->toEncoding(Str\Encoding::ascii)->length(),
        )
            ->pack()
            ->append($this->original->toString());
    }
}
