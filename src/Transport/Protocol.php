<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport;

use Innmind\AMQP\{
    Transport\Protocol\Version,
    Transport\Protocol\Connection,
    Transport\Protocol\Channel,
    Transport\Protocol\Exchange,
    Transport\Protocol\Queue,
    Transport\Protocol\Basic,
    Transport\Protocol\Transaction,
    Transport\Protocol\ArgumentTranslator,
    Transport\Protocol\Reader,
    Transport\Frame\Method,
    Transport\Frame\Visitor\ChunkArguments,
    Transport\Frame\Value\UnsignedOctet,
    Transport\Frame\Value\UnsignedShortInteger,
    Transport\Frame\Value\UnsignedLongLongInteger,
    Transport\Frame\Value\Timestamp,
    Transport\Frame\Value\Table,
    Transport\Frame\Value\ShortString,
    Transport\Frame\Value,
    Exception\VersionNotUsable,
};
use Innmind\Stream\Readable;
use Innmind\Immutable\{
    Sequence,
    Maybe,
};

final class Protocol
{
    private Version $version;
    private Reader $read;
    private Connection $connection;
    private Channel $channel;
    private Exchange $exchange;
    private Queue $queue;
    private Basic $basic;
    private Transaction $transaction;

    public function __construct(ArgumentTranslator $translator)
    {
        $this->version = Version::v091;
        $this->read = new Reader;
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

    public function use(int $major, int $minor, int $fix): void
    {
        if (!$this->version->compatibleWith($major, $minor, $fix)) {
            throw new VersionNotUsable("$major.$minor.$fix");
        }
    }

    /**
     * @return Maybe<Sequence<Value>>
     */
    public function read(Method $method, Readable $arguments): Maybe
    {
        return ($this->read)($method, $arguments);
    }

    /**
     * @return Maybe<Sequence<Value>>
     */
    public function readHeader(Readable $arguments): Maybe
    {
        return UnsignedLongLongInteger::unpack($arguments)->flatMap(
            fn($bodySize) => UnsignedShortInteger::unpack($arguments)->flatMap(
                fn($flags) => $this->parseHeader($bodySize, $flags, $arguments),
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
            [13, Table::unpack(...)], // headers
            [12, UnsignedOctet::unpack(...)], // delivery mode
            [11, UnsignedOctet::unpack(...)], // priority
            [10, ShortString::unpack(...)], // correlation id
            [9, ShortString::unpack(...)], // reply to
            [8, ShortString::unpack(...)], // expiration
            [7, ShortString::unpack(...)], // id,
            [6, Timestamp::unpack(...)], // timestamp
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
