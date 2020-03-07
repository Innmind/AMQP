<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Client\Channel\Basic;

use Innmind\AMQP\Model\Basic\Message;

interface Get
{
    /**
     * Will receive the following arguments:
     *
     * - Message $message
     * - bool $redelivered
     * - string $exchange
     * - string $routingKey
     * - int $messageCount
     *
     * @param callable(Message, bool, string, string, int): void $consume
     */
    public function __invoke(callable $consume): void;
}
