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
    Exception\VersionNotUsable
};
use Innmind\Immutable\{
    Str,
    StreamInterface
};

final class Protocol implements ProtocolInterface
{
    private $version;
    private $read;
    private $connection;
    private $channel;
    private $exchange;
    private $queue;
    private $basic;
    private $transaction;

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
    public function read(Method $method, Str $arguments): StreamInterface
    {
        return ($this->read)($method, $arguments);
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
