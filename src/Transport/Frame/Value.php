<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame;

use Innmind\Stream\Readable;

interface Value
{
    public static function unpack(Readable $stream): self;

    /**
     * Original value
     *
     * @return mixed
     */
    public function original();
    public function pack(): string;
}
