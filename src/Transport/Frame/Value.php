<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame;

use Innmind\Stream\Readable;
use Innmind\Immutable\Str;

interface Value
{
    public static function fromString(Str $string): self;
    public static function fromStream(Readable $stream): self;
    public static function cut(Str $string): Str;

    /**
     * Original value
     *
     * @return mixed
     */
    public function original();
    public function __toString(): string;
}
