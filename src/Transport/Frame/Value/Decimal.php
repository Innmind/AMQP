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
 */
final class Decimal implements Value
{
    private static ?NaturalNumbers $definitionSet = null;

    private ?string $string = null;
    private Integer $value;
    private Integer $scale;
    private Number $original;

    public function __construct(Integer $value, Integer $scale)
    {
        $this->scale = $scale;
        $this->value = $value;
        $this->original = $value->divideBy(
            (new Integer(10))->power($scale)
        );
    }

    public static function of(Integer $value, Integer $scale): self
    {
        SignedLongInteger::of($value);
        UnsignedOctet::of($scale);

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
        return $this->string ?? $this->string = (new UnsignedOctet($this->scale))->pack().(new SignedLongInteger($this->value))->pack();
    }

    public static function definitionSet(): Set
    {
        return self::$definitionSet ?? self::$definitionSet = new NaturalNumbers;
    }
}
