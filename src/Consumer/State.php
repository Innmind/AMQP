<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Consumer;

/**
 * @psalm-immutable
 */
enum State
{
    case ack;
    case reject;
    case requeue;
    case cancel;
}
