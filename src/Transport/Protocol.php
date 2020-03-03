<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport;

use Innmind\AMQP\Transport\{
    Frame\Method,
    Protocol\Version,
    Protocol\Connection,
    Protocol\Channel,
    Protocol\Exchange,
    Protocol\Queue,
    Protocol\Basic,
    Protocol\Transaction,
};
use Innmind\Stream\Readable;
use Innmind\Immutable\Sequence;

interface Protocol
{
    public function version(): Version;
    public function use(Version $version): self;

    /**
     * @return Sequence<Value>
     */
    public function read(Method $method, Readable $arguments): Sequence;

    /**
     * @return Sequence<Value>
     */
    public function readHeader(Readable $arguments): Sequence;
    public function method(string $name): Method;
    public function connection(): Connection;
    public function channel(): Channel;
    public function exchange(): Exchange;
    public function queue(): Queue;
    public function basic(): Basic;
    public function transaction(): Transaction;
}
