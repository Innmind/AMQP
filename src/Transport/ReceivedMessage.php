<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport;

use Innmind\AMQP\Model\Basic\Message;

/**
 * @internal
 */
final class ReceivedMessage
{
    private Connection $connection;
    private Message $message;

    private function __construct(Connection $connection, Message $message)
    {
        $this->connection = $connection;
        $this->message = $message;
    }

    public static function of(Connection $connection, Message $message): self
    {
        return new self($connection, $message);
    }

    public function connection(): Connection
    {
        return $this->connection;
    }

    public function message(): Message
    {
        return $this->message;
    }
}
