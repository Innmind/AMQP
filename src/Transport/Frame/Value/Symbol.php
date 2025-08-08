<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Value;

use Innmind\TimeContinuum\Clock;
use Innmind\IO\Frame;
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

    /**
     * @psalm-pure
     *
     * @return Frame<Unpacked>
     */
    public static function frame(Clock $clock, string $symbol): Frame
    {
        /** @var Frame<Unpacked> */
        return match ($symbol) {
            'b' => SignedOctet::frame(),
            'B' => UnsignedOctet::frame(),
            'U' => SignedShortInteger::frame(),
            'u' => UnsignedShortInteger::frame(),
            'I' => SignedLongInteger::frame(),
            'i' => UnsignedLongInteger::frame(),
            'L' => SignedLongLongInteger::frame(),
            'l' => UnsignedLongLongInteger::frame(),
            'D' => Decimal::frame(),
            'T' => Timestamp::frame($clock),
            'V' => VoidValue::frame(),
            't' => Bits::frame(),
            's' => ShortString::frame(),
            'S' => LongString::frame(),
            'A' => Sequence::frame($clock),
            'F' => Table::frame($clock),
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
