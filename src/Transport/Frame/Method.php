<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame;

use Innmind\AMQP\Transport\Frame\Value\{
    Unpacked,
    Bits,
    LongString,
    ShortString,
    UnsignedLongInteger,
    UnsignedLongLongInteger,
    UnsignedOctet,
    UnsignedShortInteger,
    Table,
};
use Innmind\TimeContinuum\Clock;
use Innmind\IO\Readable\Frame;
use Innmind\Immutable\{
    Maybe,
    Sequence,
    Predicate\Instance,
};

/**
 * @psalm-immutable
 * @internal
 */
enum Method
{
    case connectionStart;
    case connectionStartOk;
    case connectionSecure;
    case connectionSecureOk;
    case connectionTune;
    case connectionTuneOk;
    case connectionOpen;
    case connectionOpenOk;
    case connectionClose;
    case connectionCloseOk;
    // connectionBlocked and connectionsUnblocked (methods 10.60 and 10.61) are not supported (RabbitMQ extension)
    case channelOpen;
    case channelOpenOk;
    case channelFlow;
    case channelFlowOk;
    case channelClose;
    case channelCloseOk;
    case exchangeDeclare;
    case exchangeDeclareOk;
    case exchangeDelete;
    case exchangeDeleteOk;
    case queueDeclare;
    case queueDeclareOk;
    case queueBind;
    case queueBindOk;
    case queueUnbind;
    case queueUnbindOk;
    case queuePurge;
    case queuePurgeOk;
    case queueDelete;
    case queueDeleteOk;
    case basicQos;
    case basicQosOk;
    case basicConsume;
    case basicConsumeOk;
    case basicCancel;
    case basicCancelOk;
    case basicPublish;
    case basicReturn;
    case basicDeliver;
    case basicGet;
    case basicGetOk;
    case basicGetEmpty;
    case basicAck;
    case basicReject;
    case basicRecoverAsync;
    case basicRecover;
    case basicRecoverOk;
    case transactionSelect;
    case transactionSelectOk;
    case transactionCommit;
    case transactionCommitOk;
    case transactionRollback;
    case transactionRollbackOk;

    /**
     * @psalm-pure
     */
    public static function of(int $class, int $method): self
    {
        return self::maybe($class, $method)->match(
            static fn($self) => $self,
            static fn() => throw new \RuntimeException("$class,$method"),
        );
    }

    /**
     * @psalm-pure
     *
     * @return Frame<self>
     */
    public static function frame(int $class, int $method): Frame
    {
        return self::maybe($class, $method)->match(
            static fn($self) => Frame\NoOp::of($self),
            static fn() => Frame\NoOp::of(self::connectionStart)->filter(static fn() => false), // force fail
        );
    }

    /**
     * @psalm-pure
     *
     * @return Maybe<self>
     */
    public static function maybe(int $class, int $method): Maybe
    {
        /** @var Maybe<self> */
        return Maybe::of((match ([$class, $method]) {
            [10, 10] => self::connectionStart,
            [10, 11] => self::connectionStartOk,
            [10, 20] => self::connectionSecure,
            [10, 21] => self::connectionSecureOk,
            [10, 30] => self::connectionTune,
            [10, 31] => self::connectionTuneOk,
            [10, 40] => self::connectionOpen,
            [10, 41] => self::connectionOpenOk,
            [10, 50] => self::connectionClose,
            [10, 51] => self::connectionCloseOk,
            [20, 10] => self::channelOpen,
            [20, 11] => self::channelOpenOk,
            [20, 20] => self::channelFlow,
            [20, 21] => self::channelFlowOk,
            [20, 40] => self::channelClose,
            [20, 41] => self::channelCloseOk,
            [40, 10] => self::exchangeDeclare,
            [40, 11] => self::exchangeDeclareOk,
            [40, 20] => self::exchangeDelete,
            [40, 21] => self::exchangeDeleteOk,
            [50, 10] => self::queueDeclare,
            [50, 11] => self::queueDeclareOk,
            [50, 20] => self::queueBind,
            [50, 21] => self::queueBindOk,
            [50, 50] => self::queueUnbind,
            [50, 51] => self::queueUnbindOk,
            [50, 30] => self::queuePurge,
            [50, 31] => self::queuePurgeOk,
            [50, 40] => self::queueDelete,
            [50, 41] => self::queueDeleteOk,
            [60, 10] => self::basicQos,
            [60, 11] => self::basicQosOk,
            [60, 20] => self::basicConsume,
            [60, 21] => self::basicConsumeOk,
            [60, 30] => self::basicCancel,
            [60, 31] => self::basicCancelOk,
            [60, 40] => self::basicPublish,
            [60, 50] => self::basicReturn,
            [60, 60] => self::basicDeliver,
            [60, 70] => self::basicGet,
            [60, 71] => self::basicGetOk,
            [60, 72] => self::basicGetEmpty,
            [60, 80] => self::basicAck,
            [60, 90] => self::basicReject,
            [60, 100] => self::basicRecoverAsync,
            [60, 110] => self::basicRecover,
            [60, 111] => self::basicRecoverOk,
            [90, 10] => self::transactionSelect,
            [90, 11] => self::transactionSelectOk,
            [90, 20] => self::transactionCommit,
            [90, 21] => self::transactionCommitOk,
            [90, 30] => self::transactionRollback,
            [90, 31] => self::transactionRollbackOk,
            default => null,
        }));
    }

    public function class(): MethodClass
    {
        return match ($this) {
            self::connectionStart => MethodClass::connection,
            self::connectionStartOk => MethodClass::connection,
            self::connectionSecure => MethodClass::connection,
            self::connectionSecureOk => MethodClass::connection,
            self::connectionTune => MethodClass::connection,
            self::connectionTuneOk => MethodClass::connection,
            self::connectionOpen => MethodClass::connection,
            self::connectionOpenOk => MethodClass::connection,
            self::connectionClose => MethodClass::connection,
            self::connectionCloseOk => MethodClass::connection,
            self::channelOpen => MethodClass::channel,
            self::channelOpenOk => MethodClass::channel,
            self::channelFlow => MethodClass::channel,
            self::channelFlowOk => MethodClass::channel,
            self::channelClose => MethodClass::channel,
            self::channelCloseOk => MethodClass::channel,
            self::exchangeDeclare => MethodClass::exchange,
            self::exchangeDeclareOk => MethodClass::exchange,
            self::exchangeDelete => MethodClass::exchange,
            self::exchangeDeleteOk => MethodClass::exchange,
            self::queueDeclare => MethodClass::queue,
            self::queueDeclareOk => MethodClass::queue,
            self::queueBind => MethodClass::queue,
            self::queueBindOk => MethodClass::queue,
            self::queueUnbind => MethodClass::queue,
            self::queueUnbindOk => MethodClass::queue,
            self::queuePurge => MethodClass::queue,
            self::queuePurgeOk => MethodClass::queue,
            self::queueDelete => MethodClass::queue,
            self::queueDeleteOk => MethodClass::queue,
            self::basicQos => MethodClass::basic,
            self::basicQosOk => MethodClass::basic,
            self::basicConsume => MethodClass::basic,
            self::basicConsumeOk => MethodClass::basic,
            self::basicCancel => MethodClass::basic,
            self::basicCancelOk => MethodClass::basic,
            self::basicPublish => MethodClass::basic,
            self::basicReturn => MethodClass::basic,
            self::basicDeliver => MethodClass::basic,
            self::basicGet => MethodClass::basic,
            self::basicGetOk => MethodClass::basic,
            self::basicGetEmpty => MethodClass::basic,
            self::basicAck => MethodClass::basic,
            self::basicReject => MethodClass::basic,
            self::basicRecoverAsync => MethodClass::basic,
            self::basicRecover => MethodClass::basic,
            self::basicRecoverOk => MethodClass::basic,
            self::transactionSelect => MethodClass::transaction,
            self::transactionSelectOk => MethodClass::transaction,
            self::transactionCommit => MethodClass::transaction,
            self::transactionCommitOk => MethodClass::transaction,
            self::transactionRollback => MethodClass::transaction,
            self::transactionRollbackOk => MethodClass::transaction,
        };
    }

    /**
     * @return int<10, 111>
     */
    public function method(): int
    {
        return match ($this) {
            self::connectionStart => 10,
            self::connectionStartOk => 11,
            self::connectionSecure => 20,
            self::connectionSecureOk => 21,
            self::connectionTune => 30,
            self::connectionTuneOk => 31,
            self::connectionOpen => 40,
            self::connectionOpenOk => 41,
            self::connectionClose => 50,
            self::connectionCloseOk => 51,
            self::channelOpen => 10,
            self::channelOpenOk => 11,
            self::channelFlow => 20,
            self::channelFlowOk => 21,
            self::channelClose => 40,
            self::channelCloseOk => 41,
            self::exchangeDeclare => 10,
            self::exchangeDeclareOk => 11,
            self::exchangeDelete => 20,
            self::exchangeDeleteOk => 21,
            self::queueDeclare => 10,
            self::queueDeclareOk => 11,
            self::queueBind => 20,
            self::queueBindOk => 21,
            self::queueUnbind => 50,
            self::queueUnbindOk => 51,
            self::queuePurge => 30,
            self::queuePurgeOk => 31,
            self::queueDelete => 40,
            self::queueDeleteOk => 41,
            self::basicQos => 10,
            self::basicQosOk => 11,
            self::basicConsume => 20,
            self::basicConsumeOk => 21,
            self::basicCancel => 30,
            self::basicCancelOk => 31,
            self::basicPublish => 40,
            self::basicReturn => 50,
            self::basicDeliver => 60,
            self::basicGet => 70,
            self::basicGetOk => 71,
            self::basicGetEmpty => 72,
            self::basicAck => 80,
            self::basicReject => 90,
            self::basicRecoverAsync => 100,
            self::basicRecover => 110,
            self::basicRecoverOk => 111,
            self::transactionSelect => 10,
            self::transactionSelectOk => 11,
            self::transactionCommit => 20,
            self::transactionCommitOk => 21,
            self::transactionRollback => 30,
            self::transactionRollbackOk => 31,
        };
    }

    public function equals(self $method): bool
    {
        return $method === $this;
    }

    /**
     * @return Frame<Sequence<Value>>
     */
    public function incomingFrame(Clock $clock): Frame
    {
        /**
         * @psalm-suppress NamedArgumentNotAllowed
         * @var Frame<Sequence<Unpacked>>
         */
        $frame = match ($this) {
            Method::basicQosOk,
            Method::basicRecoverOk,
            Method::channelCloseOk,
            Method::connectionCloseOk,
            Method::exchangeDeclareOk,
            Method::exchangeDeleteOk,
            Method::queueBindOk,
            Method::queueUnbindOk,
            Method::transactionSelectOk,
            Method::transactionCommitOk,
            Method::transactionRollbackOk => Frame\NoOp::of(Sequence::of()), // no arguments
            Method::basicConsumeOk => ShortString::frame()->map(Sequence::of(...)), // consumer tag
            Method::basicCancelOk => ShortString::frame()->map(Sequence::of(...)), // consumer tag
            Method::basicReturn => Frame\Composite::of(
                static fn(Unpacked ...$values) => Sequence::of(...$values),
                UnsignedShortInteger::frame(), // reply code
                ShortString::frame(), // reply text
                ShortString::frame(), // exchange
                ShortString::frame(), // routing key
            ),
            Method::basicDeliver => Frame\Composite::of(
                static fn(Unpacked ...$values) => Sequence::of(...$values),
                ShortString::frame(), // consumer tag
                UnsignedLongLongInteger::frame(), // delivery tag
                Bits::frame(), // redelivered
                ShortString::frame(), // exchange
                ShortString::frame(), // routing key
            ),
            Method::basicGetOk => Frame\Composite::of(
                static fn(Unpacked ...$values) => Sequence::of(...$values),
                UnsignedLongLongInteger::frame(), // delivery tag
                Bits::frame(), // redelivered
                ShortString::frame(), // exchange
                ShortString::frame(), // routing key
                UnsignedLongInteger::frame(), // message count
            ),
            Method::basicGetEmpty => ShortString::frame()->map(Sequence::of(...)), // reserved,
            Method::channelOpenOk => LongString::frame()->map(Sequence::of(...)), // reserved
            Method::channelFlow => Bits::frame()->map(Sequence::of(...)), // active
            Method::channelFlowOk => Bits::frame()->map(Sequence::of(...)), // active
            Method::channelClose => Frame\Composite::of(
                static fn(Unpacked ...$values) => Sequence::of(...$values),
                UnsignedShortInteger::frame(), // reply code
                ShortString::frame(), // reply text
                UnsignedShortInteger::frame(), // failing class id
                UnsignedShortInteger::frame(), // failing method id
            ),
            Method::connectionStart => Frame\Composite::of(
                static fn(Unpacked ...$values) => Sequence::of(...$values),
                UnsignedOctet::frame(), // major version
                UnsignedOctet::frame(), // minor version
                Table::frame($clock), // server properties
                LongString::frame(), // mechanisms
                LongString::frame(), // locales
            ),
            Method::connectionSecure => LongString::frame()->map(Sequence::of(...)), // challenge
            Method::connectionTune => Frame\Composite::of(
                static fn(Unpacked ...$values) => Sequence::of(...$values),
                UnsignedShortInteger::frame(), // max channels
                UnsignedLongInteger::frame(), // max frame size
                UnsignedShortInteger::frame(), // heartbeat delay
            ),
            Method::connectionOpenOk => ShortString::frame()->map(Sequence::of(...)), // known hosts
            Method::connectionClose => Frame\Composite::of(
                static fn(Unpacked ...$values) => Sequence::of(...$values),
                UnsignedShortInteger::frame(), // reply code
                ShortString::frame(), // reply text
                UnsignedShortInteger::frame(), // failing class id
                UnsignedShortInteger::frame(), // failing method id
            ),
            Method::queueDeclareOk => Frame\Composite::of(
                static fn(Unpacked ...$values) => Sequence::of(...$values),
                ShortString::frame(), // queue
                UnsignedLongInteger::frame(), // message count
                UnsignedLongInteger::frame(), // consumer count
            ),
            Method::queuePurgeOk => UnsignedLongInteger::frame()->map(Sequence::of(...)), // message count
            Method::queueDeleteOk => UnsignedLongInteger::frame()->map(Sequence::of(...)), // message count
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

        return $frame->map(
            static fn($values) => $values
                ->keep(Instance::of(Unpacked::class))
                ->map(static fn($unpacked) => $unpacked->unwrap()),
        );
    }
}
