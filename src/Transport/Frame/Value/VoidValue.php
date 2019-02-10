<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\Value;
use Innmind\Stream\Readable;
use Innmind\Immutable\Str;

final class VoidValue implements Value
{
    public static function fromString(Str $string): Value
    {
        return new self;
    }

    public static function fromStream(Readable $stream): Value
    {
        return new self;
    }

    public static function cut(Str $string): Str
    {
        return new Str('');
    }

    public function original(): void
    {
    }

    public function __toString(): string
    {
        return '';
    }
}
