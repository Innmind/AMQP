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
    Protocol\Transaction
};
use Innmind\Immutable\{
    Str,
    StreamInterface
};

interface Protocol
{
    public function version(): Version;

    /**
     * @return StreamInterface<Value>
     */
    public function read(Method $method, Str $arguments): StreamInterface;
    public function connection(): Connection;
    public function channel(): Channel;
    public function exchange(): Exchange;
    public function queue(): Queue;
    public function basic(): Basic;
    public function transaction(): Transaction;
}
