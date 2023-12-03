<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport;

use Innmind\AMQP\Transport\{
    Protocol\Version,
    Protocol\Connection,
    Protocol\Channel,
    Protocol\Exchange,
    Protocol\Queue,
    Protocol\Basic,
    Protocol\Transaction,
    Protocol\ArgumentTranslator,
    Protocol\Reader,
    Frame\Method,
    Frame\Visitor\ChunkArguments,
    Frame\Value\UnsignedOctet,
    Frame\Value\UnsignedShortInteger,
    Frame\Value\UnsignedLongLongInteger,
    Frame\Value\Timestamp,
    Frame\Value\Table,
    Frame\Value\ShortString,
    Frame\Value,
};
use Innmind\TimeContinuum\Clock;
use Innmind\IO\Readable\Frame;
use Innmind\Immutable\Sequence;

/**
 * @internal
 */
final class Protocol
{
    private Clock $clock;
    private Version $version;
    private Reader $read;
    private Connection $connection;
    private Channel $channel;
    private Exchange $exchange;
    private Queue $queue;
    private Basic $basic;
    private Transaction $transaction;

    public function __construct(Clock $clock, ArgumentTranslator $translator)
    {
        $this->clock = $clock;
        $this->version = Version::v091;
        $this->read = new Reader($clock);
        $this->connection = new Connection;
        $this->channel = new Channel;
        $this->exchange = new Exchange($translator);
        $this->queue = new Queue($translator);
        $this->basic = new Basic($translator);
        $this->transaction = new Transaction;
    }

    public function version(): Version
    {
        return $this->version;
    }

    /**
     * @return Frame<Sequence<Value>>
     */
    public function frame(Method $method): Frame
    {
        return ($this->read)($method);
    }

    /**
     * @return Frame<Sequence<Value>>
     */
    public function headerFrame(): Frame
    {
        return UnsignedLongLongInteger::frame()->flatMap(
            fn($bodySize) => UnsignedShortInteger::frame()->flatMap(
                fn($flags) => $this->parseHeader(
                    $bodySize->unwrap(),
                    $flags->unwrap(),
                ),
            ),
        );
    }

    public function connection(): Connection
    {
        return $this->connection;
    }

    public function channel(): Channel
    {
        return $this->channel;
    }

    public function exchange(): Exchange
    {
        return $this->exchange;
    }

    public function queue(): Queue
    {
        return $this->queue;
    }

    public function basic(): Basic
    {
        return $this->basic;
    }

    public function transaction(): Transaction
    {
        return $this->transaction;
    }

    /**
     * @return Frame<Sequence<Value>>
     */
    private function parseHeader(
        UnsignedLongLongInteger $bodySize,
        UnsignedShortInteger $flags,
    ): Frame {
        $flagBits = $flags->original();
        $toChunk = Sequence::of(
            [15, ShortString::frame()], // content type
            [14, ShortString::frame()], // content encoding
            [13, Table::frame($this->clock)], // headers
            [12, UnsignedOctet::frame()], // delivery mode
            [11, UnsignedOctet::frame()], // priority
            [10, ShortString::frame()], // correlation id
            [9, ShortString::frame()], // reply to
            [8, ShortString::frame()], // expiration
            [7, ShortString::frame()], // id,
            [6, Timestamp::frame($this->clock)], // timestamp
            [5, ShortString::frame()], // type
            [4, ShortString::frame()], // user id
            [3, ShortString::frame()], // app id
        )
            ->map(static fn($pair) => [1 << $pair[0], $pair[1]])
            ->filter(static fn($pair) => (bool) ($flagBits & $pair[0]))
            ->map(static fn($pair) => $pair[1])
            ->map(static fn($frame) => $frame->map(
                static fn($value) => $value->unwrap(),
            ));

        /** @var Frame<Sequence<Value>> */
        return $toChunk->match(
            static fn($first, $rest) => Frame\Composite::of(
                static fn(Value ...$values) => Sequence::of($bodySize, $flags, ...$values),
                $first,
                ...$rest->toList(),
            ),
            static fn() => Frame\NoOp::of(Sequence::of($bodySize, $flags)),
        );
    }
}
