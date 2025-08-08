<?php
declare(strict_types = 1);

namespace Innmind\AMQP;

use Innmind\AMQP\Transport\Frame\Method;
use Innmind\Signals\Signal;
use Innmind\Immutable\Maybe;

/**
 * @psalm-immutable
 */
abstract class Failure
{
    #[\NoDiscard]
    final public static function toOpenConnection(): self
    {
        return new Failure\ToOpenConnection;
    }

    #[\NoDiscard]
    final public static function toOpenChannel(): self
    {
        return new Failure\ToOpenChannel;
    }

    #[\NoDiscard]
    final public static function toCloseChannel(): self
    {
        return new Failure\ToCloseChannel;
    }

    #[\NoDiscard]
    final public static function toCloseConnection(): self
    {
        return new Failure\ToCloseConnection;
    }

    #[\NoDiscard]
    final public static function toDeleteQueue(Model\Queue\Deletion $command): self
    {
        return new Failure\ToDeleteQueue($command);
    }

    #[\NoDiscard]
    final public static function toDeleteExchange(Model\Exchange\Deletion $command): self
    {
        return new Failure\ToDeleteExchange($command);
    }

    #[\NoDiscard]
    final public static function toDeclareQueue(Model\Queue\Declaration $command): self
    {
        return new Failure\ToDeclareQueue($command);
    }

    #[\NoDiscard]
    final public static function toDeclareExchange(Model\Exchange\Declaration $command): self
    {
        return new Failure\ToDeclareExchange($command);
    }

    #[\NoDiscard]
    final public static function toBind(Model\Queue\Binding $command): self
    {
        return new Failure\ToBind($command);
    }

    #[\NoDiscard]
    final public static function toUnbind(Model\Queue\Unbinding $command): self
    {
        return new Failure\ToUnbind($command);
    }

    #[\NoDiscard]
    final public static function toPurge(Model\Queue\Purge $command): self
    {
        return new Failure\ToPurge($command);
    }

    #[\NoDiscard]
    final public static function toAdjustQos(): self
    {
        return new Failure\ToAdjustQos;
    }

    #[\NoDiscard]
    final public static function toPublish(Model\Basic\Publish $command): self
    {
        return new Failure\ToPublish($command);
    }

    #[\NoDiscard]
    final public static function toGet(Model\Basic\Get $command): self
    {
        return new Failure\ToGet($command);
    }

    #[\NoDiscard]
    final public static function toConsume(Model\Basic\Consume $command): self
    {
        return new Failure\ToConsume($command);
    }

    #[\NoDiscard]
    final public static function toAck(string $queue): self
    {
        return new Failure\ToAck($queue);
    }

    #[\NoDiscard]
    final public static function toReject(string $queue): self
    {
        return new Failure\ToReject($queue);
    }

    #[\NoDiscard]
    final public static function toCancel(string $queue): self
    {
        return new Failure\ToCancel($queue);
    }

    #[\NoDiscard]
    final public static function toRecover(string $queue): self
    {
        return new Failure\ToRecover($queue);
    }

    #[\NoDiscard]
    final public static function toSelect(): self
    {
        return new Failure\ToSelect;
    }

    #[\NoDiscard]
    final public static function toCommit(): self
    {
        return new Failure\ToCommit;
    }

    #[\NoDiscard]
    final public static function toRollback(): self
    {
        return new Failure\ToRollback;
    }

    #[\NoDiscard]
    final public static function toSendFrame(): self
    {
        return new Failure\ToSendFrame;
    }

    #[\NoDiscard]
    final public static function toReadFrame(): self
    {
        return new Failure\ToReadFrame;
    }

    #[\NoDiscard]
    final public static function toReadMessage(): self
    {
        return new Failure\ToReadMessage;
    }

    #[\NoDiscard]
    final public static function unexpectedFrame(): self
    {
        return new Failure\UnexpectedFrame;
    }

    /**
     * @param int<0, 65535> $code
     * @param Maybe<Method> $method
     */
    #[\NoDiscard]
    final public static function closedByServer(
        string $message,
        int $code,
        Maybe $method,
    ): self {
        return new Failure\ClosedByServer($message, $code, $method);
    }

    #[\NoDiscard]
    final public static function closedBySignal(Signal $signal): self
    {
        return new Failure\ClosedBySignal($signal);
    }

    #[\NoDiscard]
    abstract public function kind(): Failure\Kind;
}
