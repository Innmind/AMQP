<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Client\Channel\Basic;

use Innmind\AMQP\Model\Basic\Message;

interface Consumer
{
    /**
     * Will receive the following arguments:
     *
     * - Message $message
     * - bool $redelivered
     * - string $exchange
     * - string $routingKey
     *
     * @param callable(Message, bool, string, string): void $consume
     */
    public function foreach(callable $consume): void;

    /**
     * Number of messages to process
     *
     * This number applies to the number of message received post-filter
     */
    public function take(int $count): void;

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
     *
     * @param callable(Message, bool, string, string): bool $predicate
     */
    public function filter(callable $predicate): void;
}
