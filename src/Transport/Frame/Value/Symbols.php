<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Value;

use Innmind\Immutable\{
    Map,
    Pair,
};

final class Symbols
{
    private static $symbols;
    private static $classes;

    private function __construct()
    {
    }

    public static function symbol(string $class): string
    {
        self::init();

        return self::$classes->get($class);
    }

    public static function class(string $symbol): string
    {
        self::init();

        return self::$symbols->get($symbol);
    }

    private static function init(): void
    {
        if (!\is_null(self::$symbols)) {
            return;
        }

        self::$symbols = Map::of('string', 'string')
            ('b', SignedOctet::class)
            ('B', UnsignedOctet::class)
            ('U', SignedShortInteger::class)
            ('u', UnsignedShortInteger::class)
            ('I', SignedLongInteger::class)
            ('i', UnsignedLongInteger::class)
            ('L', SignedLongLongInteger::class)
            ('l', UnsignedLongLongInteger::class)
            ('D', Decimal::class)
            ('T', Timestamp::class)
            ('V', VoidValue::class)
            ('t', Bits::class)
            ('s', ShortString::class)
            ('S', LongString::class)
            ('A', Sequence::class)
            ('F', Table::class);
        self::$classes = self::$symbols->map(static function(string $symbol, string $class): Pair {
            return new Pair($class, $symbol);
        });
    }
}
