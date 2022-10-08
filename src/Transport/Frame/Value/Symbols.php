<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\Value;
use Innmind\Stream\Readable;

final class Symbols
{
    /**
     * @psalm-pure
     *
     * @param class-string<Value> $class
     */
    public static function symbol(string $class): string
    {
        return match ($class) {
            SignedOctet::class => 'b',
            UnsignedOctet::class => 'B',
            SignedShortInteger::class => 'U',
            UnsignedShortInteger::class => 'u',
            SignedLongInteger::class => 'I',
            UnsignedLongInteger::class => 'i',
            SignedLongLongInteger::class => 'L',
            UnsignedLongLongInteger::class => 'l',
            Decimal::class => 'D',
            Timestamp::class => 'T',
            VoidValue::class => 'V',
            Bits::class => 't',
            ShortString::class => 's',
            LongString::class => 'S',
            Sequence::class => 'A',
            Table::class => 'F',
        };
    }

    public static function unpack(string $symbol, Readable $stream): Value
    {
        return match ($symbol) {
            'b' => SignedOctet::unpack($stream),
            'B' => UnsignedOctet::unpack($stream),
            'U' => SignedShortInteger::unpack($stream),
            'u' => UnsignedShortInteger::unpack($stream),
            'I' => SignedLongInteger::unpack($stream),
            'i' => UnsignedLongInteger::unpack($stream),
            'L' => SignedLongLongInteger::unpack($stream),
            'l' => UnsignedLongLongInteger::unpack($stream),
            'D' => Decimal::unpack($stream),
            'T' => Timestamp::unpack($stream),
            'V' => VoidValue::unpack($stream),
            't' => Bits::unpack($stream),
            's' => ShortString::unpack($stream),
            'S' => LongString::unpack($stream),
            'A' => Sequence::unpack($stream),
            'F' => Table::unpack($stream),
        };
    }
}
