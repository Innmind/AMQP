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

final class SignedLongLongInteger implements Value
{
    private $value;
    private $original;

    public function __construct(Integer $value)
    {
        $this->original = $value;
    }

    public static function fromString(Str $string): Value
    {
        $string = $string->toEncoding('ASCII');

        if ($string->length() !== 8) {
            throw new StringNotOfExpectedLength($string, 8);
        }

        [, $value] = unpack('q', (string) $string);

        return new self(new Integer($value));
    }

    public static function fromStream(Readable $stream): Value
    {
        return self::fromString($stream->read(8));
    }

    public static function cut(Str $string): Str
    {
        return $string->toEncoding('ASCII')->substring(0, 8);
    }

    public function original(): Integer
    {
        return $this->original;
    }

    public function __toString(): string
    {
        return $this->value ?? $this->value = pack('q', $this->original->value());
    }
}
