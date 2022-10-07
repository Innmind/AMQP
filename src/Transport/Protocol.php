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
    Transport\Protocol\Methods,
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
use Innmind\Immutable\Sequence;

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
     * @return Sequence<Value>
     */
    public function read(Method $method, Readable $arguments): Sequence
    {
        return ($this->read)($method, $arguments);
    }

    /**
     * @return Sequence<Value>
     */
    public function readHeader(Readable $arguments): Sequence
    {
        $bodySize = UnsignedLongLongInteger::unpack($arguments);
        $flags = UnsignedShortInteger::unpack($arguments);

        $flagBits = $flags->original()->value();
        $toChunk = [];

        if ($flagBits & (1 << 15)) {
            $toChunk[] = ShortString::class; // content type
        }

        if ($flagBits & (1 << 14)) {
            $toChunk[] = ShortString::class; // content encoding
        }

        if ($flagBits & (1 << 13)) {
            $toChunk[] = Table::class; // headers
        }

        if ($flagBits & (1 << 12)) {
            $toChunk[] = UnsignedOctet::class; // delivery mode
        }

        if ($flagBits & (1 << 11)) {
            $toChunk[] = UnsignedOctet::class; // priority
        }

        if ($flagBits & (1 << 10)) {
            $toChunk[] = ShortString::class; // correlation id
        }

        if ($flagBits & (1 << 9)) {
            $toChunk[] = ShortString::class; // reply to
        }

        if ($flagBits & (1 << 8)) {
            $toChunk[] = ShortString::class; // expiration
        }

        if ($flagBits & (1 << 7)) {
            $toChunk[] = ShortString::class; // id
        }

        if ($flagBits & (1 << 6)) {
            $toChunk[] = Timestamp::class; // timestamp
        }

        if ($flagBits & (1 << 5)) {
            $toChunk[] = ShortString::class; // type
        }

        if ($flagBits & (1 << 4)) {
            $toChunk[] = ShortString::class; // user id
        }

        if ($flagBits & (1 << 3)) {
            $toChunk[] = ShortString::class; // app id
        }

        /** @var Sequence<Value> */
        $values = Sequence::of($bodySize, $flags);

        return $values->append(
            (new ChunkArguments(...$toChunk))($arguments),
        );
    }

    public function method(string $name): Method
    {
        return Methods::get($name);
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
}
