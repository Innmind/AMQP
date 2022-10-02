<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\Value;

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

    /**
     * @psalm-pure
     *
     * @return class-string<Value>
     */
    public static function class(string $symbol): string
    {
        return match ($symbol) {
            'b' => SignedOctet::class,
            'B' => UnsignedOctet::class,
            'U' => SignedShortInteger::class,
            'u' => UnsignedShortInteger::class,
            'I' => SignedLongInteger::class,
            'i' => UnsignedLongInteger::class,
            'L' => SignedLongLongInteger::class,
            'l' => UnsignedLongLongInteger::class,
            'D' => Decimal::class,
            'T' => Timestamp::class,
            'V' => VoidValue::class,
            't' => Bits::class,
            's' => ShortString::class,
            'S' => LongString::class,
            'A' => Sequence::class,
            'F' => Table::class,
        };
    }
}
