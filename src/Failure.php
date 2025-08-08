<?php
declare(strict_types = 1);

namespace Innmind\AMQP;

use Innmind\AMQP\Transport\Frame\Method;
use Innmind\Signals\Signal;
use Innmind\Immutable\Maybe;

final class Failure extends Exception\RuntimeException
{
    private function __construct(
        private object $failure,
    ) {
    }

    #[\NoDiscard]
    public static function toOpenConnection(): self
    {
        return new self(new Failure\ToOpenConnection);
    }

    #[\NoDiscard]
    public static function toOpenChannel(): self
    {
        return new self(new Failure\ToOpenChannel);
    }

    #[\NoDiscard]
    public static function toCloseChannel(): self
    {
        return new self(new Failure\ToCloseChannel);
    }

    #[\NoDiscard]
    public static function toCloseConnection(): self
    {
        return new self(new Failure\ToCloseConnection);
    }

    #[\NoDiscard]
    public static function toDeleteQueue(Model\Queue\Deletion $command): self
    {
        return new self(new Failure\ToDeleteQueue($command));
    }

    #[\NoDiscard]
    public static function toDeleteExchange(Model\Exchange\Deletion $command): self
    {
        return new self(new Failure\ToDeleteExchange($command));
    }

    #[\NoDiscard]
    public static function toDeclareQueue(Model\Queue\Declaration $command): self
    {
        return new self(new Failure\ToDeclareQueue($command));
    }

    #[\NoDiscard]
    public static function toDeclareExchange(Model\Exchange\Declaration $command): self
    {
        return new self(new Failure\ToDeclareExchange($command));
    }

    #[\NoDiscard]
    public static function toBind(Model\Queue\Binding $command): self
    {
        return new self(new Failure\ToBind($command));
    }

    #[\NoDiscard]
    public static function toUnbind(Model\Queue\Unbinding $command): self
    {
        return new self(new Failure\ToUnbind($command));
    }

    #[\NoDiscard]
    public static function toPurge(Model\Queue\Purge $command): self
    {
        return new self(new Failure\ToPurge($command));
    }

    #[\NoDiscard]
    public static function toAdjustQos(): self
    {
        return new self(new Failure\ToAdjustQos);
    }

    #[\NoDiscard]
    public static function toPublish(Model\Basic\Publish $command): self
    {
        return new self(new Failure\ToPublish($command));
    }

    #[\NoDiscard]
    public static function toGet(Model\Basic\Get $command): self
    {
        return new self(new Failure\ToGet($command));
    }

    #[\NoDiscard]
    public static function toConsume(Model\Basic\Consume $command): self
    {
        return new self(new Failure\ToConsume($command));
    }

    #[\NoDiscard]
    public static function toAck(string $queue): self
    {
        return new self(new Failure\ToAck($queue));
    }

    #[\NoDiscard]
    public static function toReject(string $queue): self
    {
        return new self(new Failure\ToReject($queue));
    }

    #[\NoDiscard]
    public static function toCancel(string $queue): self
    {
        return new self(new Failure\ToCancel($queue));
    }

    #[\NoDiscard]
    public static function toRecover(string $queue): self
    {
        return new self(new Failure\ToRecover($queue));
    }

    #[\NoDiscard]
    public static function toSelect(): self
    {
        return new self(new Failure\ToSelect);
    }

    #[\NoDiscard]
    public static function toCommit(): self
    {
        return new self(new Failure\ToCommit);
    }

    #[\NoDiscard]
    public static function toRollback(): self
    {
        return new self(new Failure\ToRollback);
    }

    #[\NoDiscard]
    public static function toSendFrame(): self
    {
        return new self(new Failure\ToSendFrame);
    }

    #[\NoDiscard]
    public static function toReadFrame(): self
    {
        return new self(new Failure\ToReadFrame);
    }

    #[\NoDiscard]
    public static function toReadMessage(): self
    {
        return new self(new Failure\ToReadMessage);
    }

    #[\NoDiscard]
    public static function unexpectedFrame(): self
    {
        return new self(new Failure\UnexpectedFrame);
    }

    /**
     * @param int<0, 65535> $code
     * @param Maybe<Method> $method
     */
    #[\NoDiscard]
    public static function closedByServer(
        string $message,
        int $code,
        Maybe $method,
    ): self {
        return new self(new Failure\ClosedByServer($message, $code, $method));
    }

    #[\NoDiscard]
    public static function closedBySignal(Signal $signal): self
    {
        return new self(new Failure\ClosedBySignal($signal));
    }

    #[\NoDiscard]
    public function unwrap(): object
    {
        return $this->failure;
    }

    #[\NoDiscard]
    public function kind(): Failure\Kind
    {
        /** @psalm-suppress MixedMethodCall,MixedReturnStatement */
        return $this->failure->kind();
    }
}
