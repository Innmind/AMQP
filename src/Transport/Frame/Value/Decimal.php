<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\Value;
use Innmind\Math\{
    Algebra\Number,
    Algebra\Integer,
    DefinitionSet\Set,
    DefinitionSet\NaturalNumbers,
};
use Innmind\Stream\Readable;

/**
 * @implements Value<Number>
 * @psalm-immutable
 */
final class Decimal implements Value
{
    private Integer $value;
    private Integer $scale;
    private Number $original;

    public function __construct(Integer $value, Integer $scale)
    {
        $this->scale = $scale;
        $this->value = $value;
        $this->original = $value->divideBy(
            Integer::of(10)->power($scale),
        );
    }

    /**
     * @psalm-pure
     */
    public static function of(Integer $value, Integer $scale): self
    {
        $_ = SignedLongInteger::of($value);
        $_ = UnsignedOctet::of($scale);

        return new self($value, $scale);
    }

    public static function unpack(Readable $stream): self
    {
        $scale = UnsignedOctet::unpack($stream)->original();
        $value = SignedLongInteger::unpack($stream)->original();

        return new self($value, $scale);
    }

    public function original(): Number
    {
        return $this->original;
    }

    public function pack(): string
    {
        return (new UnsignedOctet($this->scale))->pack().(new SignedLongInteger($this->value))->pack();
    }

    public static function definitionSet(): Set
    {
        return new NaturalNumbers;
    }
}
