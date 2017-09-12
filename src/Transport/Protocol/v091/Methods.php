<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Protocol\v091;

use Innmind\AMQP\Transport\Frame\Method;
use Innmind\Immutable\{
    MapInterface,
    Map
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
        if (!self::$all instanceof MapInterface) {
            self::$all = (new Map('string', Method::class))
                ->put('connection.start', new Method(10, 10))
                ->put('connection.start-ok', new Method(10, 11))
                ->put('connection.secure', new Method(10, 20))
                ->put('connection.secure-ok', new Method(10, 21))
                ->put('connection.tune', new Method(10, 30))
                ->put('connection.tune-ok', new Method(10, 31))
                ->put('connection.open', new Method(10, 40))
                ->put('connection.open-ok', new Method(10, 41))
                ->put('connection.close', new Method(10, 50))
                ->put('connection.close-ok', new Method(10, 51))
                ->put('channel.open', new Method(20, 10))
                ->put('channel.open-ok', new Method(20, 11))
                ->put('channel.flow', new Method(20, 20))
                ->put('channel.flow-ok', new Method(20, 21))
                ->put('channel.close', new Method(20, 40))
                ->put('channel.close-ok', new Method(20, 41))
                ->put('exchange.declare', new Method(40, 10))
                ->put('exchange.declare-ok', new Method(40, 11))
                ->put('exchange.delete', new Method(40, 20))
                ->put('exchange.delete-ok', new Method(40, 21))
                ->put('queue.declare', new Method(50, 10))
                ->put('queue.declare-ok', new Method(50, 11))
                ->put('queue.bind', new Method(50, 20))
                ->put('queue.bind-ok', new Method(50, 21))
                ->put('queue.unbind', new Method(50, 50))
                ->put('queue.unbind-ok', new Method(50, 51))
                ->put('queue.purge', new Method(50, 30))
                ->put('queue.purge-ok', new Method(50, 31))
                ->put('queue.delete', new Method(50, 40))
                ->put('queue.delete-ok', new Method(50, 41))
                ->put('basic.qos', new Method(60, 10))
                ->put('basic.qos-ok', new Method(60, 11))
                ->put('basic.consume', new Method(60, 20))
                ->put('basic.consume-ok', new Method(60, 21))
                ->put('basic.cancel', new Method(60, 30))
                ->put('basic.cancel-ok', new Method(60, 31))
                ->put('basic.publish', new Method(60, 40))
                ->put('basic.return', new Method(60, 50))
                ->put('basic.deliver', new Method(60, 60))
                ->put('basic.get', new Method(60, 70))
                ->put('basic.get-ok', new Method(60, 71))
                ->put('basic.get-empty', new Method(60, 72))
                ->put('basic.ack', new Method(60, 80))
                ->put('basic.reject', new Method(60, 90))
                ->put('basic.recover-async', new Method(60, 100))
                ->put('basic.recover', new Method(60, 110))
                ->put('basic.recover-ok', new Method(60, 111))
                ->put('tx.select', new Method(90, 10))
                ->put('tx.select-ok', new Method(90, 11))
                ->put('tx.commit', new Method(90, 20))
                ->put('tx.commit-ok', new Method(90, 21))
                ->put('tx.rollback', new Method(90, 30))
                ->put('tx.rollback-ok', new Method(90, 31));
        }

        return self::$all;
    }

    /**
     * @return MapInterface<int, string>
     */
    public static function classes(): MapInterface
    {
        if (is_null(self::$classes)) {
            self::$classes = (new Map('int', 'string'))
                ->put(10, 'connection')
                ->put(20, 'channel')
                ->put(40, 'exchange')
                ->put(50, 'queue')
                ->put(60, 'basic')
                ->put(90, 'tx');
        }

        return self::$classes;
    }
}
