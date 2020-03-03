<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\Value;
use Innmind\Stream\Readable;

final class VoidValue implements Value
{
    public static function unpack(Readable $stream): Value
    {
        return new self;
    }

    public function original(): void
    {
    }

    public function pack(): string
    {
        return '';
    }
}
