<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Exception;

/**
 * To be thrown to cancel a consumer from inside a consumer callable
 */
final class Cancel extends RuntimeException
{
}
