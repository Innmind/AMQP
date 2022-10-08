<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model\Basic\Message;

/**
 * @psalm-immutable
 */
enum DeliveryMode
{
    /**
     * Message will be lost in case of server crash
     */
    case nonPersistent;
    /**
     * Will persist the message to disk on the server, meaning it will be restored
     * in case of a server crash (applies only to durable queues)
     */
    case persistent;

    /**
     * @return int<1, 2>
     */
    public function toInt(): int
    {
        return match ($this) {
            self::nonPersistent => 1,
            self::persistent => 2,
        };
    }
}
