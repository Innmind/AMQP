<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Client\Channel\Basic;

interface Consumer
{
    /**
     * Will receive the following arguments:
     *
     * - Message $message
     * - bool $redelivered
     * - string $exchange
     * - string $routingKey
     */
    public function foreach(callable $consume): void;

    /**
     * Number of messages to process
     *
     * This number applies to the number of message received post-filter
     */
    public function take(int $count): self;

    /**
     * Messages not fulfilling the predicate will be requeued
     *
     * Use this only in the case the AMQP routing capabilities are not enough
     * as it can be the case if you need to filter against your domain data
     *
     * Will receive the following arguments:
     *
     * - Message $message
     * - bool $redelivered
     * - string $exchange
     * - string $routingKey
     */
    public function filter(callable $predicate): self;
}
