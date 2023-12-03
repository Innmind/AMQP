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
use Innmind\IO\Readable\Stream;
use Innmind\Socket\Client;
use Innmind\Stream\Readable;
use Innmind\Immutable\{
    Sequence,
    Maybe,
};

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
     * @param Stream<Client> $arguments
     *
     * @return Maybe<Sequence<Value>>
     */
    public function read(Method $method, Stream $arguments): Maybe
    {
        return ($this->read)($method, $arguments);
    }

    /**
     * @param Stream<Client> $arguments
     *
     * @return Maybe<Sequence<Value>>
     */
    public function readHeader(Stream $arguments): Maybe
    {
        return UnsignedLongLongInteger::unpack($arguments->unwrap())->flatMap(
            fn($bodySize) => UnsignedShortInteger::unpack($arguments->unwrap())->flatMap(
                fn($flags) => $this->parseHeader($bodySize, $flags, $arguments->unwrap()),
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
     * @return Maybe<Sequence<Value>>
     */
    private function parseHeader(
        UnsignedLongLongInteger $bodySize,
        UnsignedShortInteger $flags,
        Readable $arguments,
    ): Maybe {
        $flagBits = $flags->original();
        $toChunk = Sequence::of(
            [15, ShortString::unpack(...)], // content type
            [14, ShortString::unpack(...)], // content encoding
            [13, fn(Readable $stream) => Table::unpack($this->clock, $stream)], // headers
            [12, UnsignedOctet::unpack(...)], // delivery mode
            [11, UnsignedOctet::unpack(...)], // priority
            [10, ShortString::unpack(...)], // correlation id
            [9, ShortString::unpack(...)], // reply to
            [8, ShortString::unpack(...)], // expiration
            [7, ShortString::unpack(...)], // id,
            [6, fn(Readable $stream) => Timestamp::unpack($this->clock, $stream)], // timestamp
            [5, ShortString::unpack(...)], // type
            [4, ShortString::unpack(...)], // user id
            [3, ShortString::unpack(...)], // app id
        )
            ->map(static fn($pair) => [1 << $pair[0], $pair[1]])
            ->filter(static fn($pair) => (bool) ($flagBits & $pair[0]))
            ->map(static fn($pair) => $pair[1])
            ->toList();

        /**
         * @psalm-suppress InvalidArgument
         * @psalm-suppress ArgumentTypeCoercion
         * @var Maybe<Sequence<Value>>
         */
        return (new ChunkArguments(...$toChunk))($arguments)->map(
            static fn($arguments) => Sequence::of($bodySize, $flags)->append($arguments),
        );
    }
}
