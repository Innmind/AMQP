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
        private Failure\Kind $kind,
        ?\Throwable $previous = null,
    ) {
        parent::__construct('', 0, $previous);
    }

    /**
     * @return callable(\Throwable): \Throwable
     */
    public static function as(self $failure): callable
    {
        return static fn(\Throwable $e) => new self(
            $failure->failure,
            $failure->kind,
            $e,
        );
    }

    #[\NoDiscard]
    public static function toOpenConnection(): self
    {
        return new self(
            new Failure\ToOpenConnection,
            Failure\Kind::toOpenConnection,
        );
    }

    #[\NoDiscard]
    public static function toOpenChannel(): self
    {
        return new self(
            new Failure\ToOpenChannel,
            Failure\Kind::toOpenChannel,
        );
    }

    #[\NoDiscard]
    public static function toCloseChannel(): self
    {
        return new self(
            new Failure\ToCloseChannel,
            Failure\Kind::toCloseChannel,
        );
    }

    #[\NoDiscard]
    public static function toCloseConnection(): self
    {
        return new self(
            new Failure\ToCloseConnection,
            Failure\Kind::toCloseConnection,
        );
    }

    #[\NoDiscard]
    public static function toDeleteQueue(Model\Queue\Deletion $command): self
    {
        return new self(
            new Failure\ToDeleteQueue($command),
            Failure\Kind::toDeleteQueue,
        );
    }

    #[\NoDiscard]
    public static function toDeleteExchange(Model\Exchange\Deletion $command): self
    {
        return new self(
            new Failure\ToDeleteExchange($command),
            Failure\Kind::toDeleteExchange,
        );
    }

    #[\NoDiscard]
    public static function toDeclareQueue(Model\Queue\Declaration $command): self
    {
        return new self(
            new Failure\ToDeclareQueue($command),
            Failure\Kind::toDeclareQueue,
        );
    }

    #[\NoDiscard]
    public static function toDeclareExchange(Model\Exchange\Declaration $command): self
    {
        return new self(
            new Failure\ToDeclareExchange($command),
            Failure\Kind::toDeclareExchange,
        );
    }

    #[\NoDiscard]
    public static function toBind(Model\Queue\Binding $command): self
    {
        return new self(
            new Failure\ToBind($command),
            Failure\Kind::toBind,
        );
    }

    #[\NoDiscard]
    public static function toUnbind(Model\Queue\Unbinding $command): self
    {
        return new self(
            new Failure\ToUnbind($command),
            Failure\Kind::toUnbind,
        );
    }

    #[\NoDiscard]
    public static function toPurge(Model\Queue\Purge $command): self
    {
        return new self(
            new Failure\ToPurge($command),
            Failure\Kind::toPurge,
        );
    }

    #[\NoDiscard]
    public static function toAdjustQos(): self
    {
        return new self(
            new Failure\ToAdjustQos,
            Failure\Kind::toAdjustQos,
        );
    }

    #[\NoDiscard]
    public static function toPublish(Model\Basic\Publish $command): self
    {
        return new self(
            new Failure\ToPublish($command),
            Failure\Kind::toPublish,
        );
    }

    #[\NoDiscard]
    public static function toGet(Model\Basic\Get $command): self
    {
        return new self(
            new Failure\ToGet($command),
            Failure\Kind::toGet,
        );
    }

    #[\NoDiscard]
    public static function toConsume(Model\Basic\Consume $command): self
    {
        return new self(
            new Failure\ToConsume($command),
            Failure\Kind::toConsume,
        );
    }

    #[\NoDiscard]
    public static function toAck(string $queue): self
    {
        return new self(
            new Failure\ToAck($queue),
            Failure\Kind::toAck,
        );
    }

    #[\NoDiscard]
    public static function toReject(string $queue): self
    {
        return new self(
            new Failure\ToReject($queue),
            Failure\Kind::toReject,
        );
    }

    #[\NoDiscard]
    public static function toCancel(string $queue): self
    {
        return new self(
            new Failure\ToCancel($queue),
            Failure\Kind::toCancel,
        );
    }

    #[\NoDiscard]
    public static function toRecover(string $queue): self
    {
        return new self(
            new Failure\ToRecover($queue),
            Failure\Kind::toRecover,
        );
    }

    #[\NoDiscard]
    public static function toSelect(): self
    {
        return new self(
            new Failure\ToSelect,
            Failure\Kind::toSelect,
        );
    }

    #[\NoDiscard]
    public static function toCommit(): self
    {
        return new self(
            new Failure\ToCommit,
            Failure\Kind::toCommit,
        );
    }

    #[\NoDiscard]
    public static function toRollback(): self
    {
        return new self(
            new Failure\ToRollback,
            Failure\Kind::toRollback,
        );
    }

    #[\NoDiscard]
    public static function toSendFrame(): self
    {
        return new self(
            new Failure\ToSendFrame,
            Failure\Kind::toSendFrame,
        );
    }

    #[\NoDiscard]
    public static function toReadFrame(): self
    {
        return new self(
            new Failure\ToReadFrame,
            Failure\Kind::toReadFrame,
        );
    }

    #[\NoDiscard]
    public static function toReadMessage(): self
    {
        return new self(
            new Failure\ToReadMessage,
            Failure\Kind::toReadMessage,
        );
    }

    #[\NoDiscard]
    public static function unexpectedFrame(): self
    {
        return new self(
            new Failure\UnexpectedFrame,
            Failure\Kind::unexpectedFrame,
        );
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
        return new self(
            new Failure\ClosedByServer($message, $code, $method),
            Failure\Kind::closedByServer,
        );
    }

    #[\NoDiscard]
    public static function closedBySignal(Signal $signal): self
    {
        return new self(
            new Failure\ClosedBySignal($signal),
            Failure\Kind::closedBySignal,
        );
    }

    #[\NoDiscard]
    public function unwrap(): object
    {
        return $this->failure;
    }

    #[\NoDiscard]
    public function kind(): Failure\Kind
    {
        return $this->kind;
    }
}
