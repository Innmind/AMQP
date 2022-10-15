<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Protocol;

use Innmind\AMQP\Transport\Frame\{
    Method,
    Value,
    Visitor\ChunkArguments,
    Value\Bits,
    Value\LongString,
    Value\ShortString,
    Value\UnsignedLongInteger,
    Value\UnsignedLongLongInteger,
    Value\UnsignedOctet,
    Value\UnsignedShortInteger,
    Value\Table,
};
use Innmind\Stream\Readable;
use Innmind\Immutable\{
    Sequence,
    Maybe,
};

final class Reader
{
    /**
     * @return Maybe<Sequence<Value>>
     */
    public function __invoke(Method $method, Readable $arguments): Maybe
    {
        $chunk = match ($method) {
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

        return $chunk($arguments);
    }

    private function basicQosOk(): ChunkArguments
    {
        return new ChunkArguments; // no arguments
    }

    private function basicConsumeOk(): ChunkArguments
    {
        /** @psalm-suppress InvalidArgument Because it doesn't understand it accepts subtypes */
        return new ChunkArguments(
            ShortString::unpack(...), // consumer tag
        );
    }

    private function basicCancelOk(): ChunkArguments
    {
        /** @psalm-suppress InvalidArgument Because it doesn't understand it accepts subtypes */
        return new ChunkArguments(
            ShortString::unpack(...), // consumer tag
        );
    }

    private function basicReturn(): ChunkArguments
    {
        /** @psalm-suppress InvalidArgument Because it doesn't understand it accepts subtypes */
        return new ChunkArguments(
            UnsignedShortInteger::unpack(...), // reply code
            ShortString::unpack(...), // reply text
            ShortString::unpack(...), // exchange
            ShortString::unpack(...), // routing key
        );
    }

    private function basicDeliver(): ChunkArguments
    {
        /** @psalm-suppress InvalidArgument Because it doesn't understand it accepts subtypes */
        return new ChunkArguments(
            ShortString::unpack(...), // consumer tag
            UnsignedLongLongInteger::unpack(...), // delivery tag
            Bits::unpack(...), // redelivered
            ShortString::unpack(...), // exchange
            ShortString::unpack(...), // routing key
        );
    }

    private function basicGetOk(): ChunkArguments
    {
        /** @psalm-suppress InvalidArgument Because it doesn't understand it accepts subtypes */
        return new ChunkArguments(
            UnsignedLongLongInteger::unpack(...), // delivery tag
            Bits::unpack(...), // redelivered
            ShortString::unpack(...), // exchange
            ShortString::unpack(...), // routing key
            UnsignedLongInteger::unpack(...), // message count
        );
    }

    private function basicGetEmpty(): ChunkArguments
    {
        /** @psalm-suppress InvalidArgument Because it doesn't understand it accepts subtypes */
        return new ChunkArguments(
            ShortString::unpack(...), // reserved
        );
    }

    private function basicRecoverOk(): ChunkArguments
    {
        return new ChunkArguments; // no arguments
    }

    private function channelOpenOk(): ChunkArguments
    {
        /** @psalm-suppress InvalidArgument Because it doesn't understand it accepts subtypes */
        return new ChunkArguments(
            LongString::unpack(...), // reserved
        );
    }

    private function channelFlow(): ChunkArguments
    {
        /** @psalm-suppress InvalidArgument Because it doesn't understand it accepts subtypes */
        return new ChunkArguments(
            Bits::unpack(...), // active
        );
    }

    private function channelFlowOk(): ChunkArguments
    {
        /** @psalm-suppress InvalidArgument Because it doesn't understand it accepts subtypes */
        return new ChunkArguments(
            Bits::unpack(...), // active
        );
    }

    private function channelClose(): ChunkArguments
    {
        /** @psalm-suppress InvalidArgument Because it doesn't understand it accepts subtypes */
        return new ChunkArguments(
            UnsignedShortInteger::unpack(...), // reply code
            ShortString::unpack(...), // reply text
            UnsignedShortInteger::unpack(...), // failing class id
            UnsignedShortInteger::unpack(...), // failing method id
        );
    }

    private function channelCloseOk(): ChunkArguments
    {
        return new ChunkArguments; // no arguments
    }

    private function connectionStart(): ChunkArguments
    {
        /** @psalm-suppress InvalidArgument Because it doesn't understand it accepts subtypes */
        return new ChunkArguments(
            UnsignedOctet::unpack(...), // major version
            UnsignedOctet::unpack(...), // minor version
            Table::unpack(...), // server properties
            LongString::unpack(...), // mechanisms
            LongString::unpack(...), // locales
        );
    }

    private function connectionSecure(): ChunkArguments
    {
        /** @psalm-suppress InvalidArgument Because it doesn't understand it accepts subtypes */
        return new ChunkArguments(
            LongString::unpack(...), // challenge
        );
    }

    private function connectionTune(): ChunkArguments
    {
        /** @psalm-suppress InvalidArgument Because it doesn't understand it accepts subtypes */
        return new ChunkArguments(
            UnsignedShortInteger::unpack(...), // max channels
            UnsignedLongInteger::unpack(...), // max frame size
            UnsignedShortInteger::unpack(...), // heartbeat delay
        );
    }

    private function connectionOpenOk(): ChunkArguments
    {
        /** @psalm-suppress InvalidArgument Because it doesn't understand it accepts subtypes */
        return new ChunkArguments(
            ShortString::unpack(...), // known hosts
        );
    }

    private function connectionClose(): ChunkArguments
    {
        /** @psalm-suppress InvalidArgument Because it doesn't understand it accepts subtypes */
        return new ChunkArguments(
            UnsignedShortInteger::unpack(...), // reply code
            ShortString::unpack(...), // reply text
            UnsignedShortInteger::unpack(...), // failing class id
            UnsignedShortInteger::unpack(...), // failing method id
        );
    }

    private function connectionCloseOk(): ChunkArguments
    {
        return new ChunkArguments; // no arguments
    }

    private function exchangeDeclareOk(): ChunkArguments
    {
        return new ChunkArguments; // no arguments
    }

    private function exchangeDeleteOk(): ChunkArguments
    {
        return new ChunkArguments; // no arguments
    }

    private function queueDeclareOk(): ChunkArguments
    {
        /** @psalm-suppress InvalidArgument Because it doesn't understand it accepts subtypes */
        return new ChunkArguments(
            ShortString::unpack(...), // queue
            UnsignedLongInteger::unpack(...), // message count
            UnsignedLongInteger::unpack(...), // consumer count
        );
    }

    private function queueBindOk(): ChunkArguments
    {
        return new ChunkArguments; // no arguments
    }

    private function queueUnbindOk(): ChunkArguments
    {
        return new ChunkArguments; // no arguments
    }

    private function queuePurgeOk(): ChunkArguments
    {
        /** @psalm-suppress InvalidArgument Because it doesn't understand it accepts subtypes */
        return new ChunkArguments(
            UnsignedLongInteger::unpack(...), // message count
        );
    }

    private function queueDeleteOk(): ChunkArguments
    {
        /** @psalm-suppress InvalidArgument Because it doesn't understand it accepts subtypes */
        return new ChunkArguments(
            UnsignedLongInteger::unpack(...), // message count
        );
    }

    private function transactionSelectOk(): ChunkArguments
    {
        return new ChunkArguments; // no arguments
    }

    private function transactionCommitOk(): ChunkArguments
    {
        return new ChunkArguments; // no arguments
    }

    private function transactionRollbackOk(): ChunkArguments
    {
        return new ChunkArguments; // no arguments
    }
}
