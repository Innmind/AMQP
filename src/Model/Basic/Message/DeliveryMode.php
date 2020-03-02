<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model\Basic\Message;

final class DeliveryMode
{
    private const NON_PERSISTENT = 1;
    private const PERSISTENT = 2;

    private static ?self $nonPersistent = null;
    private static ?self $persistent = null;

    private int $mode;

    private function __construct(int $mode)
    {
        $this->mode = $mode;
    }

    /**
     * Message will be lost in case of server crash
     */
    public static function nonPersistent(): self
    {
        return self::$nonPersistent ?? self::$nonPersistent = new self(self::NON_PERSISTENT);
    }

    /**
     * Will persist the message to disk on the server, meaning it will be restored
     * in case of a server crash (applies only to durable queues)
     */
    public static function persistent(): self
    {
        return self::$persistent ?? self::$persistent = new self(self::PERSISTENT);
    }

    public function toInt(): int
    {
        return $this->mode;
    }
}
