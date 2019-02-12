<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Protocol\v091;

use Innmind\AMQP\Transport\Frame\Method;
use Innmind\Immutable\{
    MapInterface,
    Map,
};

final class Methods
{
    private static $all;
    private static $classes;

    public static function get(string $method): Method
    {
        return self::all()->get($method);
    }

    public static function class(Method $method): string
    {
        return self::classes()->get($method->class());
    }

    public static function classId(string $class): int
    {
        return self::classes()
            ->filter(static function(int $id, string $name) use ($class): bool {
                return $name === $class;
            })
            ->key();
    }

    /**
     * @return MapInterface<string, Method>
     */
    public static function all(): MapInterface
    {
        return self::$all ?? self::$all = Map::of('string', Method::class)
            ('connection.start', new Method(10, 10))
            ('connection.start-ok', new Method(10, 11))
            ('connection.secure', new Method(10, 20))
            ('connection.secure-ok', new Method(10, 21))
            ('connection.tune', new Method(10, 30))
            ('connection.tune-ok', new Method(10, 31))
            ('connection.open', new Method(10, 40))
            ('connection.open-ok', new Method(10, 41))
            ('connection.close', new Method(10, 50))
            ('connection.close-ok', new Method(10, 51))
            ('channel.open', new Method(20, 10))
            ('channel.open-ok', new Method(20, 11))
            ('channel.flow', new Method(20, 20))
            ('channel.flow-ok', new Method(20, 21))
            ('channel.close', new Method(20, 40))
            ('channel.close-ok', new Method(20, 41))
            ('exchange.declare', new Method(40, 10))
            ('exchange.declare-ok', new Method(40, 11))
            ('exchange.delete', new Method(40, 20))
            ('exchange.delete-ok', new Method(40, 21))
            ('queue.declare', new Method(50, 10))
            ('queue.declare-ok', new Method(50, 11))
            ('queue.bind', new Method(50, 20))
            ('queue.bind-ok', new Method(50, 21))
            ('queue.unbind', new Method(50, 50))
            ('queue.unbind-ok', new Method(50, 51))
            ('queue.purge', new Method(50, 30))
            ('queue.purge-ok', new Method(50, 31))
            ('queue.delete', new Method(50, 40))
            ('queue.delete-ok', new Method(50, 41))
            ('basic.qos', new Method(60, 10))
            ('basic.qos-ok', new Method(60, 11))
            ('basic.consume', new Method(60, 20))
            ('basic.consume-ok', new Method(60, 21))
            ('basic.cancel', new Method(60, 30))
            ('basic.cancel-ok', new Method(60, 31))
            ('basic.publish', new Method(60, 40))
            ('basic.return', new Method(60, 50))
            ('basic.deliver', new Method(60, 60))
            ('basic.get', new Method(60, 70))
            ('basic.get-ok', new Method(60, 71))
            ('basic.get-empty', new Method(60, 72))
            ('basic.ack', new Method(60, 80))
            ('basic.reject', new Method(60, 90))
            ('basic.recover-async', new Method(60, 100))
            ('basic.recover', new Method(60, 110))
            ('basic.recover-ok', new Method(60, 111))
            ('tx.select', new Method(90, 10))
            ('tx.select-ok', new Method(90, 11))
            ('tx.commit', new Method(90, 20))
            ('tx.commit-ok', new Method(90, 21))
            ('tx.rollback', new Method(90, 30))
            ('tx.rollback-ok', new Method(90, 31));
    }

    /**
     * @return MapInterface<int, string>
     */
    public static function classes(): MapInterface
    {
        return self::$classes ?? self::$classes = Map::of('int', 'string')
            (10, 'connection')
            (20, 'channel')
            (40, 'exchange')
            (50, 'queue')
            (60, 'basic')
            (90, 'tx');
    }
}
