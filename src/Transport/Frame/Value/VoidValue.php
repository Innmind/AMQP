<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\Value;
use Innmind\IO\Readable\Stream;
use Innmind\Socket\Client;
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
     * @param Stream<Client> $stream
     *
     * @return Maybe<Unpacked<self>>
     */
    public static function unpack(Stream $stream): Maybe
    {
        return Maybe::just(Unpacked::of(0, new self));
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
