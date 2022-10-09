<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\Value;
use Innmind\Stream\Readable;
use Innmind\Immutable\Str;

/**
 * @psalm-immutable
 */
enum Symbol
{
    case signedOctet;
    case unsignedOctet;
    case signedShortInteger;
    case unsignedShortInteger;
    case signedLongInteger;
    case unsignedLongInteger;
    case signedLongLongInteger;
    case unsignedLongLongInteger;
    case decimal;
    case timestamp;
    case void;
    case bits;
    case shortString;
    case longString;
    case sequence;
    case table;

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

    public function pack(): Str
    {
        return match ($this) {
            self::signedOctet => Str::of('b'),
            self::unsignedOctet => Str::of('B'),
            self::signedShortInteger => Str::of('U'),
            self::unsignedShortInteger => Str::of('u'),
            self::signedLongInteger => Str::of('I'),
            self::unsignedLongInteger => Str::of('i'),
            self::signedLongLongInteger => Str::of('L'),
            self::unsignedLongLongInteger => Str::of('l'),
            self::decimal => Str::of('D'),
            self::timestamp => Str::of('T'),
            self::void => Str::of('V'),
            self::bits => Str::of('t'),
            self::shortString => Str::of('s'),
            self::longString => Str::of('S'),
            self::sequence => Str::of('A'),
            self::table => Str::of('F'),
        };
    }
}
