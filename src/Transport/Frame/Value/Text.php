<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\Value;
use Innmind\Stream\Readable;
use Innmind\Immutable\Str;

final class Text implements Value
{
    private string $value;
    private Str $original;

    public function __construct(Str $string)
    {
        $this->value = (string) $string;
        $this->original = $string;
    }

    public static function fromStream(Readable $stream): Value
    {
        return new self($stream->read());
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
