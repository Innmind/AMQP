<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\{
    Transport\Frame\Value,
    Exception\StringNotOfExpectedLength
};
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

    public static function fromString(Str $string): Value
    {
        $string = $string->toEncoding('ASCII');
        $length = UnsignedOctet::fromString($string->substring(0, 1))->original();
        $string = $string->substring(1);

        if ($string->length() !== $length->value()) {
            throw new StringNotOfExpectedLength($string, $length->value());
        }

        return new self($string);
    }

    public static function fromStream(Readable $stream): Value
    {
        $length = UnsignedOctet::fromStream($stream)->original();

        return new self($stream->read($length->value()));
    }

    public static function cut(Str $string): Str
    {
        $string = $string->toEncoding('ASCII');
        $length = UnsignedOctet::fromString($string->substring(0, 1))->original();

        return $string->substring(0, $length->value() + 1);
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
