<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Protocol;

use Innmind\AMQP\Transport\{
    Frame\Method,
    Frame\Value,
    Frame\Visitor\ChunkArguments,
    Frame\Value\Bits,
    Frame\Value\LongString,
    Frame\Value\ShortString,
    Frame\Value\UnsignedLongInteger,
    Frame\Value\UnsignedLongLongInteger,
    Frame\Value\UnsignedOctet,
    Frame\Value\UnsignedShortInteger,
    Frame\Value\Table,
};
use Innmind\Stream\Readable;
use Innmind\Immutable\Sequence;

final class Reader
{
    /**
     * @return Sequence<Value>
     */
    public function __invoke(Method $method, Readable $arguments): Sequence
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
        return new ChunkArguments(
            ShortString::class, // consumer tag
        );
    }

    private function basicCancelOk(): ChunkArguments
    {
        return new ChunkArguments(
            ShortString::class, // consumer tag
        );
    }

    private function basicReturn(): ChunkArguments
    {
        return new ChunkArguments(
            UnsignedShortInteger::class, // reply code
            ShortString::class, // reply text
            ShortString::class, // exchange
            ShortString::class, // routing key
        );
    }

    private function basicDeliver(): ChunkArguments
    {
        return new ChunkArguments(
            ShortString::class, // consumer tag
            UnsignedLongLongInteger::class, // delivery tag
            Bits::class, // redelivered
            ShortString::class, // exchange
            ShortString::class, // routing key
        );
    }

    private function basicGetOk(): ChunkArguments
    {
        return new ChunkArguments(
            UnsignedLongLongInteger::class, // delivery tag
            Bits::class, // redelivered
            ShortString::class, // exchange
            ShortString::class, // routing key
            UnsignedLongInteger::class, // message count
        );
    }

    private function basicGetEmpty(): ChunkArguments
    {
        return new ChunkArguments; // no arguments
    }

    private function basicRecoverOk(): ChunkArguments
    {
        return new ChunkArguments; // no arguments
    }

    private function channelOpenOk(): ChunkArguments
    {
        return new ChunkArguments; // no arguments
    }

    private function channelFlow(): ChunkArguments
    {
        return new ChunkArguments(
            Bits::class, // active
        );
    }

    private function channelFlowOk(): ChunkArguments
    {
        return new ChunkArguments(
            Bits::class, // active
        );
    }

    private function channelClose(): ChunkArguments
    {
        return new ChunkArguments(
            UnsignedShortInteger::class, // reply code
            ShortString::class, // reply text
            UnsignedShortInteger::class, // failing class id
            UnsignedShortInteger::class, // failing method id
        );
    }

    private function channelCloseOk(): ChunkArguments
    {
        return new ChunkArguments; // no arguments
    }

    private function connectionStart(): ChunkArguments
    {
        return new ChunkArguments(
            UnsignedOctet::class, // major version
            UnsignedOctet::class, // minor version
            Table::class, // server properties
            LongString::class, // mechanisms
            LongString::class, // locales
        );
    }

    private function connectionSecure(): ChunkArguments
    {
        return new ChunkArguments(
            LongString::class, // challenge
        );
    }

    private function connectionTune(): ChunkArguments
    {
        return new ChunkArguments(
            UnsignedShortInteger::class, // max channels
            UnsignedLongInteger::class, // max frame size
            UnsignedShortInteger::class, // heartbeat delay
        );
    }

    private function connectionOpenOk(): ChunkArguments
    {
        return new ChunkArguments(
            ShortString::class, // known hosts
        );
    }

    private function connectionClose(): ChunkArguments
    {
        return new ChunkArguments(
            UnsignedShortInteger::class, // reply code
            ShortString::class, // reply text
            UnsignedShortInteger::class, // failing class id
            UnsignedShortInteger::class, // failing method id
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
        return new ChunkArguments(
            ShortString::class, // queue
            UnsignedLongInteger::class, // message count
            UnsignedLongInteger::class, // consumer count
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
        return new ChunkArguments(
            UnsignedLongInteger::class, // message count
        );
    }

    private function queueDeleteOk(): ChunkArguments
    {
        return new ChunkArguments(
            UnsignedLongInteger::class, // message count
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
