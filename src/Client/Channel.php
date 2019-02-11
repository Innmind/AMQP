<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Client;

use Innmind\AMQP\Client\Channel\{
    Exchange,
    Queue,
    Basic,
    Transaction,
};

interface Channel
{
    public function exchange(): Exchange;
    public function queue(): Queue;
    public function basic(): Basic;
    public function transaction(): Transaction;
    public function closed(): bool;
    public function close(): void;
}
