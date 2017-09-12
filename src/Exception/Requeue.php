<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Exception;

/**
 * To be thrown to reject a message and put it back in the queue
 */
final class Requeue extends RuntimeException
{
}
