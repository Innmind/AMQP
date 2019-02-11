<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\Value\{
    Symbols,
    SignedOctet,
    UnsignedOctet,
    SignedShortInteger,
    UnsignedShortInteger,
    SignedLongInteger,
    UnsignedLongInteger,
    SignedLongLongInteger,
    UnsignedLongLongInteger,
    Decimal,
    Timestamp,
    VoidValue,
    Bits,
    ShortString,
    LongString,
    Sequence,
    Table,
};
use PHPUnit\Framework\TestCase;

class SymbolsTest extends TestCase
{
    /**
     * @dataProvider cases
     */
    public function testValues($symbol, $class)
    {
        $this->assertSame($class, Symbols::class($symbol));
        $this->assertSame($symbol, Symbols::symbol($class));
    }

    public function cases(): array
    {
        return [
            ['b', SignedOctet::class],
            ['B', UnsignedOctet::class],
            ['U', SignedShortInteger::class],
            ['u', UnsignedShortInteger::class],
            ['I', SignedLongInteger::class],
            ['i', UnsignedLongInteger::class],
            ['L', SignedLongLongInteger::class],
            ['l', UnsignedLongLongInteger::class],
            ['D', Decimal::class],
            ['T', Timestamp::class],
            ['V', VoidValue::class],
            ['t', Bits::class],
            ['s', ShortString::class],
            ['S', LongString::class],
            ['A', Sequence::class],
            ['F', Table::class],
        ];
    }
}
