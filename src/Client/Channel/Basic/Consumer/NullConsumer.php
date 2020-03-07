<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Client\Channel\Basic\Consumer;

use Innmind\AMQP\Client\Channel\Basic\Consumer as ConsumerInterface;

final class NullConsumer implements ConsumerInterface
{
    public function foreach(callable $consume): void
    {
        // pass
    }

    public function take(int $count): void
    {
        // pass
    }

    public function filter(callable $predicate): void
    {
        // pass
    }
}
