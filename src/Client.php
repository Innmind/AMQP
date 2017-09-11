<?php
declare(strict_types = 1);

namespace Innmind\AMQP;

use Innmind\AMQP\Client\Channel;

interface Client
{
    public function channel(): Channel;
    public function closed(): bool;
    public function close(): void;
}
