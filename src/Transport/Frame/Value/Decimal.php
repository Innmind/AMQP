<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\Value;
use Innmind\IO\Readable\Frame;
use Innmind\Immutable\Str;

/**
 * @implements Value<int|float>
 * @psalm-immutable
 */
final class Decimal implements Value
{
    private SignedLongInteger $value;
    private UnsignedOctet $scale;

    private function __construct(SignedLongInteger $value, UnsignedOctet $scale)
    {
        $this->scale = $scale;
        $this->value = $value;
    }

    /**
     * @psalm-pure
     *
     * @param int<-2147483648, 2147483647> $value
     * @param int<0, 255> $scale
     */
    public static function of(int $value, int $scale): self
    {
        return new self(SignedLongInteger::of($value), UnsignedOctet::of($scale));
    }

    /**
     * @psalm-pure
     *
     * @return Frame<Unpacked<self>>
     */
    public static function frame(): Frame
    {
        return UnsignedOctet::frame()->flatMap(
            static fn($scale) => SignedLongInteger::frame()->map(
                static fn($value) => Unpacked::of(
                    $scale->read() + $value->read(),
                    new self($value->unwrap(), $scale->unwrap()),
                ),
            ),
        );
    }

    public function original(): int|float
    {
        return $this->value->original() / (10 ** $this->scale->original());
    }

    public function symbol(): Symbol
    {
        return Symbol::decimal;
    }

    public function pack(): Str
    {
        return $this->scale->pack()->append($this->value->pack()->toString());
    }
}
