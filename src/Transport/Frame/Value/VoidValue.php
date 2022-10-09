<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\Value;
use Innmind\Stream\Readable;
use Innmind\Immutable\{
    Str,
    Maybe,
};

/**
 * @implements Value<void>
 * @psalm-immutable
 */
final class VoidValue implements Value
{
    /**
     * @return Maybe<self>
     */
    public static function unpack(Readable $stream): Maybe
    {
        return Maybe::just(new self);
    }

    public function original(): void
    {
    }

    public function symbol(): Symbol
    {
        return Symbol::void;
    }

    public function pack(): Str
    {
        return Str::of('');
    }
}
