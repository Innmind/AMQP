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
    final public static function toOpenConnection(): self
    {
        return new Failure\ToOpenConnection;
    }

    final public static function toOpenChannel(): self
    {
        return new Failure\ToOpenChannel;
    }

    final public static function toCloseChannel(): self
    {
        return new Failure\ToCloseChannel;
    }

    final public static function toCloseConnection(): self
    {
        return new Failure\ToCloseConnection;
    }

    final public static function toDeleteQueue(Model\Queue\Deletion $command): self
    {
        return new Failure\ToDeleteQueue($command);
    }

    final public static function toDeleteExchange(Model\Exchange\Deletion $command): self
    {
        return new Failure\ToDeleteExchange($command);
    }

    final public static function toDeclareQueue(Model\Queue\Declaration $command): self
    {
        return new Failure\ToDeclareQueue($command);
    }

    final public static function toDeclareExchange(Model\Exchange\Declaration $command): self
    {
        return new Failure\ToDeclareExchange($command);
    }

    final public static function toBind(Model\Queue\Binding $command): self
    {
        return new Failure\ToBind($command);
    }

    final public static function toUnbind(Model\Queue\Unbinding $command): self
    {
        return new Failure\ToUnbind($command);
    }

    final public static function toPurge(Model\Queue\Purge $command): self
    {
        return new Failure\ToPurge($command);
    }

    final public static function toAdjustQos(): self
    {
        return new Failure\ToAdjustQos;
    }

    final public static function toPublish(Model\Basic\Publish $command): self
    {
        return new Failure\ToPublish($command);
    }

    final public static function toGet(Model\Basic\Get $command): self
    {
        return new Failure\ToGet($command);
    }

    final public static function toConsume(Model\Basic\Consume $command): self
    {
        return new Failure\ToConsume($command);
    }

    final public static function toAck(string $queue): self
    {
        return new Failure\ToAck($queue);
    }

    final public static function toReject(string $queue): self
    {
        return new Failure\ToReject($queue);
    }

    final public static function toCancel(string $queue): self
    {
        return new Failure\ToCancel($queue);
    }

    final public static function toRecover(string $queue): self
    {
        return new Failure\ToRecover($queue);
    }

    final public static function toSelect(): self
    {
        return new Failure\ToSelect;
    }

    final public static function toCommit(): self
    {
        return new Failure\ToCommit;
    }

    final public static function toRollback(): self
    {
        return new Failure\ToRollback;
    }

    final public static function toSendFrame(): self
    {
        return new Failure\ToSendFrame;
    }

    final public static function toReadFrame(): self
    {
        return new Failure\ToReadFrame;
    }

    final public static function toReadMessage(): self
    {
        return new Failure\ToReadMessage;
    }

    final public static function unexpectedFrame(): self
    {
        return new Failure\UnexpectedFrame;
    }

    /**
     * @param int<0, 65535> $code
     * @param Maybe<Method> $method
     */
    final public static function closedByServer(
        string $message,
        int $code,
        Maybe $method,
    ): self {
        return new Failure\ClosedByServer($message, $code, $method);
    }

    final public static function closedBySignal(Signal $signal): self
    {
        return new Failure\ClosedBySignal($signal);
    }

    abstract public function kind(): Failure\Kind;
}
