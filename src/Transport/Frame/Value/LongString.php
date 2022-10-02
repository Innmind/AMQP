<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\Value;
use Innmind\Math\Algebra\Integer;
use Innmind\Stream\Readable;
use Innmind\Immutable\Str;

/**
 * @implements Value<Str>
 * @psalm-immutable
 */
final class LongString implements Value
{
    private Str $original;

    public function __construct(Str $string)
    {
        $this->original = $string;
    }

    /**
     * @psalm-pure
     */
    public static function of(Str $string): self
    {
        $_ = UnsignedLongInteger::of(Integer::of($string->toEncoding('ASCII')->length()));

        return new self($string);
    }

    public static function unpack(Readable $stream): self
    {
        $length = UnsignedLongInteger::unpack($stream)->original();
        /** @psalm-suppress ArgumentTypeCoercion */
        $string = $stream->read($length->value())->match(
            static fn($string) => $string,
            static fn() => throw new \LogicException,
        );

        return new self($string);
    }

    public function original(): Str
    {
        return $this->original;
    }

    public function pack(): string
    {
        return (new UnsignedLongInteger(
            Integer::of($this->original->toEncoding('ASCII')->length()),
        ))->pack().$this->original->toString();
    }
}
