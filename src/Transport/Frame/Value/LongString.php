<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\Value;
use Innmind\Math\Algebra\Integer;
use Innmind\Stream\Readable;
use Innmind\Immutable\Str;

final class LongString implements Value
{
    private ?string $value = null;
    private Str $original;

    public function __construct(Str $string)
    {
        $this->original = $string;
    }

    public static function of(Str $string): self
    {
        UnsignedLongInteger::of(new Integer($string->toEncoding('ASCII')->length()));

        return new self($string);
    }

    public static function unpack(Readable $stream): Value
    {
        $length = UnsignedLongInteger::unpack($stream)->original();

        return new self($stream->read($length->value()));
    }

    public function original(): Str
    {
        return $this->original;
    }

    public function pack(): string
    {
        return $this->value ?? $this->value = (new UnsignedLongInteger(
            new Integer($this->original->toEncoding('ASCII')->length())
        ))->pack().$this->original->toString();
    }
}
