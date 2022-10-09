<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame;

use Innmind\Stream\Readable;
use Innmind\Immutable\Str;

/**
 * @template T
 *
 * @psalm-immutable
 */
interface Value
{
    public static function unpack(Readable $stream): self;

    /**
     * Original value
     *
     * @return T
     */
    public function original();
    public function symbol(): Value\Symbol;
    public function pack(): Str;
}
