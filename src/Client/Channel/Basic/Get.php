<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Client\Channel\Basic;

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
     */
    public function __invoke(callable $consume): void;
}
