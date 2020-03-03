<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Protocol\v091;

use Innmind\AMQP\{
    Transport\Protocol as ProtocolInterface,
    Transport\Protocol\Version,
    Transport\Protocol\Connection as ConnectionInterface,
    Transport\Protocol\Channel as ChannelInterface,
    Transport\Protocol\Exchange as ExchangeInterface,
    Transport\Protocol\Queue as QueueInterface,
    Transport\Protocol\Basic as BasicInterface,
    Transport\Protocol\Transaction as TransactionInterface,
    Transport\Protocol\ArgumentTranslator,
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
use function Innmind\Immutable\unwrap;

final class Protocol implements ProtocolInterface
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
        $this->version = new Version(0, 9, 1);
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

    public function use(Version $version): ProtocolInterface
    {
        if (!$version->compatibleWith($this->version)) {
            throw new VersionNotUsable($version);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function read(Method $method, Readable $arguments): Sequence
    {
        return ($this->read)($method, $arguments);
    }

    /**
     * {@inheritdoc}
     */
    public function readHeader(Readable $payload): Sequence
    {
        $chunk = new ChunkArguments(
            UnsignedLongLongInteger::class,
            UnsignedShortInteger::class
        );
        [$bodySize, $flags] = unwrap($chunk($payload));

        $flagBits = $flags->original()->value();
        $toChunk = [];

        if ($flagBits & (1 << 15)) {
            $toChunk[] = ShortString::class; //content type
        }

        if ($flagBits & (1 << 14)) {
            $toChunk[] = ShortString::class; //content encoding
        }

        if ($flagBits & (1 << 13)) {
            $toChunk[] = Table::class; //headers
        }

        if ($flagBits & (1 << 12)) {
            $toChunk[] = UnsignedOctet::class; //delivery mode
        }

        if ($flagBits & (1 << 11)) {
            $toChunk[] = UnsignedOctet::class; //priority
        }

        if ($flagBits & (1 << 10)) {
            $toChunk[] = ShortString::class; //correlation id
        }

        if ($flagBits & (1 << 9)) {
            $toChunk[] = ShortString::class; //reply to
        }

        if ($flagBits & (1 << 8)) {
            $toChunk[] = ShortString::class; //expiration
        }

        if ($flagBits & (1 << 7)) {
            $toChunk[] = ShortString::class; //id
        }

        if ($flagBits & (1 << 6)) {
            $toChunk[] = Timestamp::class; //timestamp
        }

        if ($flagBits & (1 << 5)) {
            $toChunk[] = ShortString::class; //type
        }

        if ($flagBits & (1 << 4)) {
            $toChunk[] = ShortString::class; //user id
        }

        if ($flagBits & (1 << 3)) {
            $toChunk[] = ShortString::class; //app id
        }

        return Sequence::of(Value::class, $bodySize, $flags)->append(
            (new ChunkArguments(...$toChunk))($payload),
        );
    }

    public function method(string $name): Method
    {
        return Methods::get($name);
    }

    public function connection(): ConnectionInterface
    {
        return $this->connection;
    }

    public function channel(): ChannelInterface
    {
        return $this->channel;
    }

    public function exchange(): ExchangeInterface
    {
        return $this->exchange;
    }

    public function queue(): QueueInterface
    {
        return $this->queue;
    }

    public function basic(): BasicInterface
    {
        return $this->basic;
    }

    public function transaction(): TransactionInterface
    {
        return $this->transaction;
    }
}
