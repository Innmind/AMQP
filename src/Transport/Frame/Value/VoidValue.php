<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\Value;
use Innmind\IO\Readable\Frame;
use Innmind\Immutable\Str;

/**
 * @implements Value<void>
 * @psalm-immutable
 */
final class VoidValue implements Value
{
    /**
     * @psalm-pure
     *
     * @return Frame<Unpacked<self>>
     */
    public static function frame(): Frame
    {
        return Frame\NoOp::of(Unpacked::of(0, new self));
    }

    #[\Override]
    public function original(): void
    {
    }

    #[\Override]
    public function symbol(): Symbol
    {
        return Symbol::void;
    }

    #[\Override]
    public function pack(): Str
    {
        return Str::of('');
    }
}
