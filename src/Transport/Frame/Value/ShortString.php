<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\Value;
use Innmind\Math\Algebra\Integer;
use Innmind\Stream\Readable;
use Innmind\Immutable\Str;

final class ShortString implements Value
{
    private $value;
    private $original;

    public function __construct(Str $string)
    {
        $this->original = $string;
        $string = $string->toEncoding('ASCII');
        $this->value = (string) new UnsignedOctet(
            new Integer($string->length())
        );
        $this->value .= $string;
    }

    public static function fromStream(Readable $stream): Value
    {
        $length = UnsignedOctet::fromStream($stream)->original();

        return new self($stream->read($length->value()));
    }

    public function original(): Str
    {
        return $this->original;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
