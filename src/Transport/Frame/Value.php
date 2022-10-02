<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame;

use Innmind\Stream\Readable;

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
    public function pack(): string;
}
