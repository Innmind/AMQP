<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Protocol;

use Innmind\AMQP\Transport\Frame\{
    Method,
    Value,
    Value\Bits,
    Value\LongString,
    Value\ShortString,
    Value\UnsignedLongInteger,
    Value\UnsignedLongLongInteger,
    Value\UnsignedOctet,
    Value\UnsignedShortInteger,
    Value\Table,
    Value\Unpacked,
};
use Innmind\TimeContinuum\Clock;
use Innmind\IO\Readable\Frame;
use Innmind\Immutable\Sequence;

/**
 * @internal
 */
final class Reader
{
    private Clock $clock;

    public function __construct(Clock $clock)
    {
        $this->clock = $clock;
    }

    /**
     * @return Frame<Sequence<Value>>
     */
    public function __invoke(Method $method): Frame
    {
        return match ($method) {
            Method::basicQosOk => $this->basicQosOk(),
            Method::basicConsumeOk => $this->basicConsumeOk(),
            Method::basicCancelOk => $this->basicCancelOk(),
            Method::basicReturn => $this->basicReturn(),
            Method::basicDeliver => $this->basicDeliver(),
            Method::basicGetOk => $this->basicGetOk(),
            Method::basicGetEmpty => $this->basicGetEmpty(),
            Method::basicRecoverOk => $this->basicRecoverOk(),
            Method::channelOpenOk => $this->channelOpenOk(),
            Method::channelFlow => $this->channelFlow(),
            Method::channelFlowOk => $this->channelFlowOk(),
            Method::channelClose => $this->channelClose(),
            Method::channelCloseOk => $this->channelCloseOk(),
            Method::connectionStart => $this->connectionStart(),
            Method::connectionSecure => $this->connectionSecure(),
            Method::connectionTune => $this->connectionTune(),
            Method::connectionOpenOk => $this->connectionOpenOk(),
            Method::connectionClose => $this->connectionClose(),
            Method::connectionCloseOk => $this->connectionCloseOk(),
            Method::exchangeDeclareOk => $this->exchangeDeclareOk(),
            Method::exchangeDeleteOk => $this->exchangeDeleteOk(),
            Method::queueDeclareOk => $this->queueDeclareOk(),
            Method::queueBindOk => $this->queueBindOk(),
            Method::queueUnbindOk => $this->queueUnbindOk(),
            Method::queuePurgeOk => $this->queuePurgeOk(),
            Method::queueDeleteOk => $this->queueDeleteOk(),
            Method::transactionSelectOk => $this->transactionSelectOk(),
            Method::transactionCommitOk => $this->transactionCommitOk(),
            Method::transactionRollbackOk => $this->transactionRollbackOk(),
            Method::basicAck,
            Method::basicCancel,
            Method::basicConsume,
            Method::basicGet,
            Method::basicPublish,
            Method::basicQos,
            Method::basicRecover,
            Method::basicRecoverAsync,
            Method::basicReject,
            Method::channelOpen,
            Method::connectionOpen,
            Method::connectionSecureOk,
            Method::connectionStartOk,
            Method::connectionTuneOk,
            Method::exchangeDeclare,
            Method::exchangeDelete,
            Method::queueBind,
            Method::queueDeclare,
            Method::queueDelete,
            Method::queuePurge,
            Method::queueUnbind,
            Method::transactionCommit,
            Method::transactionRollback,
            Method::transactionSelect => throw new \LogicException('Server should never send this method'),
        };
    }

    /**
     * @return Frame<Sequence<Value>>
     */
    private function basicQosOk(): Frame
    {
        /** @var Frame<Sequence<Value>> */
        return Frame\NoOp::of(Sequence::of()); // no arguments
    }

    /**
     * @return Frame<Sequence<Value>>
     */
    private function basicConsumeOk(): Frame
    {
        /** @var Frame<Sequence<Value>> */
        return ShortString::frame()
            ->map(static fn($value) => $value->unwrap())
            ->map(Sequence::of(...)); // consumer tag
    }

    /**
     * @return Frame<Sequence<Value>>
     */
    private function basicCancelOk(): Frame
    {
        /** @var Frame<Sequence<Value>> */
        return ShortString::frame()
            ->map(static fn($value) => $value->unwrap())
            ->map(Sequence::of(...)); // consumer tag
    }

    /**
     * @return Frame<Sequence<Value>>
     */
    private function basicReturn(): Frame
    {
        /** @psalm-suppress NamedArgumentNotAllowed */
        return Frame\Composite::of(
            static fn(Unpacked ...$values) => Sequence::of(...$values)->map(
                static fn($unpacked) => $unpacked->unwrap(),
            ),
            UnsignedShortInteger::frame(), // reply code
            ShortString::frame(), // reply text
            ShortString::frame(), // exchange
            ShortString::frame(), // routing key
        );
    }

    /**
     * @return Frame<Sequence<Value>>
     */
    private function basicDeliver(): Frame
    {
        /** @psalm-suppress NamedArgumentNotAllowed */
        return Frame\Composite::of(
            static fn(Unpacked ...$values) => Sequence::of(...$values)->map(
                static fn($unpacked) => $unpacked->unwrap(),
            ),
            ShortString::frame(), // consumer tag
            UnsignedLongLongInteger::frame(), // delivery tag
            Bits::frame(), // redelivered
            ShortString::frame(), // exchange
            ShortString::frame(), // routing key
        );
    }

    /**
     * @return Frame<Sequence<Value>>
     */
    private function basicGetOk(): Frame
    {
        /** @psalm-suppress NamedArgumentNotAllowed */
        return Frame\Composite::of(
            static fn(Unpacked ...$values) => Sequence::of(...$values)->map(
                static fn($unpacked) => $unpacked->unwrap(),
            ),
            UnsignedLongLongInteger::frame(), // delivery tag
            Bits::frame(), // redelivered
            ShortString::frame(), // exchange
            ShortString::frame(), // routing key
            UnsignedLongInteger::frame(), // message count
        );
    }

    /**
     * @return Frame<Sequence<Value>>
     */
    private function basicGetEmpty(): Frame
    {
        /** @var Frame<Sequence<Value>> */
        return ShortString::frame()
            ->map(static fn($value) => $value->unwrap())
            ->map(Sequence::of(...)); // reserved
    }

    /**
     * @return Frame<Sequence<Value>>
     */
    private function basicRecoverOk(): Frame
    {
        /** @var Frame<Sequence<Value>> */
        return Frame\NoOp::of(Sequence::of()); // no arguments
    }

    /**
     * @return Frame<Sequence<Value>>
     */
    private function channelOpenOk(): Frame
    {
        /** @var Frame<Sequence<Value>> */
        return LongString::frame()
            ->map(static fn($value) => $value->unwrap())
            ->map(Sequence::of(...)); // reserved
    }

    /**
     * @return Frame<Sequence<Value>>
     */
    private function channelFlow(): Frame
    {
        /** @var Frame<Sequence<Value>> */
        return Bits::frame()
            ->map(static fn($value) => $value->unwrap())
            ->map(Sequence::of(...)); // active
    }

    /**
     * @return Frame<Sequence<Value>>
     */
    private function channelFlowOk(): Frame
    {
        /** @var Frame<Sequence<Value>> */
        return Bits::frame()
            ->map(static fn($value) => $value->unwrap())
            ->map(Sequence::of(...)); // active
    }

    /**
     * @return Frame<Sequence<Value>>
     */
    private function channelClose(): Frame
    {
        /** @psalm-suppress NamedArgumentNotAllowed */
        return Frame\Composite::of(
            static fn(Unpacked ...$values) => Sequence::of(...$values)->map(
                static fn($unpacked) => $unpacked->unwrap(),
            ),
            UnsignedShortInteger::frame(), // reply code
            ShortString::frame(), // reply text
            UnsignedShortInteger::frame(), // failing class id
            UnsignedShortInteger::frame(), // failing method id
        );
    }

    /**
     * @return Frame<Sequence<Value>>
     */
    private function channelCloseOk(): Frame
    {
        /** @var Frame<Sequence<Value>> */
        return Frame\NoOp::of(Sequence::of()); // no arguments
    }

    /**
     * @return Frame<Sequence<Value>>
     */
    private function connectionStart(): Frame
    {
        /** @psalm-suppress NamedArgumentNotAllowed */
        return Frame\Composite::of(
            static fn(Unpacked ...$values) => Sequence::of(...$values)->map(
                static fn($unpacked) => $unpacked->unwrap(),
            ),
            UnsignedOctet::frame(), // major version
            UnsignedOctet::frame(), // minor version
            Table::frame($this->clock), // server properties
            LongString::frame(), // mechanisms
            LongString::frame(), // locales
        );
    }

    /**
     * @return Frame<Sequence<Value>>
     */
    private function connectionSecure(): Frame
    {
        /** @var Frame<Sequence<Value>> */
        return LongString::frame()
            ->map(static fn($value) => $value->unwrap())
            ->map(Sequence::of(...)); // challenge
    }

    /**
     * @return Frame<Sequence<Value>>
     */
    private function connectionTune(): Frame
    {
        /** @psalm-suppress NamedArgumentNotAllowed */
        return Frame\Composite::of(
            static fn(Unpacked ...$values) => Sequence::of(...$values)->map(
                static fn($unpacked) => $unpacked->unwrap(),
            ),
            UnsignedShortInteger::frame(), // max channels
            UnsignedLongInteger::frame(), // max frame size
            UnsignedShortInteger::frame(), // heartbeat delay
        );
    }

    /**
     * @return Frame<Sequence<Value>>
     */
    private function connectionOpenOk(): Frame
    {
        /** @var Frame<Sequence<Value>> */
        return ShortString::frame()
            ->map(static fn($value) => $value->unwrap())
            ->map(Sequence::of(...)); // known hosts
    }

    /**
     * @return Frame<Sequence<Value>>
     */
    private function connectionClose(): Frame
    {
        /** @psalm-suppress NamedArgumentNotAllowed */
        return Frame\Composite::of(
            static fn(Unpacked ...$values) => Sequence::of(...$values)->map(
                static fn($unpacked) => $unpacked->unwrap(),
            ),
            UnsignedShortInteger::frame(), // reply code
            ShortString::frame(), // reply text
            UnsignedShortInteger::frame(), // failing class id
            UnsignedShortInteger::frame(), // failing method id
        );
    }

    /**
     * @return Frame<Sequence<Value>>
     */
    private function connectionCloseOk(): Frame
    {
        /** @var Frame<Sequence<Value>> */
        return Frame\NoOp::of(Sequence::of()); // no arguments
    }

    /**
     * @return Frame<Sequence<Value>>
     */
    private function exchangeDeclareOk(): Frame
    {
        /** @var Frame<Sequence<Value>> */
        return Frame\NoOp::of(Sequence::of()); // no arguments
    }

    /**
     * @return Frame<Sequence<Value>>
     */
    private function exchangeDeleteOk(): Frame
    {
        /** @var Frame<Sequence<Value>> */
        return Frame\NoOp::of(Sequence::of()); // no arguments
    }

    /**
     * @return Frame<Sequence<Value>>
     */
    private function queueDeclareOk(): Frame
    {
        /** @psalm-suppress NamedArgumentNotAllowed */
        return Frame\Composite::of(
            static fn(Unpacked ...$values) => Sequence::of(...$values)->map(
                static fn($unpacked) => $unpacked->unwrap(),
            ),
            ShortString::frame(), // queue
            UnsignedLongInteger::frame(), // message count
            UnsignedLongInteger::frame(), // consumer count
        );
    }

    /**
     * @return Frame<Sequence<Value>>
     */
    private function queueBindOk(): Frame
    {
        /** @var Frame<Sequence<Value>> */
        return Frame\NoOp::of(Sequence::of()); // no arguments
    }

    /**
     * @return Frame<Sequence<Value>>
     */
    private function queueUnbindOk(): Frame
    {
        /** @var Frame<Sequence<Value>> */
        return Frame\NoOp::of(Sequence::of()); // no arguments
    }

    /**
     * @return Frame<Sequence<Value>>
     */
    private function queuePurgeOk(): Frame
    {
        /** @var Frame<Sequence<Value>> */
        return UnsignedLongInteger::frame()
            ->map(static fn($value) => $value->unwrap())
            ->map(Sequence::of(...)); // message count
    }

    /**
     * @return Frame<Sequence<Value>>
     */
    private function queueDeleteOk(): Frame
    {
        /** @var Frame<Sequence<Value>> */
        return UnsignedLongInteger::frame()
            ->map(static fn($value) => $value->unwrap())
            ->map(Sequence::of(...)); // message count
    }

    /**
     * @return Frame<Sequence<Value>>
     */
    private function transactionSelectOk(): Frame
    {
        /** @var Frame<Sequence<Value>> */
        return Frame\NoOp::of(Sequence::of()); // no arguments
    }

    /**
     * @return Frame<Sequence<Value>>
     */
    private function transactionCommitOk(): Frame
    {
        /** @var Frame<Sequence<Value>> */
        return Frame\NoOp::of(Sequence::of()); // no arguments
    }

    /**
     * @return Frame<Sequence<Value>>
     */
    private function transactionRollbackOk(): Frame
    {
        /** @var Frame<Sequence<Value>> */
        return Frame\NoOp::of(Sequence::of()); // no arguments
    }
}
