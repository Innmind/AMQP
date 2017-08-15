<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Value;

use Innmind\Immutable\{
    Map,
    Pair
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
        if (!is_null(self::$symbols)) {
            return;
        }

        self::$symbols = (new Map('string', 'string'))
            ->put('b', SignedOctet::class)
            ->put('B', UnsignedOctet::class)
            ->put('U', SignedShortInteger::class)
            ->put('u', UnsignedShortInteger::class)
            ->put('I', SignedLongInteger::class)
            ->put('i', UnsignedLongInteger::class)
            ->put('L', SignedLongLongInteger::class)
            ->put('l', UnsignedLongLongInteger::class)
            ->put('D', Decimal::class)
            ->put('T', Timestamp::class)
            ->put('V', Void::class)
            ->put('t', Bits::class)
            ->put('s', ShortString::class)
            ->put('S', LongString::class)
            ->put('A', Sequence::class)
            ->put('F', Table::class);
        self::$classes = self::$symbols->map(static function(string $symbol, string $class): Pair {
            return new Pair($class, $symbol);
        });
    }
}
