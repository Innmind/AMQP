<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\Value;
use Innmind\Immutable\{
    Map,
    Pair,
};

final class Symbols
{
    private static ?self $instance = null;

    /** @var Map<string, class-string<Value>> */
    private Map $symbols;
    /** @var Map<class-string<Value>, string> */
    private Map $classes;

    /**
     * @param Map<string, class-string<Value>> $symbols
     * @param Map<class-string<Value>, string> $classes
     */
    private function __construct(Map $symbols, Map $classes)
    {
        $this->symbols = $symbols;
        $this->classes = $classes;
    }

    /**
     * @param class-string<Value> $class
     */
    public static function symbol(string $class): string
    {
        return self::init()->classes->get($class);
    }

    /**
     * @return class-string<Value>
     */
    public static function class(string $symbol): string
    {
        return self::init()->symbols->get($symbol);
    }

    private static function init(): self
    {
        if (!\is_null(self::$instance)) {
            return self::$instance;
        }

        /** @var Map<string, class-string<Value>> */
        $symbols = Map::of('string', 'string');
        $symbols = $symbols
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
            ->put('V', VoidValue::class)
            ->put('t', Bits::class)
            ->put('s', ShortString::class)
            ->put('S', LongString::class)
            ->put('A', Sequence::class)
            ->put('F', Table::class);
        /**
         * @psalm-suppress ArgumentTypeCoercion
         * @var Map<class-string<Value>, string>
         */
        $classes = $symbols->map(static function(string $symbol, string $class): Pair {
            return new Pair($class, $symbol);
        });

        return self::$instance = new self($symbols, $classes);
    }
}
