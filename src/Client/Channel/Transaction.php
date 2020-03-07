<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Client\Channel;

interface Transaction
{
    public function select(): void;
    public function commit(): void;
    public function rollback(): void;
}
