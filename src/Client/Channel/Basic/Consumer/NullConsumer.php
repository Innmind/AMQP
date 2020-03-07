<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Client\Channel\Basic\Consumer;

use Innmind\AMQP\Client\Channel\Basic\Consumer as ConsumerInterface;

final class NullConsumer implements ConsumerInterface
{
    /**
     * {@inheritdoc}
     */
    public function foreach(callable $consume): void
    {
        //pass
    }

    /**
     * {@inheritdoc}
     */
    public function take(int $count): void
    {
        // pass
    }

    /**
     * {@inheritdoc}
     */
    public function filter(callable $predicate): void
    {
        // pass
    }
}
