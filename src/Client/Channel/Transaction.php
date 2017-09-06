<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Client\Channel;

interface Transaction
{
    public function select(): self;
    public function commit(): self;
    public function rollback(): self;
}
