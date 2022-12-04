<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Failure;

use Innmind\AMQP\Failure;

/**
 * @psalm-immutable
 */
final class UnexpectedFrame extends Failure
{
    public function kind(): Kind
    {
        return Kind::unexpectedFrame;
    }
}
