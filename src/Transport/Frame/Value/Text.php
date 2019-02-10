<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\Value;
use Innmind\Stream\Readable;
use Innmind\Immutable\Str;

final class Text implements Value
{
    private $value;
    private $original;

    public function __construct(Str $string)
    {
        $this->value = (string) $string;
        $this->original = $string;
    }

    public static function fromString(Str $string): Value
    {
        return new self($string);
    }

    public static function fromStream(Readable $stream): Value
    {
        return new self($stream->read());
    }

    public static function cut(Str $string): Str
    {
        return $string;
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
